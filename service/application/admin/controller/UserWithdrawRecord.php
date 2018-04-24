<?php

/**
 * 用户出款控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Model;
use think\Request;
use think\Loader;
use think\Config;

class UserWithdrawRecord {

    /**
     * 获取人工出款列表
     *
     * @param Request $request
     * @return array
     */
    public function getSystemList(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);

        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }
        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }
        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if($request->param('operationType') != '') {
            $params ['uwr_operation_type'] = $request->param('operationType');
        }

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $systemList              = $userWithdrawRecordLogic->getSystemList($params);
        foreach($systemList ['list'] as &$info) {
            $info = $this->_packUserSystemInfo($info);
        }

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => $systemList,
        ];
    }

    /**
     * 新增人工出款
     *
     * @param Request $request
     * @return array
     */
    public function addSystem(Request $request) {
        $params ['user_name']          = $request->param('username');
        $params ['uwr_apply_amount']   = $request->param('applyAmount',0);
        $params ['uwr_traffic_amount'] = $request->param('trafficAmount', 0);
        $params ['uwr_operation_type'] = $request->param('operationType', 1);
        $params ['uwr_remark']         = $request->param('remark', '');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $systemInfo              = $userWithdrawRecordLogic->addSystem($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($systemInfo),
        ];
    }

    /**
     * 获取注单状态
     *
     * @param Request $request
     * @return array
     */
    public function getSystemTypeList(Request $request) {
        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');

        $data = [];
        foreach(Config::get('status.user_withdraw_system_name') as $key => $val) {
            $data [$key - 1] = [
                'id'    => $key,
                'value' => $val,
            ];
        }

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    /**
     * 获取在线出款列表
     *
     * @param Request $request
     * @return array
     */
    public function getOnlineList(Request $request) {
        $params ['page'] = $request->param('page', 1);
        $params ['num']  = $request->param('num', 10);

        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }
        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }
        if($request->param('minAmount') != '') {
            $params ['min_amount'] = $request->param('minAmount');
        }
        if($request->param('maxAmount') != '') {
            $params ['max_amount'] = $request->param('maxAmount');
        }
        if($request->param('minOutAmount') != '') {
            $params ['min_out_amount'] = $request->param('minOutAmount');
        }
        if($request->param('maxOutAmount') != '') {
            $params ['max_out_amount'] = $request->param('maxOutAmount');
        }
        if($request->param('dateType') != '') {
            $params ['date_type'] = $request->param('dateType');
        }
        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }
        if($request->param('status/a') != '') {
            $params ['uwr_status'] = $request->param('status/a');
        }
        if($request->param('operatorName') != '') {
            $params ['operator_name'] = $request->param('operatorName');
        }
        //是否查询首出
        $isFirst = '';
        if ( $request->param('isFirst') != '' ) {
           $isFirst = (int)$request->param('isFirst');
        }
        $params['user_level'] = $request->param('userLevel');
        $params['isFirst']    = $isFirst;

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineList              = $userWithdrawRecordLogic->getOnlineList($params);

        $userIds = [];
        foreach($onlineList ['list'] as $item) {
            $userIds[] = $item['user_id'];
        }

        foreach($onlineList ['list'] as &$info) {
           $info = $this->_packUserOnlineInfo($info);
        }

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => $onlineList,
        ];
    }

    /**
     * 导出execl
     * @param Request $request
     */
    public function exportExcel(Request $request){

        $startDate       = $request->param('startDate');
        $endDate         = $request->param('endDate');

        if(count_days($startDate, $endDate) > 31) {
            return [
                'errorcode' => BC_AD_WITHDRAW_EXPORT_EXCEL_DAY_LIMIT_ERROR,
                'message'   => Config::get('errorcode') [BC_AD_WITHDRAW_EXPORT_EXCEL_DAY_LIMIT_ERROR]
            ];
        }


        $params ['start_date'] = !empty($startDate)?date('Y-m-d 00:00:00', strtotime($request->param('startDate'))):date('Y-m-d 00:00:00');
        $params ['end_date']   = !empty($endDate)?date('Y-m-d 23:59:59', strtotime($request->param('endDate'))):date('Y-m-d 23:59:59');

        $request->param('minAmount') != '' && $params ['min_amount'] = $request->param('minAmount');
        $request->param('maxAmount') != '' && $params ['max_amount'] = $request->param('maxAmount');
        $request->param('minOutAmount') != '' && $params ['min_out_amount'] = $request->param('minOutAmount');
        $request->param('maxOutAmount') != '' && $params ['max_out_amount'] = $request->param('maxOutAmount');
        $request->param('dateType') != '' && $params ['date_type'] = $request->param('dateType');
        $request->param('username') != '' && $params ['user_name'] = $request->param('username');
        $request->param('status/a') != '' && $params ['uwr_status'] = $request->param('status/a');
        $request->param('operatorName') != '' && $params ['operator_name'] = $request->param('operatorName');

        $params['user_level'] = $request->param('userLevel');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineList              = $userWithdrawRecordLogic->getOnlineList($params);
        //获取银行用户关系表
        $userBankRelation = Loader::model('UserBankRelation', 'logic');
        $userBanksList = $userBankRelation->getAllBankRelations();
        if(!empty($userBanksList)){
            $newUserBankList = reindex_array(collection($userBanksList)->toArray(),'ub_id');
        }else{
            $newUserBankList = array();
        }

        $list = $onlineList['list'] ? collection($onlineList['list'])->toArray() : [];

        if(!empty($list)){
            $tmp = [];
            $data = [];
            foreach ($list as $item){
                $tmp['ul_name']         = $item['ul_name'];
                $tmp['user_name']       = $item['user_name'];
                $tmp['user_realname']   = $item['user_realname'];
                $oldBankAccount         = !empty($newUserBankList)?!empty($newUserBankList[$item['ub_id']]['ub_bank_account'])?strval($newUserBankList[$item['ub_id']]['ub_bank_account'])."　":"已被删除":'';
                $bankName         = !empty($newUserBankList)?!empty($newUserBankList[$item['ub_id']]['bank_name'])?$newUserBankList[$item['ub_id']]['bank_name'] ."　":"已被删除":'';
                $bankAddress         = !empty($newUserBankList)?!empty($newUserBankList[$item['ub_id']]['ub_address'])?$newUserBankList[$item['ub_id']]['ub_address'] ."　":"已被删除":'';
                $tmp['bank_account']       = !empty($item['uwr_bank_account'])?$item['uwr_bank_account']." ":$oldBankAccount;
                $tmp['bank_name']       = $bankName;
                $tmp['bank_address']    = $bankAddress;
                $tmp['apply_amount']    = $item['uwr_apply_amount'].($item['uwr_is_first'] > 0 ? '首出':'');
                $tmp['handling_charge'] = $item['uwr_handling_charge'];
                $tmp['real_amount']     = $item['uwr_real_amount'];
                $tmp['parent_user_name']= $item['parent_user_name'];
                $tmp['touser_remark']   = $item ['uwr_touser_remark'];
                $tmp['status']          = Config::get('status.withdraw_status_name') [$item['uwr_status']];
                $tmp['operator_name']   = $item ['operator_name'];
                $tmp['remark']          = !empty($item ['uwr_remark'])?str_replace('=','-',$item ['uwr_remark']):'';
                $tmp['createtime']      = $item['uwr_createtime'];
                $tmp['modifytime']      = $item ['uwr_modifytime'];
                $tmp['confirmtime']     = $item ['uwr_confirmtime'];
                $tmp['uwr_create_ip']   = $item['uwr_create_ip'];
                $data[] = $tmp;
            }
        }

        $fileName = 'user_withdraw_'.$startDate.'-'.$endDate;
        $title = ['会员层级','会员账号','真实姓名','银行卡号','银行名称','开户行','申请金额','手续费','出款金额','上级用户','拒绝理由','状态','操作人','备注','提交时间','异动时间','确定时间','提交IP'];

        $localFilePath  = 'uploads' . DS . $fileName;
        Loader::model('ReportExcel', 'logic')->ExportList($data, $title, $localFilePath);
        $ossFileName = $localFilePath.'.xls';

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => ['reportUrl' => $ossFileName],
        ];

    }

    /**
     * 新增线上出款备注
     *
     * @param Request $request
     * @return array
     */
    public function addOnlineRemark(Request $request) {
        $params ['uwr_id']     = $request->param('id');
        $params ['uwr_remark'] = $request->param('remark', '');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->addOnlineRemark($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }

    /**
     * 锁定线上出款
     *
     * @param Request $request
     * @return array
     */
    public function lockOnline(Request $request) {
        $params ['uwr_id'] = $request->param('id');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->lockOnline($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }

    /**
     * 解锁线上出款
     *
     * @param Request $request
     * @return array
     */
    public function unlockOnline(Request $request) {
        $params ['uwr_id'] = $request->param('id');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->unlockOnline($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }

    /**
     * 确认线上出款
     *
     * @param Request $request
     * @return array
     */
    public function confirmOnline(Request $request) {
        $params ['uwr_id'] = $request->param('id');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->confirmOnline($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }
    /**
     * 代付线上出款
     *
     * @param Request $request
     * @return array
     */
    public function goToPay(Request $request) {
        $params ['uwr_id'] = $request->param('id');
        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->goToPayOnline($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => $onlineInfo,
        ];
    }

    /**
     * 取消线上出款
     *
     * @param Request $request
     * @return array
     */
    public function refuseAndCancelOnline(Request $request) {
        $params ['id']     = $request->param('id');
        $params ['type']   = $request->param('type');
        $params ['remark'] = $request->param('remark');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->refuseAndCancelOnline($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }

    /**
     * 取消代付出款
     *
     * @param Request $request
     * @return array
     */
    public function cancelPaymentOnline(Request $request) {
        $params ['id']     = $request->param('id');
        $params ['type']   = $request->param('type');
        $params ['remark'] = $request->param('remark');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->cancelPayment($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }

    /**
     * 即时检查
     *
     * @param Request $request
     * @return array
     */
    public function currentCheck(Request $request) {
        $params ['user_name'] = $request->param('username');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->currentCheck($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }

    /**
     * 即时检查弹出框
     *
     * @param Request $request
     * @return array
     */
    public function currentCheckBox(Request $request) {
        $params ['user_id'] = $request->param('uid');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->currentCheckBox($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }

    /**
     * 即时检查(小额)
     *
     * @param Request $request
     * @return array
     */
    public function currentCheckLow(Request $request) {
        $params ['user_name'] = $request->param('username');

        $userWithdrawRecordLogic = Loader::model('UserWithdrawRecord', 'logic');
        $onlineInfo              = $userWithdrawRecordLogic->currentCheckLow($params);

        return [
            'errorcode' => $userWithdrawRecordLogic->errorcode,
            'message'   => Config::get('errorcode') [$userWithdrawRecordLogic->errorcode],
            'data'      => output_format($onlineInfo),
        ];
    }

    private function _packUserSystemInfo($info) {
        return [
            'no'                => $info ['uwr_no'],
            'username'          => $info ['user_name'],
            'operationTypeName' => Config::get('status.user_withdraw_system_name') [$info ['uwr_operation_type']],
            'applyAmount'       => $info ['uwr_apply_amount'],
            'trafficAmount'     => $info ['uwr_traffic_amount'],
            'createtime'        => $info ['uwr_createtime'],
            'remark'            => $info ['uwr_remark'],
            'operatorName'      => $info ['operator_name'],
        ];
    }

    private function _packUserOnlineInfo($info) {
        $userId = $info ['user_id'];
        if(!empty($info['uwr_is_first'])){
            $isFirst = true;
        }else{
            $isFirst = false;
        }
        return [
            'id'             => $info ['uwr_id'],
            'uid'            => $userId,
            'ulName'         => $info ['ul_name'],
            'username'       => $info ['user_name'],
            'userRealname'   => $info ['user_realname'],
            'parentUsername' => $info ['parent_user_name'],
            'applyAmount'    => $info ['uwr_apply_amount'],
            'discountAmount' => $info ['uwr_discount_amount'],
            'handlingCharge' => $info ['uwr_handling_charge'],
            'realAmount'     => $info ['uwr_real_amount'],
            'ubId'           => $info ['ub_id'],
            'createtime'     => $info ['uwr_createtime'],
            'status'         => Config::get('status.withdraw_status_name') [$info ['uwr_status']],
            'remark'         => $info ['uwr_remark'],
            'isFirst'        => $isFirst,
            'operatorName'   => $info ['operator_name'],
            'modifytime'     => $info ['uwr_modifytime'],
            'confirmtime'    => $info ['uwr_confirmtime'],
            'isMyself'       => ($info ['uwr_operator_id'] === MEMBER_ID) ? 1 : 0,
            'touserRemark'   => $info ['uwr_touser_remark'],
            'createIp'       => $info ['uwr_create_ip'],
            'ppid'           => $info ['uwr_pp_id'],
            'ispayment'      => $info ['uwr_is_payment'],
        ];
    }
}
