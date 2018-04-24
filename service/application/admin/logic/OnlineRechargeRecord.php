<?php
namespace app\admin\logic;

use think\Model;
use think\Config;
use think\Loader;
use think\Log;

class OnlineRechargeRecord extends Model {

    public $errorcode = EC_AD_SUCCESS;

    public function getList($params){
        $condition = $this->_buildCondition($params);
        if($condition === false){
            return $this->_defaultResponse();
        }
        $forceIndex = $this->_getForceIndex($params['dateType']);
        $list = $this->_getList($condition, $params['ulIds'], $forceIndex, $params['page'], $params['num']);
        $response = $this->_buildResponse($list);
        $totals = $this->_getTotals($condition, $params['ulIds']);
        $response['totalCount'] = $totals['order_count'];
        $response['totals'] = $totals;

        return $response;
    }


    private function _defaultResponse(){
        return [
            'totalCount' => 0,
            'totals'     => 0,
            'subtotals'  => 0,
            'list'       => [],
        ];
    }


    private function _buildCondition($params){
        $condition = [];
        $condition ['urr_type'] = Config::get('status.user_recharge_type') ['online'];
        $dateCondition = $this->_buildDateCondition($params['dateType'], $params['startDate'], $params['endDate']);
        $amountCondition = $this->_buildAmountCondition($params['minAmount'], $params['maxAmount']);
        $accountValueCondition = $this->_buildAccountValueCondition($params['accountType'], $params['accountValue']);
        $condition = array_merge($condition, $dateCondition, $amountCondition, $accountValueCondition);
        if(!empty ($params ['urr_recharge_account_id'])) {
            $condition ['urr_recharge_account_id'] = ['IN', $params ['urr_recharge_account_id']];
        }
        if(isset ($params ['urr_status'])) {
            $condition ['urr_status'] = $params ['urr_status'];
        }

        return $condition;
    }

    private function _getForceIndex($dateType){
        return $dateType == 1 ? $forceIndex = "urr_createtime" : $forceIndex = "urr_confirm_time";
    }

    private function _buildDateCondition($dateType, $startDate, $endTime){
        $dateCondition = [];
        if(empty($startDate) || empty($endTime)){
            return $dateCondition;
        }

        if($dateType == 1) {
            $condition ['urr_createtime'] = [
                ['EGT', $startDate],
                ['ELT', $endTime],
            ];
        } else {
            $condition ['urr_confirm_time'] = [
                ['EGT', $startDate],
                ['ELT', $endTime],
            ];
        }
        return $condition;
    }


    private function _buildAmountCondition($minAmount, $maxAmount){
        $amountCondition = [];
        if($minAmount > 0 && $maxAmount > 0) {
            $amountCondition['urr_amount'] = [
                ['EGT', $minAmount],
                ['ELT', $maxAmount]
            ];
        } else if($minAmount > 0) {
            $amountCondition['urr_amount'] = [
                'EGT', $minAmount,
            ];
        } else if($maxAmount > 0) {
            $amountCondition['urr_amount'] = [
                'ELT', $maxAmount,
            ];
        }

        return $amountCondition;
    }


    private function _buildAccountValueCondition($accountType, $accountValue){
        $accountValueCondition = [];
        if(empty($accountType) || empty($accountValue)) {
            return $accountValueCondition;
        }
        switch($accountType) {
            case Config::get('status.recharge_search_type') ['user'] :
                $userId = Loader::model('User', 'logic')->getUserIdByUsername($accountValue);
                if(empty($userId)) {
                    return false;
                }
                $accountValueCondition['user_id'] = $userId;
                break;
            case Config::get('status.recharge_search_type') ['orderno'] :
                $accountValueCondition ['urr_no'] = $accountValue;
                break;
            case Config::get('status.recharge_search_type') ['operator'] :
                $operatorId = Loader::model('Member')->getUserIdByUsername($accountValue);
                if(!empty($operatorId)) {
                    $accountValueCondition['urr_operator_id'] = $operatorId;
                }else {
                    return false;
                }
                break;
        }

        return $accountValueCondition;
    }


