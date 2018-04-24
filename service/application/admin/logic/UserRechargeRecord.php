<?php

/**
 * 用户入款相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use Filipac\Ip;
use think\Collection;
use think\Config;
use think\Loader;
use think\Log;
use think\Model;

class UserRechargeRecord extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取人工入款列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getSystemList($params) {

        $returnArr = [
            'totalCount'     => 0,
            'subAmount'      => 0,
            'allAmount'      => 0,
            'list'           => [],
        ];

        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

        $condition ['urr_type'] = Config::get('status.user_recharge_type') ['system'];

        if(isset ($params ['user_name'])) {
            $userId = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            $condition['user_id'] = $userId;
        }
        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['urr_createtime'] = [
                [
                    'EGT',
                    $params ['start_date'],
                ],
                [
                    'ELT',
                    $params ['end_date'],
                ],
            ];
        }
        if(isset ($params ['urr_operation_type'])) {
            $condition ['urr_operation_type'] = $params ['urr_operation_type'];
        }
        if(isset ($params ['account_type']) && isset ($params ['account_value'])) {
            switch($params ['account_type']) {
                case Config::get('status.recharge_search_type') ['user'] :
                    $userId = Loader::model('User', 'logic')->getUserIdByUsername($params ['account_value']);
                    if(!empty($userId)) {
                        $condition['user_id'] = $userId;
                    }else {
                        return $returnArr;
                    }
                    break;
                case Config::get('status.recharge_search_type') ['orderno'] :
                    $condition ['urr_no'] = $params ['account_value'];
                    break;
                case Config::get('status.recharge_search_type') ['operator'] :
                    $operatorId = Loader::model('Member')->getUserIdByUsername($params ['account_value']);
                    if(!empty($operatorId)) {
                        $condition['urr_operator_id'] = $operatorId;
                    }else {
                        return $returnArr;
                    }
                    break;
            }
        }

        // 获取总条数
        $count = $userRechargeRecordModel->where($condition)->count();

        $list = $userRechargeRecordModel->where($condition)->order('urr_id desc')->limit($params ['num'])->page($params ['page'])->select();

        //批量获取用户名称
        $userIds = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name,ul_id,user_pid', 'user_id');

        //批量获取操作人名称
        $operatorIds = array_unique(extract_array($list, 'urr_operator_id'));
        $operatorList = Loader::model('Member')->where(['uid'=>['IN', $operatorIds]])->column('nickname', 'uid');

        $subAmount          = 0;
        $subDiscountAmount  = 0;

        if(!empty ($list)) {
            foreach($list as &$val) {
                $subAmount                          += $val ['urr_amount'];
                $subDiscountAmount                  += $val ['urr_recharge_discount'];

                $val['user_name'] = isset($userList[$val['user_id']])?$userList[$val['user_id']]['user_name']:'';
                $val['operator_name'] = isset($operatorList[$val['urr_operator_id']])?$operatorList[$val['urr_operator_id']]:'';
            }
        }

        // 总额
        $totalAmountList = $userRechargeRecordModel->field('sum(urr_amount) as urr_amount, sum(urr_recharge_discount) as urr_recharge_discount')->where($condition)->find();
        $allAmount      = $totalAmountList['urr_amount'];
        $allDiscountAmount = $totalAmountList['urr_recharge_discount'];

        $returnArr = [
            'totalCount'     => $count,
            'subAmount'      => sprintf("%.2f",$subAmount),
            'subDiscountAmount' => sprintf("%.2f",$subDiscountAmount),
            'allAmount'      => sprintf("%.2f",$allAmount),
            'allDiscountAmount' => sprintf("%.2f",$allDiscountAmount),
            'list'           => $list,
        ];

        return $returnArr;
    }

    /**
     * 新增人工入款
     *
     * @param
     *            $params
     * @return bool
     */
    public function addSystem($params) {
        // 获取用户信息
        $info = Loader::model('User')->where([
            'user_name' => $params ['user_name'],
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;
            return false;
        }

        $userId           = $info ['user_id'];
        $recharge_amount   = floatval($params ['urr_amount']);
        $recharge_discount = floatval($params ['urr_recharge_discount']);
        $total_amount      = bcadd($recharge_amount, $recharge_discount, 2);

        if($recharge_amount<=0&&$recharge_discount<=0){
            $this->errorcode = EC_AD_RECHARGE_AMOUNT_MUST_BE_GREATER_THAN_ZERO;
            return false;
        }

        if($recharge_amount>=100000000||$recharge_discount>=100000000){
            $this->errorcode = EC_AD_RECHARGE_AMOUNT_TOO_BIG;
            return false;
        }

        if(!isset($params['urr_operation_type'])){
            $this->errorcode = EC_AD_SYSTEM_BATCH_RECHARGE_OPERATION_TYPE_EMPTY;
            return false;
        }

        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

        // 入库
        $data ['user_id']                 = $userId;
        $data ['urr_no']                  = generate_order_number();
        $data ['urr_recharge_account_id'] = 0;
        $data ['urr_type']                = Config::get('status.user_recharge_type') ['system'];
        $data ['urr_amount']              = $recharge_amount;
        $data ['urr_recharge_discount']   = $recharge_discount;
        $data ['urr_total_amount']        = $total_amount;
        $data ['urr_traffic_amount']      = floatval($params ['urr_traffic_amount']);
        $data ['urr_operation_type']      = $params ['urr_operation_type'];
        $data ['urr_required_bet_amount'] = 0;
        $data ['urr_operator_id']         = MEMBER_ID;
        $data ['urr_remark']              = $params ['urr_remark'];
        $data ['urr_createtime']          = date('Y-m-d H:i:s');
        $data ['urr_confirm_time']        = date('Y-m-d H:i:s');
        $data ['urr_client_ip']           = Ip::get();
        $data ['urr_status']              = 1;

        //只有金额存在才判断是否首次入款
        if($recharge_amount>0 && $params ['urr_operation_type']==Config::get('status.operation_type_in')['recharge'] ){
            $first_info = $userRechargeRecordModel->isFirst($userId);
            $data['urr_is_first']  = empty($first_info)?Config::get('status.recharge_is_first') ['yes']:Config::get('status.recharge_is_first') ['no'];
        }

        $this->startTrans();

        $ret                     = $userRechargeRecordModel->insertGetId($data);
        if($ret) {

            $userExtendLogic     = Loader::model('UserExtend', 'logic');
            $user_before_balance = $userExtendLogic->getBalance($userId);

            $isFormal = $params ['urr_operation_type'] == Config::get('status.operation_type_in')['recharge']?true:false;
            $userExtendLogic->addRechargeAmount($userId, $recharge_amount, $recharge_discount,$isFormal);
            $recharge_after_balance = bcadd($user_before_balance, $recharge_amount, 3);

            if($recharge_amount>0){
                $account_record                          = [];
                $account_record ['user_id']              = $userId;
                $account_record ['uar_source_id']        = $ret;
                $account_record ['uar_source_type']      = Config::get('status.user_account_record_source_type') ['recharge'];
                $account_record ['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['artificial_in'];
                $account_record ['uar_action_type']      = Config::get('status.account_record_action_type') ['deposit'];
                $account_record ['uar_amount']           = $recharge_amount;
                $account_record ['uar_before_balance']   = $user_before_balance;
                $account_record ['uar_after_balance']    = $recharge_after_balance;
                $account_record ['uar_remark']           = $params ['urr_remark'] ? $params ['urr_remark'] : '';

                $resultAccount = Loader::model('UserAccountRecord')->insert($account_record);
                if($resultAccount == false){
                    $this->rollback();
                    $this->errorcode = EC_AD_ADD_RECHARGE_SYSTEM_ERROR;
                    return false;
                }
            }

            if($recharge_discount > 0) {
                $discount_account_record                          = [];
                $discount_account_record ['user_id']              = $userId;
                $discount_account_record ['uar_source_id']        = $ret;
                $discount_account_record ['uar_source_type']      = Config::get('status.user_account_record_source_type') ['recharge'];
                $discount_account_record ['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['discount'];
                $discount_account_record ['uar_action_type']      = Config::get('status.account_record_action_type') ['deposit'];
                $discount_account_record ['uar_amount']           = $recharge_discount;
                $discount_account_record ['uar_before_balance']   = $recharge_after_balance;
                $discount_account_record ['uar_after_balance']    = bcadd($recharge_after_balance, $recharge_discount, 3);
                $discount_account_record ['uar_remark']           = '人工入款优惠';

                $resultDiscount = Loader::model('UserAccountRecord')->insert($discount_account_record);
                if($resultDiscount == false){
                    $this->rollback();
                    $this->errorcode = EC_AD_ADD_RECHARGE_SYSTEM_ERROR;
                    return false;
                }
            }

            $this->commit();

            // 记录行为
            Loader::model('General', 'logic')->actionLog('add_money', 'UserRechargeRecord', $userId, MEMBER_ID, json_encode($data));

            $systemInfo = [
                'id' => $ret,
            ];

            return $systemInfo;
        }

        $this->rollback();

        $this->errorcode = EC_AD_ADD_RECHARGE_SYSTEM_ERROR;

        return false;
    }

    /**
     * 人工入款修改备注
     *
     * @param Request $request
     * @return array
     */
    public function editRemark($params)
    {
        //新的备注信息
        $data['urr_remark'] = $params ['urr_remark'];

        $ret = Loader::model('UserRechargeRecord') -> save($data, ['urr_no'=>$params ['urr_no']]);

        return $ret;
 
    }

    /**
     * 获取公司入款列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getCompanyList($params) {

        $returnArr = [
            'totalCount'     => 0,
            'subAmount'      => 0,
            'subTotalAmount' => 0,
            'allAmount'      => 0,
            'allTotalAmount' => 0,
            'allDiscountAmount' => 0,
            'list'           => [],
        ];

        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

        $condition ['urr_type'] = Config::get('status.user_recharge_type') ['company'];

        if(isset($params ['date_type']) && $params ['date_type'] == 1) {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['urr_createtime'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
            }
        } else {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['urr_confirm_time'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
            }
        }

        if(isset ($params ['min_amount']) && empty ($params ['max_amount'])) {
            $condition ['urr_amount'] = ['EGT',$params ['min_amount']];
        }
        if(isset ($params ['max_amount']) && empty ($params ['min_amount'])) {
            $condition ['urr_amount'] = ['ELT',$params ['max_amount']];
        }
        if(isset ($params ['min_amount']) && isset ($params ['max_amount'])) {
            $condition ['urr_amount'] = [
                [
                    'EGT',
                    $params ['min_amount'],
                ],
                [
                    'ELT',
                    $params ['max_amount'],
                ],
            ];
        }
        if(isset ($params ['account_type']) && isset ($params ['account_value'])) {
            switch($params ['account_type']) {
                case Config::get('status.recharge_search_type') ['user'] :
                    $userId = Loader::model('User', 'logic')->getUserIdByUsername($params ['account_value']);
                    if(!empty($userId)) {
                        $condition['user_id'] = $userId;
                    }else {
                        return $returnArr;
                    }
                    break;
                case Config::get('status.recharge_search_type') ['orderno'] :
                    $condition ['urr_no'] = $params ['account_value'];
                    break;
                case Config::get('status.recharge_search_type') ['operator'] :
                    $operatorId = Loader::model('Member')->getUserIdByUsername($params ['account_value']);
                    if(!empty($operatorId)) {
                        $condition['urr_operator_id'] = $operatorId;
                    }else{
                        return $returnArr;
                    }
                    break;
            }
        }
        if(isset ($params ['urr_recharge_account_id'])) {
            $condition ['urr_recharge_account_id'] = array('IN', $params ['urr_recharge_account_id']);
        }
        if(isset ($params ['urr_status'])) {
            $condition ['urr_status'] = $params ['urr_status'];
        }
        if(isset ($params ['ul_ids']) && !empty ($params ['ul_ids'])) {
            // 获取总条数
            $ul_ids = $params ['ul_ids'];

            $list = $userRechargeRecordModel->where($condition)->where('user_id','IN',function($query) use($ul_ids){
                $query->table('ds_user')->where(['ul_id'=>['IN', $ul_ids]])->field('user_id');
            })->order('urr_id desc')->limit($params ['num'])->page($params ['page'])->select();
        }else{
            if ( isset($params ['num']) ) {
               // 获取总条数
               $list = $userRechargeRecordModel->where($condition)->order('urr_id desc')->limit($params ['num'])->page($params ['page'])->select();
            }else{
                //报表下载，不超过30天
               $list = $userRechargeRecordModel->where($condition)->order('urr_id desc')->select(); 
            }
        }

        //批量获取用户名称
        $userIds = extract_array($list, 'user_id');
        $userIds = array_unique($userIds);
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name,ul_id,user_pid,user_realname', 'user_id');


        //批量获取用户上级名称
        $userPIds = extract_array($userList, 'user_pid');
        $userPList = Loader::model('User')->where(['user_id'=>['IN', $userPIds]])->column('user_name', 'user_id');

        //批量获取银行名称
        $bankList = Loader::model('Bank')->column('bank_name', 'bank_id');
        //批量获取用户层级名称
        $userULIds = extract_array($userList, 'ul_id');
        $userULIds = array_unique($userULIds);
        $userULList = Loader::model('UserLevel')->where(['ul_id'=>['IN', $userULIds]])->column('ul_name', 'ul_id');

        //批量获取操作人名称
        $operatorIds = array_unique(extract_array($list, 'urr_operator_id'));
        $operatorList = Loader::model('Member')->where(['uid'=>['IN', $operatorIds]])->column('nickname', 'uid');

        //批量获取入款账号名称
        $rechargeAccountIds = extract_array($list, 'urr_recharge_account_id');
        $rechargeAccountList = Loader::model('PayAccount')->where(['pa_id'=>['IN', $rechargeAccountIds]])->column('bank_id,pa_collection_user_name', 'pa_id');


        //今日大额提醒 //TODO 影响查询速度，任务跑统计数据
        $userLarge = [];
        if($params ['end_date']>date("Y-m-d 00:00:00")){
            $userLarge = $this->countLargeAmountUser($userList,$userULIds);
        }

        $subAmount      = 0;
        $subTotalAmount = 0;

        if(!empty ($list)) {
            foreach($list as &$val) {
                $subAmount                          += $val ['urr_amount'];
                $subTotalAmount                     += $val ['urr_total_amount'];

                $val['user_name'] = isset($userList[$val['user_id']])?$userList[$val['user_id']]['user_name']:'';
                $val['user_realname'] = isset($userList[$val['user_id']])?$userList[$val['user_id']]['user_realname']:'';

                if(isset($userList[ $val['user_id'] ]) && isset($userPList[ $userList[ $val['user_id'] ]['user_pid'] ]) ){
                    $val['parent_user_name'] = $userPList[ $userList[ $val['user_id'] ]['user_pid'] ];
                }else{
                    $val['parent_user_name'] = '';
                }
                $val['bank_name'] = isset($bankList[$val['urr_recharge_bank_id']])?$bankList[$val['urr_recharge_bank_id']]:'';

                if(isset($userList[ $val['user_id'] ]) && isset($userULList[$userList[ $val['user_id'] ]['ul_id']]) ){
                    $val['ul_name'] = $userULList[$userList[ $val['user_id'] ]['ul_id']];
                }else{
                    $val['ul_name'] = '';
                }

                $val['operator_name'] = isset($operatorList[$val['urr_operator_id']])?$operatorList[$val['urr_operator_id']]:'';

                if(isset($rechargeAccountList[ $val['urr_recharge_account_id'] ]) && isset($bankList[$rechargeAccountList[ $val['urr_recharge_account_id'] ]['bank_id']])){
                    $val['recharge_account_bank_name'] = $bankList[$rechargeAccountList[ $val['urr_recharge_account_id'] ]['bank_id']];
                }else{
                    $val['recharge_account_bank_name'] = '';
                }

                $val['recharge_account_name'] = isset($rechargeAccountList[$val['urr_recharge_account_id']])?$rechargeAccountList[$val['urr_recharge_account_id']]['pa_collection_user_name']:'';

                $val['user_large_amount'] = isset($userLarge[$val['user_id']])?$userLarge[$val['user_id']]:'';
            }
        }


        // 总额
        if(isset ($params ['ul_ids']) && !empty ($params ['ul_ids'])) {
            $totalAmountList = $userRechargeRecordModel->field('count(*) as order_count,sum(urr_amount) as urr_amount, sum(urr_total_amount) as urr_total_amount, sum(urr_recharge_discount) as urr_total_recharge_discount')->where($condition)
                ->where('user_id','IN',function($query) use($ul_ids){$query->table('ds_user')->where(['ul_id'=>['IN', $ul_ids]])->field('user_id');})->find();
        }else{
            $totalAmountList = $userRechargeRecordModel->field('count(*) as order_count,sum(urr_amount) as urr_amount, sum(urr_total_amount) as urr_total_amount, sum(urr_recharge_discount) as urr_total_recharge_discount')->where($condition)->find();
        }
        $allAmount      = $totalAmountList['urr_amount'];
        $allTotalAmount = $totalAmountList['urr_total_amount'];
        $allDiscountAmount = $totalAmountList['urr_total_recharge_discount'];
        $count          = $totalAmountList['order_count'];

        $returnArr = [
            'totalCount'     => $count,
            'subAmount'      => sprintf("%.2f",$subAmount),
            'subTotalAmount' => sprintf("%.2f",$subTotalAmount),
            'allAmount'      => sprintf("%.2f",$allAmount),
            'allTotalAmount' => sprintf("%.2f",$allTotalAmount),
            'allDiscountAmount' => sprintf("%.2f",$allDiscountAmount),
            'list'           => $list,
        ];

        return $returnArr;
    }


    protected function countLargeAmountUser($userList,$userULIds){

        $userIds = array_keys($userList);

        $condition = [];
        $condition['urr_createtime'] = [
            ['EGT', date("Y-m-d 00:00:00")],
            ['ELT', date("Y-m-d H:i:s")]
        ];
        $condition['user_id']   = ['IN',$userIds];
        $condition['urr_type']  = Config::get('status.user_recharge_type')['company'];

        //是否是已充值成功的
        $condition['urr_status']= Config::get('status.recharge_status')['success'];

        $list = $this->where($condition)->group("user_id")->field("user_id,sum(urr_amount) as total_recharge")->select();
        $list = $list ? collection($list)->toArray() : [];

        $largeUser = [];
        if(!empty($list)){
            $payConfig =  Loader::model('PayConfig','logic')->getUlCompanyLargeAmount($userULIds);

            foreach ($list as $vo){
                $ulId = isset($userList[$vo['user_id']])?$userList[$vo['user_id']]['ul_id']:'';
                if(!empty($ulId) && $payConfig[$ulId]>0 && $vo['total_recharge']>=$payConfig[$ulId]){
                    $largeUser[$vo['user_id']] = $vo['total_recharge'];
                }
            }
        }

        return $largeUser;
    }


    /**
     * 公司入款确认
     *
     * @param
     *            $params
     * @return bool
     */
    public function confirmCompany($params) {
        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

        // 获取入款信息
        $info = $userRechargeRecordModel->where([
            'urr_id' => $params ['urr_id'],
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_RECHARGE_RECORD_NONE;

            return false;
        }

        //是否首次充值
        $first_info = $userRechargeRecordModel->isFirst($info['user_id']);

        // 确认
        $data                      = [];
        $data ['urr_confirm_time'] = date('Y-m-d H:i:s');
        $data ['urr_operator_id']  = MEMBER_ID;
        $data ['urr_status']       = Config::get('status.recharge_status') ['success'];

        if(empty($first_info)) $data['urr_is_first'] = Config::get('status.recharge_is_first') ['yes'];
        $this->startTrans();
        $ret = $userRechargeRecordModel->save($data, [
            'urr_id'        => $info ['urr_id'],
            'urr_status'    => Config::get('status.recharge_status')['wait']
        ]);

        if($ret) {

            $userId           = $info ['user_id'];
            $recharge_amount   = floatval($info ['urr_amount']);
            $recharge_discount = floatval($info ['urr_recharge_discount']);

            $userExtendLogic     = Loader::model('UserExtend', 'logic');
            $user_before_balance = $userExtendLogic->getBalance($userId);

            $res = $userExtendLogic->addRechargeAmount($userId, $recharge_amount, $recharge_discount);
            if($res ===  false){
                $this->errorcode = EC_AD_RECHARGE_COMPANY_CONFIRM_ERROR;
                $this->rollback();
                return false;
            }

            Loader::model('PayAccount')->addRechargeAmount($info['urr_recharge_account_id'], $recharge_amount);

            $recharge_after_balance = bcadd($user_before_balance, $recharge_amount, 3);

            $account_record                          = [];
            $account_record ['user_id']              = $userId;
            $account_record ['uar_source_id']        = $info ['urr_id'];
            $account_record ['uar_source_type']      = Config::get('status.user_account_record_source_type') ['recharge'];
            $account_record ['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['recharge_company'];
            $account_record ['uar_action_type']      = Config::get('status.account_record_action_type') ['deposit'];
            $account_record ['uar_amount']           = $recharge_amount;
            $account_record ['uar_before_balance']   = $user_before_balance;
            $account_record ['uar_after_balance']    = $recharge_after_balance;
            $account_record ['uar_remark']           = '公司入款';

            $res = Loader::model('UserAccountRecord')->insert($account_record);
            if(!$res){
                $this->errorcode = EC_AD_RECHARGE_COMPANY_CONFIRM_ERROR;
                $this->rollback();
                return false;
            }
            if($recharge_discount > 0) {
                $discount_account_record                          = [];
                $discount_account_record ['user_id']              = $userId;
                $discount_account_record ['uar_source_id']        = $info ['urr_id'];
                $discount_account_record ['uar_source_type']      = Config::get('status.user_account_record_source_type') ['recharge'];
                $discount_account_record ['uar_transaction_type'] = Config::get('status.account_record_transaction_type') ['discount'];
                $discount_account_record ['uar_action_type']      = Config::get('status.account_record_action_type') ['deposit'];
                $discount_account_record ['uar_amount']           = $recharge_discount;
                $discount_account_record ['uar_before_balance']   = $recharge_after_balance;
                $discount_account_record ['uar_after_balance']    = bcadd($recharge_after_balance, $recharge_discount, 3);
                $discount_account_record ['uar_remark']           = '公司入款优惠';

                $res = Loader::model('UserAccountRecord')->insert($discount_account_record);
                if(!$res){
                    $this->errorcode = EC_AD_RECHARGE_COMPANY_CONFIRM_ERROR;
                    $this->rollback();
                    return false;
                }
            }
            $this->commit();
            return true;
        }

        $this->errorcode = EC_AD_RECHARGE_COMPANY_CONFIRM_ERROR;
        $this->rollback();
        return false;
    }

    /**
     * 公司入款取消
     *
     * @param
     *            $params
     * @return bool
     */
    public function cancelCompany($params) {

        if(strlen($params['urr_reason']) > 300){
            $this->errorcode = EC_AD_CANCEL_COMPANY_BEYOND_WORD_COUNT;
            return false;
        }

        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

        // 获取入款信息
        $info = $userRechargeRecordModel->where([
            'urr_id' => $params ['urr_id'],
        	'urr_status' => Config::get('status.recharge_status')['wait']
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_RECHARGE_RECORD_NONE;

            return false;
        }

        // 确认
        $data                      = [];
        $data ['urr_confirm_time'] = date('Y-m-d H:i:s');
        $data ['urr_operator_id']  = MEMBER_ID;
        $data ['urr_status']       = Config::get('status.recharge_status') ['fail'];
        $data ['urr_reason']       = $params['urr_reason'];

        $ret = $userRechargeRecordModel->save($data, [
            'urr_id' => $info ['urr_id'],
        ]);

        if($ret) {
            return true;
        }

        $this->errorcode = EC_AD_RECHARGE_COMPANY_CONFIRM_ERROR;

        return false;
    }

    /**
     * 公司入款恢复取消
     *
     * @param
     *            $params
     * @return bool
     */
    public function reCancelCompany($params) {
        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

        // 获取入款信息
        $info = $userRechargeRecordModel->where([
            'urr_id' => $params ['urr_id'],
        	'urr_status' => Config::get('status.recharge_status')['fail']
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_RECHARGE_RECORD_NONE;

            return false;
        }

        // 确认
        $data                      = [];
        $data ['urr_confirm_time'] = date('Y-m-d H:i:s');
        $data ['urr_operator_id']  = MEMBER_ID;
        $data ['urr_status']       = Config::get('status.recharge_status') ['wait'];
        $data ['urr_reason']       = '';

        $ret = $userRechargeRecordModel->save($data, [
            'urr_id' => $info ['urr_id'],
        ]);

        if($ret) {
            return true;
        }

        $this->errorcode = EC_AD_RECHARGE_COMPANY_CONFIRM_ERROR;

        return false;
    }


    /**
     * 获取在线入款列表
     * @param  $params
     * @return array
     */
    public function getOnlineList($params) {

        $returnArr = [
            'totalCount' => 0,
            'totals'     => 0,
            'subtotals'  => 0,
            'list'       => [],
        ];
        $condition = array();
        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

        $condition ['urr_type'] = Config::get('status.user_recharge_type') ['online'];

        //默认时间-当天
        if($params ['date_type'] == 1) {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['urr_createtime'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
                $forceIndex = "urr_createtime";
            }
        } else {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['urr_confirm_time'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
                $forceIndex = "urr_confirm_time";
            }
        }


        if(isset ($params ['min_amount']) && isset ($params ['max_amount'])) {
            $condition ['urr_amount'] = [
                [
                    'EGT',
                    $params ['min_amount'],
                ],
                [
                    'ELT',
                    $params ['max_amount'],
                ],
            ];
        } else if(isset ($params ['min_amount'])) {
            $condition ['urr_amount'] = [
                'EGT',
                $params ['min_amount'],
            ];
        } else if(isset ($params ['max_amount'])) {
            $condition ['urr_amount'] = [
                'ELT',
                $params ['max_amount'],
            ];
        }

        if(isset ($params ['account_type']) && isset ($params ['account_value'])) {
            switch($params ['account_type']) {
                case Config::get('status.recharge_search_type') ['user'] :
                    $userId = Loader::model('User', 'logic')->getUserIdByUsername($params ['account_value']);
                    if(!empty($userId)) {
                        $condition['user_id'] = $userId;
                    }else {
                        return $returnArr;
                    }
                    break;
                case Config::get('status.recharge_search_type') ['orderno'] :
                    $condition ['urr_no'] = $params ['account_value'];
                    break;
                case Config::get('status.recharge_search_type') ['operator'] :
                    $operatorId = Loader::model('Member')->getUserIdByUsername($params ['account_value']);
                    if(!empty($operatorId)) {
                        $condition['urr_operator_id'] = $operatorId;
                    }else {
                        return $returnArr;
                    }
                    break;
            }
        }
        if(!empty ($params ['urr_recharge_account_id'])) {
            $condition ['urr_recharge_account_id'] = ['IN', $params ['urr_recharge_account_id']];
        }
        if(isset ($params ['urr_status'])) {
            $condition ['urr_status'] = $params ['urr_status'];
        }
        if(isset ($params ['ul_ids']) && !empty ($params ['ul_ids'])) {
            $ul_ids = $params ['ul_ids'];

            $list = $userRechargeRecordModel->force($forceIndex)->where($condition)->where('user_id','IN',function($query) use($ul_ids){
                $query->table('ds_user')->where(['ul_id'=>['IN', $ul_ids]])->field('user_id');
            })->order('urr_id desc')->limit($params ['num'])->page($params ['page'])->select();
        }else{

            $list = $userRechargeRecordModel->force($forceIndex)->where($condition)->order('urr_id desc')->limit($params ['num'])->page($params ['page'])->select();
        }


        //批量获取用户名称
        $userIds = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name,ul_id,user_pid', 'user_id');

        //批量获取用户上级名称
        $userPIds = extract_array($userList, 'user_pid');
        $userPList = Loader::model('User')->where(['user_id'=>['IN', $userPIds]])->column('user_name', 'user_id');

        //批量获取银行名称
        $bankIds = extract_array($list, 'urr_recharge_bank_id');
        $bankList = Loader::model('Bank')->where(['bank_id'=>['IN', $bankIds]])->column('bank_name', 'bank_id');

        //批量获取用户层级名称
        $userULIds = extract_array($userList, 'ul_id');
        $userULList = Loader::model('UserLevel')->where(['ul_id'=>['IN', $userULIds]])->column('ul_name', 'ul_id');

        //批量获取操作人名称
        $operatorIds = array_unique(extract_array($list, 'urr_operator_id'));
        $operatorList = Loader::model('Member')->where(['uid'=>['IN', $operatorIds]])->column('nickname', 'uid');

        //批量获取支付平台名称
        $rechargeAccountIds = extract_array($list, 'urr_recharge_account_id');
        $rechargeAccountList = Loader::model('PayPlatform')->where(['pp_id'=>['IN', $rechargeAccountIds]])->column('pay_type_id,pp_category_id', 'pp_id');
        //批量获取支付类型名称
        $payTypeIds = extract_array($rechargeAccountList, 'pay_type_id');
        $payTypeList = Loader::model('PayType')->where(['pay_type_id'=>['IN', $payTypeIds]])->column('pay_type_name', 'pay_type_id');

        $payCenterRechargeAccountList = $this->getPayChannelMerchant($rechargeAccountIds);
        $payCenterPayTypeIds = extract_array($payCenterRechargeAccountList, 'pay_channel_id');
        $payCenterPayTypeList = $this->getPayChannelName($payCenterPayTypeIds);

        $payCenterPayTypeMap = Loader::model('common/PayCenter', 'logic')->getPayCenterPayTypeMap();
        $rechargeTypeIdCodeMap = Loader::model('common/RechargeType')->getRechargeTypeIdCodeMap();
        $subtotals = [];
        $payCenterStatus = Loader::model('common/SiteConfig')->getConfig('sports', 'common', 'pay_center_status');
        if(!empty ($list)) {
            foreach($list as &$val) {
                $code = $payCenterPayTypeMap[$payCenterRechargeAccountList[$val['urr_recharge_account_id']]['pay_type_id']];
                $subtotals['amount']           = bcadd(!empty($subtotals['amount'])?$subtotals['amount']:0, $val['urr_amount'], 2);
                $subtotals['rechargeDiscount'] = bcadd(!empty($subtotals['rechargeDiscount'])?$subtotals['rechargeDiscount']:0, $val['urr_recharge_discount'], 2);
                $subtotals['totalAmount']      = bcadd(!empty($subtotals['totalAmount'])?$subtotals['totalAmount']:0, $val['urr_total_amount'], 2);

                $val['user_name'] = $userList[$val['user_id']]['user_name'];
                $val['parent_user_name'] = !empty($userPList[ $userList[ $val['user_id'] ]['user_pid'] ])?$userPList[ $userList[ $val['user_id'] ]['user_pid'] ]:'';
                $val['bank_name'] = !empty($bankList[$val['urr_recharge_bank_id']])?$bankList[$val['urr_recharge_bank_id']]:'';
                $val['ul_name'] = $userULList[$userList[ $val['user_id'] ]['ul_id']];
                $val['operator_name'] = !empty($operatorList[$val['urr_operator_id']])?$operatorList[$val['urr_operator_id']]:'';
                $val['pp_category_id'] = $rechargeAccountList[ $val['urr_recharge_account_id'] ]['pp_category_id'];
                if($payCenterStatus['pay_center_status'] == Config::get('status.pay_center_status')['enable']){
                   $val['recharge_platform'] = $payCenterPayTypeList[$payCenterRechargeAccountList[$val['urr_recharge_account_id']]['pay_channel_id']];
                   $val['pay_category_name'] = $rechargeTypeIdCodeMap[$code]['recharge_type_name']; 
                }else{
                   $val['recharge_platform'] = $payTypeList[ $rechargeAccountList[$val['urr_recharge_account_id']]['pay_type_id']];
                }
                //超过两个月的待支付状态要换成已关闭
                $expireDate = (time() - strtotime($val['urr_createtime'])) / 86400;
                if($val['urr_status'] == Config::get('status.recharge_status') ['wait'] && $expireDate > 60) {
                    $val['urr_status'] = Config::get('status.recharge_status') ['close'];
                }

            }
        }
        $fields = 'count(*) as order_count,sum(urr_amount) as amount, sum(urr_recharge_discount) as rechargeDiscount, sum(urr_total_amount) as totalAmount';
        if(isset ($params ['ul_ids']) && !empty ($params ['ul_ids'])) {
            $totals = $userRechargeRecordModel->where($condition)->where('user_id','IN',function($query) use($ul_ids){
                $query->table('ds_user')->where(['ul_id'=>['IN', $ul_ids]])->field('user_id');
            })->field($fields)->find()->toArray();
        }else{
            $totals = $userRechargeRecordModel->where($condition)->field($fields)->find()->toArray();
        }

        $returnArr = [
            'totalCount' => $totals['order_count'],
            'totals'     => $totals,
            'subtotals'  => $subtotals,
            'list'       => $list,
        ];

        return $returnArr;
    }

    public function getPayChannelName($ids){
        $payTypeList = Loader::model('PayCenterChannel')->where(['pay_channel_id'=>['IN', $ids]])->column('name', 'pay_channel_id');
        return $payTypeList;
    }

    public function getPayChannelMerchant($ids){
        $list = Loader::model('PayCenterChannelMerchant')->where(['channel_merchant_id'=>['IN', $ids]])->column('pay_channel_id,pay_type_id,account', 'channel_merchant_id');
        return $list;
    }

    private function getPayTypeName($ids){
        $list = Loader::model('RechargeType')->where(['recharge_type_id'=>['IN', $ids]])->column('recharge_type_name', 'recharge_type_id');
        return $list;
    }


    /**
     * 获取需要检查的列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getNeedCheck($userId) {
        $condition                     = [];
        $condition ['user_id']         = $userId;
        $condition ['urr_is_withdraw'] = Config::get('status.recharge_record_withdraw_status') ['no'];
        $condition ['urr_status']      = Config::get('status.recharge_status') ['success'];

        $list = Loader::model('UserRechargeRecord')->where($condition)->order('urr_id DESC')->select();

        return $list;
    }

    /**
     * 获取需要检查的列表(小额)
     *
     * @param
     *            $params
     * @return array
     */
    public function getNeedCheckLow($userId) {
        $condition                     = [];
        $condition ['slobr_user_id']         = $userId;
        $condition ['slobr_is_withdraw'] = Config::get('status.recharge_record_withdraw_status') ['no'];

        $list = Loader::model('common/SportsLowOddsBonusRecord')->where($condition)->order('slobr_id DESC')->select();

        return $list;
    }

    /**
     * 获取用户最高可提现金额
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserMaxWithdrawAmount($userId,$pcRelaxAmount) {
        $userExtendLogic = Loader::model('UserExtend', 'logic');
        $userBalance     = $userExtendLogic->getBalance($userId);

        $notAllowWithdrawAmount   = Loader::model('common/User', 'logic')->getNotAllowWithdrawAmount($userId, $pcRelaxAmount);

        $user_max_withdraw_amount = bcsub($userBalance, $notAllowWithdrawAmount, 2);

        return $user_max_withdraw_amount < 0 ? 0 : $user_max_withdraw_amount;
    }

    /**
     * 获取用户不可提现金额
     *
     * @param
     *            $params
     * @return array
     */
    public function getNotAllowWithdrawAmount($userId, $pcRelaxAmount) {
        $condition = array();
        $condition['user_id'] = $userId;
        $condition['urr_status'] = Config::get('status.recharge_status') ['success'];
        $condition['urr_is_withdraw'] = Config::get('status.recharge_record_withdraw_status') ['no'];
        $condition['urr_required_bet_amount'] = array('EXP', '<(urr_traffic_amount - '.$pcRelaxAmount.')' );

        $result = Loader::model('UserRechargeRecord')->where($condition)->field( 'sum(urr_amount + urr_recharge_discount) as sum_amount_recharge_discount ')->find();

        return $result['sum_amount_recharge_discount'];

    }


    public function enforceOnlineRecharge($id) {
        $this->startTrans();

        $rechargeRecordInfo = Loader::model('UserRechargeRecord')->where(['urr_id' => $id])->lock(true)->find();

        if(empty($rechargeRecordInfo)) {
            Log::write('enforceOnlineRecharge:没有充值记录');
            $this->errorcode = EC_AD_ENFORCE_ONLINE_RECHARGE_FAIL;
            $this->rollback();
        } else {
            $amount = $rechargeRecordInfo['urr_amount'];
            if(!$amount) {
                Log::write('enforceOnlineRecharge:金额为空');
                $this->errorcode = EC_AD_ENFORCE_ONLINE_RECHARGE_FAIL;
                $this->rollback();
                return;
            }

            $payStatus = $rechargeRecordInfo['urr_status'];
            if($payStatus != Config::get('status.recharge_status')['wait']) {
                Log::write('enforceOnlineRecharge:充值状态异常');
                $this->errorcode = EC_AD_ENFORCE_ONLINE_RECHARGE_FAIL;
                $this->rollback();
                return;
            }

            $userId           = $rechargeRecordInfo['user_id'];
            $recordId         = $rechargeRecordInfo['urr_id'];
            $rechargeDiscount = $rechargeRecordInfo['urr_recharge_discount'];
            $tradeNo          = $rechargeRecordInfo['urr_trade_no'];

            $saveData                     = [];
            $saveData['urr_trade_no']     = $tradeNo;
            $saveData['urr_trade_time']   = current_datetime();
            $saveData['urr_status']       = Config::get('status.recharge_status')['success'];
            $saveData['urr_operator_id']  = MEMBER_ID;
            $saveData['urr_confirm_time'] = current_datetime();

            $rechargeResult = Loader::model('UserRechargeRecord')->save($saveData, ['urr_id' => $id]);
            if($rechargeResult == false) {
                $this->rollback();
                $this->errorcode = EC_AD_ENFORCE_ONLINE_RECHARGE_FAIL;
            } else {
                $userBeforeBalance = Loader::model('UserExtend')->getBalance($userId);
                $extendResult      = Loader::model('UserExtend')->addRechargeAmount($userId, $amount, $rechargeDiscount);
                if(empty($extendResult)) {
                    $this->rollback();
                    Log::write('enforceOnlineRecharge:更新账户失败！');
                    $this->errorcode = EC_AD_ENFORCE_ONLINE_RECHARGE_FAIL;
                } else {
                    $userAfterBalance = bcadd($userBeforeBalance, $amount, 3);

                    $userAccountRecord['user_id']              = $userId;
                    $userAccountRecord['uar_source_id']        = $recordId;
                    $userAccountRecord['uar_source_type']      = Config::get('status.user_account_record_source_type')['recharge'];
                    $userAccountRecord['uar_transaction_type'] = Config::get('status.account_record_transaction_type')['recharge'];
                    $userAccountRecord['uar_action_type']      = Config::get('status.account_transfer')['in'];
                    $userAccountRecord['uar_amount']           = $amount;
                    $userAccountRecord['uar_before_balance']   = $userBeforeBalance;
                    $userAccountRecord['uar_after_balance']    = $userAfterBalance;
                    $userAccountRecord['uar_remark']           = '在线充值';

                    $recordData[] = $userAccountRecord;

                    if(bccomp($rechargeDiscount, 0, 3) > 0) {
                        $discountAccountRecord                         = [];
                        $discountAccountRecord['user_id']              = $userId;
                        $discountAccountRecord['uar_source_id']        = $recordId;
                        $discountAccountRecord['uar_source_type']      = Config::get('status.user_account_record_source_type')['recharge'];
                        $discountAccountRecord['uar_transaction_type'] = Config::get('status.account_record_transaction_type')['recharge'];
                        $discountAccountRecord['uar_action_type']      = Config::get('status.account_transfer')['in'];
                        $discountAccountRecord['uar_amount']           = $rechargeDiscount;
                        $discountAccountRecord['uar_before_balance']   = $userAfterBalance;
                        $discountAccountRecord['uar_after_balance']    = bcadd($userAfterBalance, $rechargeDiscount, 3);

                        $discountAccountRecord['uar_remark'] = '在线充值优惠';

                        $recordData[] = $discountAccountRecord;
                    }

                    $recordResult = Loader::model('UserAccountRecord')->saveAll($recordData);

                    if($recordResult == false) {
                        Log::write('enforceOnlineRecharge:更新金流记录失败！');
                        $this->errorcode = EC_AD_ENFORCE_ONLINE_RECHARGE_FAIL;
                        $this->rollback();
                    } else {
                        $payCenterStatus = Loader::model('common/SiteConfig')->getConfig('sports', 'common', 'pay_center_status');
                        Log::write('enforceOnlineRecharge-payCenterStatus:'. print_r($payCenterStatus,true));
                        if($payCenterStatus['pay_center_status'] == Config::get('status.pay_center_status')['enable']){
                            $this->commit();
                        }else{
                            $payPlatformResult = Loader::model('PayPlatform')->addStatistics($rechargeRecordInfo['urr_recharge_account_id'], $amount);
                            if($payPlatformResult == false) {
                                Log::write('enforceOnlineRecharge:支付平台统计出错！');
                                $this->errorcode = EC_AD_ENFORCE_ONLINE_RECHARGE_FAIL;
                                $this->rollback();
                            } else {
                                $this->commit();
                            }
                        }
                    }
                }
            }
        }
    }

    public function getStatistics($userIds, $startTime, $endTime){
        if(empty($userIds)) return [];

        $condition = array();
        $condition['user_id']        = array('in', $userIds);
        $condition['urr_status']     = Config::get('status.recharge_status')['success'];
        $condition['urr_createtime'] = array('between', array($startTime, $endTime));
        $fields = array('user_id', 'count(*)'=>'recharge_count', 'SUM(urr_amount)'=>'recharge_total');
        return Loader::model('UserRechargeRecord')->where($condition)->field($fields)->group('user_id')->select();
    }


    public function modifyRequiredBetAmount($userId, $betTime, $betValidAmount){
        if($betValidAmount > 0){

            //先扣除小额
            $checkLowList = $this->getLowNeedDeductTrafficList($userId, $betTime);

            if($checkLowList){
                foreach ($checkLowList as $info)
                {
                    if($betValidAmount <= 0)
                        break;
                    else{
                        $diff = bcsub($info['slobr_traffic_amount'],  $info['slobr_require_bet_amount'], 2);
                        if($betValidAmount > $diff){
                            $newRequiredBetAmount = $info['slobr_traffic_amount'];
                            $betValidAmount = bcsub($betValidAmount, $diff, 2);
                        }else{
                            $newRequiredBetAmount = bcadd($betValidAmount,  $info['slobr_require_bet_amount'], 2);
                            $betValidAmount = 0;
                        }
                        $result = $this->modifyLowBetAmount($info['slobr_id'], $newRequiredBetAmount);
                        if(empty($result)){
                            return false;
                        }
                    }
                }
            }

            //然后再大额
            if($betValidAmount > 0) {
                $checkList = $this->getNeedDeductTrafficList($userId, $betTime);
                if($checkList) {
                    foreach($checkList as $info) {
                        if($betValidAmount <= 0) break; else {
                            $diff = bcsub($info['urr_traffic_amount'], $info['urr_required_bet_amount'], 2);
                            if($betValidAmount > $diff) {
                                $newRequiredBetAmount = $info['urr_traffic_amount'];
                                $betValidAmount       = bcsub($betValidAmount, $diff, 2);
                            } else {
                                $newRequiredBetAmount = bcadd($betValidAmount, $info['urr_required_bet_amount'], 2);
                                $betValidAmount       = 0;
                            }
                            $result = $this->modifyBetAmount($info['urr_id'], $newRequiredBetAmount);
                            if(empty($result)) {
                                return false;
                            }
                        }
                    }
                }
            }

            return true;
        }
    }

    public function getLowNeedDeductTrafficList($userId, $time){

        $sportsLowOddsBonusRecordModel = Loader::model('common/SportsLowOddsBonusRecord');

        $condition = [];
        $condition['slobr_user_id']                   = $userId;
        $condition['slobr_is_withdraw']           = Config::get("status.recharge_record_withdraw_status")['no'];
        $condition['slobr_create_time']            = array('LT', $time);
        $condition['slobr_require_bet_amount']   = array('EXP', '<slobr_traffic_amount');
        return $sportsLowOddsBonusRecordModel->where($condition)->order('slobr_create_time DESC')->select();
    }

    public function getNeedDeductTrafficList($userId, $time){
        $condition = [];
        $condition['user_id']                   = $userId;
        $condition['urr_is_withdraw']           = Config::get("status.recharge_record_withdraw_status")['no'];
        $condition['urr_createtime']            = array('LT', $time);
        $condition['urr_status']                = Config::get("status.recharge_status")['success'];
        $condition['urr_required_bet_amount']   = array('EXP', '<urr_traffic_amount');
        return $this->where($condition)->order('urr_createtime DESC')->select();
    }

    public function modifyLowBetAmount($slobrId, $amount){

        $sportsLowOddsBonusRecordModel = Loader::model('common/SportsLowOddsBonusRecord');

        $condition = [];
        $condition['slobr_id'] = $slobrId;

        $data = [];
        $data['slobr_require_bet_amount'] = $amount;
        return $sportsLowOddsBonusRecordModel->save($data,$condition);
    }

    public function modifyBetAmount($urrId, $amount){

        $condition = [];
        $condition['urr_id'] = $urrId;

        $data = [];
        $data['urr_required_bet_amount'] = $amount;
        return $this->save($data,$condition);
    }


    /**
     * 批量入款
     * @param $data
     * @return bool
     */
    public function doBatchSystemAdd($data){

        // 限制数量
        $count = count($data);
        if($count>1000){
            $this->errorcode = EC_AD_SYSTEM_BATCH_RECHARGE_USERS_EXCEED_LIMIT;
            return false;
        }

        //TODO 限制重复数据：添加多次的用户

        ini_set('max_execution_time',0);

        if(is_array($data)){
            $urrModel = Loader::model('UserRechargeRecord');

            $userIds = extract_array($data,'user_id');

            //是否首次入款
            $firstUserIds   = $this->extractUserids($data);
            if(!empty($firstUserIds)&&is_array($firstUserIds)){
                $firstInfos = $urrModel->getFirstInfos($firstUserIds);
                $firstInfos = collection($firstInfos)->toArray();
                $firstInfos = reindex_array($firstInfos,'user_id');
            }

            $rechargeRecord        = [];
            $rechargeAmountList   = [];

            foreach($data as $key=>$vo){

                $rechargeAmount    = 0;
                $rechargeDiscount  = 0;
                $totalAmount       = 0;

                if($vo['urr_amount']<=0 && $vo['urr_recharge_discount']<=0){
                    //第".($key+1)."行，存款金额或优惠金额为空
                    $this->errorcode = EC_AD_RECHARGE_AMOUNT_MUST_BE_GREATER_THAN_ZERO;
                    return false;
                }

                if($vo['urr_amount']>=100000000 || $vo['urr_recharge_discount']>=100000000){
                    $this->errorcode = EC_AD_RECHARGE_AMOUNT_TOO_BIG;
                    return false;
                }

                if(empty($vo['urr_operation_type'])){
                    $this->errorcode = EC_AD_SYSTEM_BATCH_RECHARGE_OPERATION_TYPE_EMPTY;
                    return false;
                }

                $rechargeAmount    = floatval($data[$key]['urr_amount']);
                $rechargeDiscount  = floatval($data[$key]['urr_recharge_discount']);
                $totalAmount = bcadd($rechargeAmount, $rechargeDiscount, 2);

                //TODO 金额、优惠金额，来校验操作类型

                $rechargeRecordTmp = [];
                $rechargeRecordTmp['urr_no']                  = generate_order_number();
                $rechargeRecordTmp['user_id']                 = $vo['user_id'];
                $rechargeRecordTmp['urr_recharge_account_id'] = 0;
                $rechargeRecordTmp['urr_type']                = Config::get("status.user_recharge_type")['system'];
                $rechargeRecordTmp['urr_operation_type']      = $vo['urr_operation_type'];
                $rechargeRecordTmp['urr_amount']              = $rechargeAmount;
                $rechargeRecordTmp['urr_recharge_discount']   = $rechargeDiscount;
                $rechargeRecordTmp['urr_total_amount']        = $totalAmount;
                $rechargeRecordTmp['urr_traffic_amount']      = !empty($vo['urr_traffic_amount'])?floatval($vo['urr_traffic_amount']):0;
                $rechargeRecordTmp['urr_required_bet_amount'] = 0;
                $rechargeRecordTmp['urr_operator_id']         = MEMBER_ID;
                $rechargeRecordTmp['urr_remark']              = isset($vo['urr_remark'])?$vo['urr_remark']:'';
                $rechargeRecordTmp['urr_createtime']          = current_datetime();
                $rechargeRecordTmp['urr_confirm_time']        = current_datetime();
                $rechargeRecordTmp['urr_client_ip']           = get_client_ip();
                $rechargeRecordTmp['urr_status']              = Config::get("status.recharge_status")['success'];
                //是否首充：人工操作-存款（类型）+ 操作金额>0
                if($rechargeAmount>0 && $vo['urr_operation_type']==Config::get('status.operation_type_in')['recharge'] ){
                    $rechargeRecordTmp['urr_is_first']        = empty($firstInfos[$vo['user_id']])?Config::get("status.recharge_is_first")['yes']:Config::get("status.recharge_is_first")['no'];
                }else{
                    $rechargeRecordTmp['urr_is_first']        = Config::get("status.recharge_is_first")['no'];
                }

                $rechargeAmountListTmp = [];
                $rechargeAmountListTmp['user_id']                 = $vo['user_id'];
                $rechargeAmountListTmp['total_amount']            = $totalAmount;
                $rechargeAmountListTmp['recharge_discount']       = $rechargeDiscount;
                $rechargeAmountListTmp['recharge_amount']         = $rechargeAmount;
                $rechargeAmountListTmp['operation_type']          = $vo['urr_operation_type'];

                $rechargeRecord[]      = $rechargeRecordTmp;
                $rechargeAmountList[] = $rechargeAmountListTmp;
            }

            if($userIds){
                $userIds = array_unique($userIds);
                $userInfos = Loader::model("UserExtend")->getInfosByRecharge($userIds);
                $userInfos = \collection($userInfos)->toArray();


                $userInfos            = reindex_array($userInfos,'user_id');
                $rechargeRecordUsers  = reindex_array($rechargeRecord,'user_id');

                $this->startTrans();

                $this->insertAll($rechargeRecord);
                $firstRechargeId = $this->where($rechargeRecord[0])->value('urr_id');

                if($this->getError()){
                    $this->rollback();
                    $this->errorcode = EC_AD_ADD_RECHARGE_SYSTEM_ERROR;
                    return false;
                }

                $_where['urr_id'] = array('egt',$firstRechargeId);
                $newUrr = $this->where($_where)->column('urr_id,user_id');

                //优化批量更新
                $rechargeAmountData = [];
                foreach($rechargeAmountList as $ue_key=>$ue_vo){
                    $user_info = $userInfos[$ue_vo['user_id']];
                    $rechargeAmountTmp = [];
                    $rechargeAmountTmp['user_id'] = $ue_vo['user_id'];
                    $rechargeAmountTmp['ue_account_balance'] = $user_info['ue_account_balance']+$ue_vo['total_amount'];
                    $rechargeAmountTmp['ue_discount_amount'] = $user_info['ue_discount_amount']+$ue_vo['recharge_discount'];

                    if($ue_vo['operation_type'] == Config::get("status.operation_type_in")['recharge']){
                        $rechargeAmountTmp['ue_recharge_amount'] = $user_info['ue_recharge_amount']+$ue_vo['recharge_amount'];
                        $rechargeAmountTmp['ue_recharge_count'] = $user_info['ue_recharge_count']+1;
                    }
                    $rechargeAmountData[] = $rechargeAmountTmp;
                }

                batch_update('ds_user_extend',$rechargeAmountData,'user_id');

                $accountRecord          = [];
                $discountAccountRecord  = [];
                foreach ($newUrr as $rechargeId=>$userId){

                    //发现ds_user的用户ds_user_extend表不存在
                    $ueAccountBalance = !empty($userInfos[$userId]['ue_account_balance'])?$userInfos[$userId]['ue_account_balance']:'0.000';

                    if(isset($rechargeRecordUsers[$userId])){
                        $rechargeUrrAmount      = $rechargeRecordUsers[$userId]['urr_amount'];
                        $rechargeUrrDiscount    = $rechargeRecordUsers[$userId]['urr_recharge_discount'];
                        $urrRemark              = $rechargeRecordUsers[$userId]['urr_remark'];
                    }else{
                        $rechargeUrrAmount      = 0;
                        $rechargeUrrDiscount    = 0;
                        $urrRemark              = '';
                    }

                    $rechargeAfterBalance = 0;
                    $rechargeAfterBalance = bcadd($ueAccountBalance, $rechargeUrrAmount, 3);

                    if($rechargeUrrAmount>0){
                        $accountRecordTmp = [];
                        $accountRecordTmp['user_id']              = $userId;
                        $accountRecordTmp['uar_source_id']        = $rechargeId;
                        $accountRecordTmp['uar_source_type']      = Config::get("status.user_account_record_source_type")['recharge'];
                        $accountRecordTmp['uar_transaction_type'] = Config::get("status.account_record_transaction_type")['artificial_in'];
                        $accountRecordTmp['uar_action_type']      = Config::get("status.account_transfer")['in'];
                        $accountRecordTmp['uar_amount']           = $rechargeUrrAmount;
                        $accountRecordTmp['uar_before_balance']   = $ueAccountBalance;
                        $accountRecordTmp['uar_after_balance']    = $rechargeAfterBalance;
                        $accountRecordTmp['uar_remark']           = $urrRemark;

                        $accountRecord[] = $accountRecordTmp;
                    }

                    if($rechargeUrrDiscount>0){
                        $discountAccountRecordTmp = [];
                        $discountAccountRecordTmp['user_id']              = $userId;
                        $discountAccountRecordTmp['uar_source_id']        = $rechargeId;
                        $discountAccountRecordTmp['uar_source_type']      = Config::get("status.user_account_record_source_type")['recharge'];
                        $discountAccountRecordTmp['uar_transaction_type'] = Config::get("status.account_record_transaction_type")['discount'];
                        $discountAccountRecordTmp['uar_action_type']      = Config::get("status.account_transfer")['in'];
                        $discountAccountRecordTmp['uar_amount']           = $rechargeUrrDiscount;
                        $discountAccountRecordTmp['uar_before_balance']   = $rechargeAfterBalance;
                        $discountAccountRecordTmp['uar_after_balance']    = bcadd($rechargeAfterBalance, $rechargeUrrDiscount, 3);
                        $discountAccountRecordTmp['uar_remark']           = '人工入款优惠';

                        $discountAccountRecord[]  = $discountAccountRecordTmp;
                    }
                }

                $uarModel = Loader::model("UserAccountRecord");

                if(!empty($accountRecord))
                    $uarModel->insertAll($accountRecord);

                if(!empty($discountAccountRecord)){
                    $uarModel->insertAll($discountAccountRecord);
                }

                if($this->getError()){
                    $this->rollback();
                    $this->errorcode = EC_AD_ADD_RECHARGE_SYSTEM_ERROR;
                    return false;

                }else{
                    // 优化行为日志-批量插入//优化为一条行为记录
                    $recordDetail = $rechargeRecord[0];
                    $recordDetail['action']            = 'doBatchSystemAdd';
                    $recordDetail['count']             = count($rechargeRecord);
                    $recordDetail['all_recharge']      = serialize($newUrr);

                    Loader::model('General', 'logic')->actionLog('add_money', 'UserRechargeRecord' ,$firstRechargeId , MEMBER_ID, json_encode($recordDetail));

                    $this->commit();

                    return true;
                }
            }

        }

    }



    private  function extractUserids($arr){

        $userIds = [];

        if(!empty($arr)&&is_array($arr)){
            foreach ($arr as $key=>$vo){
                if($vo['urr_amount']>0)
                    $userIds[] = $vo['user_id'];
            }
        }

        return array_unique($userIds);
    }

    /**
     * 确认出款,修改充值是否已出款状态
     * @param $data
     * @return bool
     */
    public function withdrawCheck($userId, $datetime){
        //修改大额
        $condition = [];
        $condition['user_id'] = $userId;
        $condition['urr_traffic_amount'] = array('exp', '<= urr_required_bet_amount');
        $condition['urr_createtime'] = array('elt', $datetime);

        $data = array();
        $data['urr_is_withdraw'] = Config::get('status.recharge_record_withdraw_status') ['yes'];

        $resultRecharge = $this->save($data,$condition);

        //修改小额
        $condition = [];
        $condition['slobr_user_id'] = $userId;
        $condition['slobr_traffic_amount'] = array('exp', '<= slobr_require_bet_amount');
        $condition['slobr_create_time'] = array('elt', $datetime);

        $data = array();
        $data['slobr_is_withdraw'] = Config::get('status.recharge_record_withdraw_status') ['yes'];

        $resultLow =  Loader::model('common/SportsLowOddsBonusRecord')->save($data,$condition);

        if($resultRecharge && $resultLow) {
            return true;
        }else {
            return false;
        }
    }


    /**
     * 清理优惠
     * @param $params
     */
    public function cleanDiscount($params){
        if(empty($params['urr_id'])){
            $this->errorcode = EC_FAILURE;
            return false;
        }

        $data = [];
        $data['urr_recharge_discount'] = '0.00';

        $condition = [];
        $condition['urr_id'] = $params['urr_id'];

        $actionData = Loader::model('General','logic')->getActionData($params['urr_id'],$data,'UserRechargeRecord');

        $result =  $this->save($data,$condition);

        if($result){
            //行为日志
            Loader::model('General', 'logic')->actionLog('company_recharge_clean_discoun', 'UserRechargeRecord', $params['urr_id'], MEMBER_ID, json_encode($actionData));

        }
        return $result;
    }

    /**
     * 打码量设置
     * @param $params
     */
    public function setTraffic($params){

        if(empty($params['urr_id'])){
            $this->errorcode = EC_FAILURE;
            return false;
        }

        $data = [];
        $data['urr_traffic_amount'] = $params['urr_traffic_amount'];

        $condition = [];
        $condition['urr_id'] = $params['urr_id'];

        $actionData = Loader::model('General','logic')->getActionData($params['urr_id'],$data,'UserRechargeRecord');

        $result =  $this->save($data,$condition);

        if($result){
            //行为日志
            Loader::model('General', 'logic')->actionLog('company_recharge_set_traffic', 'UserRechargeRecord', $params['urr_id'], MEMBER_ID, json_encode($actionData));
        }
        return $result;
    }



}