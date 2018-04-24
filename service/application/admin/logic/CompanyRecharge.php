<?php

/**
 * 公司入款相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use Filipac\Ip;
use think\Collection;
use think\Config;
use think\Loader;
use think\Log;
use think\Model;

class CompanyRecharge extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取入款列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {

        //根据参数组合条件
        $condition = $this->_buildCondition($params);
        if(!$condition) {
            return $this->_defaultResponse();
        }

        //根据条件获取入款列表
        $list = $this->_getListByParams($params, $condition);

        //根据列表获取返回结果
        $response = $this->_buildResponse($list, $params['end_date']);

        //总计
        $totals = $this->_getTotals($condition, $params['ul_ids']);
        $response['allAmount'] = $totals['allAmount'];
        $response['allTotalAmount'] = $totals['allTotalAmount'];
        $response['allDiscountAmount'] = $totals['allDiscountAmount'];

        return $response;
    }

    private function _countLargeAmountUser($userList,$userULIds){

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

        $list = Loader::model('UserRechargeRecord')->where($condition)->group("user_id")->field("user_id,sum(urr_amount) as total_recharge")->select();
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
     * 入款确认
     *
     * @param
     *            $params
     * @return bool
     */
    public function confirm($params) {
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

        //确认
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

            //调用充值中心更新入款信息
            $params['id'] = $info['urr_recharge_account_id'];
            $params['rechargeAmount'] = $recharge_amount;
            $res = Loader::model('PayCenterCompanyAccount', 'logic')->editBankAccount($params);
            if($res ===  false){
                $this->errorcode = EC_AD_RECHARGE_COMPANY_CONFIRM_ERROR;
                $this->rollback();
                return false;
            }

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
     * 入款取消
     *
     * @param
     *            $params
     * @return bool
     */
    public function cancel($params) {

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
     * 入款恢复取消
     *
     * @param
     *            $params
     * @return bool
     */
    public function reCancel($params) {
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

    private function _defaultResponse(){
        return [
            'totalCount'     => 0,
            'subAmount'      => 0,
            'subTotalAmount' => 0,
            'allAmount'      => 0,
            'allTotalAmount' => 0,
            'allDiscountAmount' => 0,
            'list'           => [],
        ];
    }

    private function _buildCondition($params) {
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
                        return false;
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
                        return false;
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

        return $condition;
    }

    private function _getListByParams($params, $condition) {

        $userRechargeRecordModel = Loader::model('UserRechargeRecord');

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

        return $list;
    }

    private function _buildResponse($list, $endDate) {

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

        //今日大额提醒 //TODO 影响查询速度，任务跑统计数据
        $userLarge = [];
        if($endDate>date("Y-m-d 00:00:00")){
            $userLarge = $this->_countLargeAmountUser($userList,$userULIds);
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

                //调用充值中心获取入款帐号信息
                $companyParams['id'] = $val['urr_recharge_account_id'];
                $companyAccountInfo = Loader::model('PayCenterCompanyAccount', 'logic')->getBankAccountDetail($companyParams);
                if(!empty($companyAccountInfo)) {
                    $val['recharge_account_bank_name'] = $companyAccountInfo['bank'];
                    $val['recharge_account_name'] = $companyAccountInfo['realName'];
                }else {
                    $val['recharge_account_bank_name'] = '';
                    $val['recharge_account_name'] = '';
                }

                $val['user_large_amount'] = isset($userLarge[$val['user_id']])?$userLarge[$val['user_id']]:'';
            }
        }

        return [
            'list' => $list,
            'subAmount'  => sprintf("%.2f",$subAmount),
            'subTotalAmount'    => sprintf("%.2f",$subTotalAmount),
        ];
    }

    private function _getTotals($condition, $ulIds) {
        $userRechargeRecordModel = Loader::model('UserRechargeRecord');
        if(isset ($ulIds) && !empty ($ulIds)) {
            $totalAmountList = $userRechargeRecordModel->field('count(*) as order_count,sum(urr_amount) as urr_amount, sum(urr_total_amount) as urr_total_amount, sum(urr_recharge_discount) as urr_total_recharge_discount')->where($condition)
                ->where('user_id','IN',function($query) use($ulIds){$query->table('ds_user')->where(['ul_id'=>['IN', $ulIds]])->field('user_id');})->find();
        }else{
            $totalAmountList = $userRechargeRecordModel->field('count(*) as order_count,sum(urr_amount) as urr_amount, sum(urr_total_amount) as urr_total_amount, sum(urr_recharge_discount) as urr_total_recharge_discount')->where($condition)->find();
        }
        $allAmount      = $totalAmountList['urr_amount'];
        $allTotalAmount = $totalAmountList['urr_total_amount'];
        $allDiscountAmount = $totalAmountList['urr_total_recharge_discount'];
        $count          = $totalAmountList['order_count'];

        return [
            'totalCount'     => $count,
            'allAmount'      => sprintf("%.2f",$allAmount),
            'allTotalAmount' => sprintf("%.2f",$allTotalAmount),
            'allDiscountAmount' => sprintf("%.2f",$allDiscountAmount),
        ];
    }
}