    public function _getList($condition, $ulIds, $forceIndex, $page, $count){
        $userRechargeRecordModel = Loader::model('UserRechargeRecord');
        if(is_array($ulIds) && !empty($ulIds)) {
            $list = $userRechargeRecordModel->force($forceIndex)->where($condition)
                ->where('user_id','IN',function($query) use($ulIds)
                {$query->table('ds_user')->where(['ul_id'=>['IN', $ulIds]])->field('user_id');})
                ->order('urr_id desc')->limit($count)->page($page)->select();
        }else{
            $list = $userRechargeRecordModel->force($forceIndex)->where($condition)->order('urr_id desc')->limit($count)->page($page)->select();
        }

        return $list;
    }


    public function _getTotals($condition, $ulIds){
        $userRechargeRecordModel = Loader::model('UserRechargeRecord');
        $fields = [
            'count(*)' => 'order_count',
            'sum(urr_amount)' => 'amount',
            'sum(urr_recharge_discount)' => 'rechargeDiscount',
            'sum(urr_total_amount)' => 'totalAmount'
        ];
        if(is_array($ulIds) && !empty($ulIds)) {
            $totals = $userRechargeRecordModel->where($condition)->where('user_id','IN',function($query) use($ulIds){
                $query->table('ds_user')->where(['ul_id'=>['IN', $ulIds]])->field('user_id');
            })->field($fields)->find()->toArray();
        }else{
            $totals = $userRechargeRecordModel->where($condition)->field($fields)->find()->toArray();
        }

        return $totals;
    }


    public function _buildResponse($list){
        $payCenterBankList = Loader::model('PayCenterBank', 'logic')->getList();
        $payCenterBankList = reindex_array($payCenterBankList['list'], 'bankId');
        $payCenterMerchantList = Loader::model('PayCenterMerchant', 'logic')->getList(['page'=>1, 'size' => '200']);
        $payCenterMerchantList = reindex_array($payCenterMerchantList['list'], 'merchantId');
        $userIds = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name,ul_id,user_pid', 'user_id');
        $userPIds = extract_array($userList, 'user_pid');
        $userPList = Loader::model('User')->where(['user_id'=>['IN', $userPIds]])->column('user_name', 'user_id');
        $userULIds = extract_array($userList, 'ul_id');
        $userULList = Loader::model('UserLevel')->where(['ul_id'=>['IN', $userULIds]])->column('ul_name', 'ul_id');
        $operatorIds = array_unique(extract_array($list, 'urr_operator_id'));
        $operatorList = Loader::model('Member')->where(['uid'=>['IN', $operatorIds]])->column('nickname', 'uid');
        $subtotals = [];
        if(!empty ($list)) {
            foreach($list as &$val) {
                $subtotals['amount']           = bcadd(!empty($subtotals['amount'])?$subtotals['amount']:0, $val['urr_amount'], 2);
                $subtotals['rechargeDiscount'] = bcadd(!empty($subtotals['rechargeDiscount'])?$subtotals['rechargeDiscount']:0, $val['urr_recharge_discount'], 2);
                $subtotals['totalAmount']      = bcadd(!empty($subtotals['totalAmount'])?$subtotals['totalAmount']:0, $val['urr_total_amount'], 2);
                $val['user_name'] = $userList[$val['user_id']]['user_name'];
                $val['parent_user_name'] = !empty($userPList[ $userList[ $val['user_id'] ]['user_pid'] ])?$userPList[ $userList[ $val['user_id'] ]['user_pid'] ]:'';
                $val['bank_name'] = $payCenterBankList[$val['urr_recharge_bank_id']]['bankName'];
                $val['ul_name'] = $userULList[$userList[ $val['user_id'] ]['ul_id']];
                $val['operator_name'] = !empty($operatorList[$val['urr_operator_id']])?$operatorList[$val['urr_operator_id']]:'';
                $val['recharge_platform'] = $payCenterMerchantList[$val['urr_recharge_account_id']]['payType'];
                $val['pay_category_name'] = $payCenterMerchantList[$val['urr_recharge_account_id']]['payChannel'];
                //超过两个月的待支付状态要换成已关闭
                $expireDate = (time() - strtotime($val['urr_createtime'])) / 86400;
                if($val['urr_status'] == Config::get('status.recharge_status') ['wait'] && $expireDate > 60) {
                    $val['urr_status'] = Config::get('status.recharge_status') ['close'];
                }
            }
        }

        return [
          'list' => $list,
          'subtotals'  => $subtotals,
        ];
    }

}