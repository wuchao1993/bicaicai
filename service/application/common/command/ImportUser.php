<?php

namespace app\common\command;

use app\common\model\Bank;
use app\common\model\LotteryCategory;
use app\common\model\SportsSecurityQa;
use app\common\model\User;
use app\common\model\UserAccountRecord;
use app\common\model\UserAutoRebateConfig;
use app\common\model\UserBankRelation;
use app\common\model\UserExtend;
use app\common\model\UserRechargeRecord;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class ImportUser extends Command {

    protected function configure() {
        $this->setName('importUser')->setDescription('导入其他平台用户数据');
    }

    protected function execute(Input $input, Output $output) {
        $importUserModel         = new \app\common\model\ImportUser();
        $bankModel               = new Bank();
        $userBankRelationModel   = new UserBankRelation();
        $userModel               = new User();
        $userExtendModel         = new UserExtend();
        $userAccountRecordModel  = new UserAccountRecord();
        $userRechargeRecordModel = new UserRechargeRecord();
        $lotteryCategoryModel    = new LotteryCategory();
//        $securityQaModel         = new SportsSecurityQa();
        $userRebateConfigModel   = new UserAutoRebateConfig();

        $users = $importUserModel->select();

        //需要初始化清空的表 user_bank_relation, user_account_record, user_recharge_record
        //TRUNCATE TABLE `ds_user_bank_relation`;TRUNCATE TABLE `ds_user_account_record`;TRUNCATE TABLE `ds_user_recharge_record`;

        foreach($users as $user) {
            $info = $userModel->where(['user_id' => $user['user_id']])->whereOr(['user_name' => $user['user_name']])->count();
            if ($info) {
                continue;
            }

            //根据银行中文名获取银行id
            if ($user->pay_card) {
                $bankName = $this->bankMap(trim($user->pay_card));
                if ($bankName) {
                    $bankInfo = $bankModel->where('bank_name', $bankName)->field('bank_id')->find();
                }
                if (!$bankInfo->bank_id || !$bankName) {
                    $output->writeln($user->pay_card . '|' . $bankName);exit;
                }
                $bankId = $bankInfo->bank_id;
            } else {
                $bankId = 0;
            }

            //导入bank_relation表
            if ($bankId) {
                $bankInsert['user_id']           = $user['user_id'];
                $bankInsert['bank_id']           = $bankId;
                $bankInsert['ub_bank_account']   = $user['pay_num'];
                $bankInsert['ub_bank_user_name'] = $user['pay_name'];
                $bankInsert['ub_address']        = $user['pay_address'];
                $userBankRelationModel->insert($bankInsert);
            }

            //导入user表
            if ($user['user_is_agent'] != 1 && $user['user_pid'] == 0) {
                $user['user_pid'] = 1;
            }
            $userInsert['user_id']                 = $user['user_id'];
            $userInsert['user_pid']                = $user['user_pid'];
            $userInsert['ul_id']                   = $user['ul_id'];
            $userInsert['bank_id']                 = $bankId;
            $userInsert['user_name']               = $user['user_name'];
            $userInsert['user_realname']           = $user['pay_name'];
            $userInsert['user_email']              = $user['user_email'];
            $userInsert['user_mobile']             = $user['user_mobile'];
            $userInsert['user_password']           = $user['user_password'];
            $userInsert['user_is_agent']           = $user['user_is_agent'];
            $userInsert['user_reg_ip']             = $user['user_reg_ip'];
            $userInsert['user_remark']             = '威廉希尔用户';
            $userInsert['user_createtime']         = $user['user_createtime'];
            $userInsert['user_agent_check_status'] = $user['user_is_agent'] == 1 ? Config::get('status.user_is_agent')['yes'] : Config::get('status.user_is_agent')['no'];
            $userInsert['user_status']             = Config::get('status.user_status')['unverified'];
            $userInsert['user_all_pid']            = $user['user_pid'];
            $userModel->insert($userInsert);

            //user_extend表
            $userExtendInsert['user_id']                = $user['user_id'];
            $userExtendInsert['ue_account_balance']     = $user['user_account_balance'];
            $userExtendInsert['ue_recharge_amount']     = $user['user_account_balance'];
            $userExtendInsert['ue_recharge_count']      = 1;
            $userExtendInsert['ue_recharge_max_amount'] = $user['user_account_balance'];
            $userExtendModel->insert($userExtendInsert);

            //user_account_record表
            if ($user['user_account_balance'] > 0) {
                $accountRecordInsert['user_id']              = $user['user_id'];
                $accountRecordInsert['uar_transaction_type'] = Config::get('status.account_record_transaction_type')['artificial_in'];
                $accountRecordInsert['uar_action_type']      = Config::get('status.account_record_action_type')['deposit'];
                $accountRecordInsert['uar_amount']           = $user['user_account_balance'];
                $accountRecordInsert['uar_after_balance']    = $user['user_account_balance'];
                $userAccountRecordModel->insert($accountRecordInsert);
            }

            //user_recharge_record表
            if ($user['user_account_balance'] > 0) {
                $rechargeRecordInsert['urr_no']                  = generate_order_number();
                $rechargeRecordInsert['user_id']                 = $user['user_id'];
                $rechargeRecordInsert['urr_recharge_account_id'] = 0;
                $rechargeRecordInsert['urr_type']                = Config::get('status.user_recharge_type')['system'];
                $rechargeRecordInsert['urr_amount']              = $user['user_account_balance'];
                $rechargeRecordInsert['urr_total_amount']        = $user['user_account_balance'];
                $rechargeRecordInsert['urr_traffic_amount']      = $user['user_account_balance'];
                $rechargeRecordInsert['urr_remark']              = '威廉希尔用户迁移入款';
                $rechargeRecordInsert['urr_status']              = Config::get('status.recharge_status')['success'];
                $userRechargeRecordModel->insert($rechargeRecordInsert);
            }

            //sports_security_qa
//            $qaInsert['ssq_user_id']  = $user['user_id'];
//            $qaInsert['ssq_question'] = $user['ask'];
//            $qaInsert['ssq_answer']   = $user['answer'];
//            $securityQaModel->insert($qaInsert);

            //user_rebate_config表
            $rebateConfig = $lotteryCategoryModel->getDefaultRebateMap();
            $rebateConfig = array_filter($rebateConfig);
            $rebate_data = array();
            foreach ($rebateConfig as $categoryId => $rebate) {
                $temp = array();
                $temp['user_id'] = $user['user_id'];
                $temp['lottery_category_id'] = $categoryId;
                $temp['user_rebate'] = $rebate;
                $temp['user_pid'] = $user['user_pid'];
                $rebate_data[] = $temp;
            }
            $userRebateConfigModel->insertAll($rebate_data);
        }

        //计算user_lower_count
        /**
        UPDATE ds_user AS a ,
        (SELECT
                c . user_id ,
                count(d . user_id) AS lower_count
            FROM
                ds_user AS c ,
                ds_user AS d
            WHERE
                c . user_id = d . user_pid
            GROUP BY
                d . user_pid
        ) AS b
        SET a . user_lower_count = b . lower_count
        WHERE
            a . user_id = b . user_id
        **/

        $output->writeln('success');
    }

    /**
     * 威廉的银行名称和我们这银行名称的映射
     * @param $bankName
     * @return mixed
     */
    public function bankMap($bankName) {
        $bankArr = [
            '中国工商银行' => '中国工商银行',
            '招商银行' => '招商银行',
            '中国银行' => '中国银行',
            '中国农业银行' => '中国农业银行',
            '中国民生银行' => '中国民生银行',
            '中国招商银行' => '招商银行',
            '中国建设银行' => '中国建设银行',
            '中国交通银行' => '交通银行',
            '交通银行' => '交通银行',
            '工商银行' => '中国工商银行',
            '工商银行卡' => '中国工商银行',
            '邮政储蓄银行' => '中国邮政储蓄银行',
            '兴业银行' => '兴业银行',
            '中国兴业银行' => '兴业银行',
            '建设银行' => '中国建设银行',
            '贵阳银行' => '贵阳银行',
            '华夏银行' => '华夏银行',
            '中国邮政储蓄银行' => '中国邮政储蓄银行',
            '邮政银行' => '中国邮政储蓄银行',
            '农业银行' => '中国农业银行',
            '上海浦发银行' => '浦发银行',
            '深圳发展银行' => '深圳发展银行',
            '中国邮政储蓄银行盘锦田家支行' => '中国邮政储蓄银行',
            '中国工商银行贵州省都匀市南工区支行' => '中国工商银行',
            '中国光大银行' => '中国光大银行',
            '民生银行' => '中国民生银行',
            '中国农行银行' => '中国农业银行',
            '广发银行' => '广发银行',
            '广东发展银行' => '广发银行',
            '光大银行' => '中国光大银行',
            '浦发银行' => '浦发银行',
            '平安银行' => '平安银行',
            '上海浦东发展银行' => '浦发银行',
            '中信银行' => '中信银行',
            '建行银行' => '中国建设银行',
            '长安银行' => '长安银行',
            '中国中信银行' => '中信银行',
            '中国建行银行' => '中国建设银行',
            '中国邮政银行' => '中国邮政储蓄银行',
            '上海银行' => '上海银行',
            '中国银行泰州支行' => '中国银行',
            '交通银行泰州支行' => '交通银行',
            '中国农业银行成都东光支行' => '中国农业银行',
        ];
        return $bankArr[$bankName];
    }
}