<?php

/**
 * 公司入款控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Collection;
use think\Request;
use think\Loader;
use think\Config;
use curl\Curlrequest;

class CompanyRecharge
{

    /**
     * 获取入款列表
     *
     * @param Request $request
     * @return array
     */
    public function getList(Request $request)
    {
        $params ['page'] = $request->param('page',1);
        $params ['num'] = $request->param('num',10);

        if ($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }
        if ($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }
        if ($request->param('minAmount') != '') {
            $params ['min_amount'] = $request->param('minAmount');
        }
        if ($request->param('maxAmount') != '') {
            $params ['max_amount'] = $request->param('maxAmount');
        }
        if ($request->param('dateType') != '') {
            $params ['date_type'] = $request->param('dateType');
        }
        if ($request->param('accountType') != '') {
            $params ['account_type'] = $request->param('accountType');
        }
        if ($request->param('accountValue') != '') {
            $params ['account_value'] = $request->param('accountValue');
        }
        if (!empty($request->param('rechargeAccountId/a'))) {
            $params ['urr_recharge_account_id'] = $request->param('rechargeAccountId/a');
        }
        if ($request->param('status') != '') {
            $params ['urr_status'] = $request->param('status');
        }
        if (!empty($request->param('ulIds/a'))) {
            $params ['ul_ids'] = $request->param('ulIds/a');
        }

        $companyRechargeLogic = Loader::model('CompanyRecharge', 'logic');
        $companyList = $companyRechargeLogic->getList($params);

        foreach ($companyList ['list'] as &$info) {
            $info = $this->_packUserCompanyInfo($info);
        }

        return [
            'errorcode' => $companyRechargeLogic->errorcode,
            'message' => Config::get('errorcode') [$companyRechargeLogic->errorcode],
            'data' => $companyList
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

        if ($request->param('minAmount') != '') {
            $params ['min_amount'] = $request->param('minAmount');
        }
        if ($request->param('maxAmount') != '') {
            $params ['max_amount'] = $request->param('maxAmount');
        }
        if ($request->param('dateType') != '') {
            $params ['date_type'] = $request->param('dateType');
        }
        if ($request->param('accountType') != '') {
            $params ['account_type'] = $request->param('accountType');
        }
        if ($request->param('accountValue') != '') {
            $params ['account_value'] = $request->param('accountValue');
        }
        if (!empty($request->param('rechargeAccountId/a'))) {
            $params ['urr_recharge_account_id'] = $request->param('rechargeAccountId/a');
        }
        if ($request->param('status') != '') {
            $params ['urr_status'] = $request->param('status');
        }
        if (!empty($request->param('ulIds/a'))) {
            $params ['ul_ids'] = $request->param('ulIds/a');
        }

        $companyRechargeLogic = Loader::model('CompanyRecharge', 'logic');
        $companyList = $companyRechargeLogic->getList($params);

        $list = $companyList['list'] ? collection($companyList['list'])->toArray() : [];

        if(!empty($list)){
            $tmp = [];
            $data = [];
            foreach ($list as $item){
                $tmp['ul_name']         = $item['ul_name'];
                $tmp['user_name']       = $item['user_name'];
                $tmp['urr_recharge_bank']   = $item['urr_recharge_user_name'].'_'.$item['bank_name'];
                $tmp['parent_user_name'] = $item['parent_user_name'];
                $tmp['recharge_account_bank']     = $item['recharge_account_name'].'_'.$item['recharge_account_bank_name'];
                $tmp['recharge_amount']= $item['urr_amount'] . '(+'. $item['urr_recharge_discount'] . ')';
                $tmp['urr_createtime']   = $item ['urr_createtime'];
                $tmp['urr_recharge_time']   = $item ['urr_recharge_time'];
                $tmp['urr_confirm_time']   = $item ['urr_confirm_time'];
                $tmp['urr_remark']   = $item ['urr_remark'];
                $tmp['urr_reason']   = $item ['urr_reason'];
                $tmp['operator_name']      = $item['operator_name'];
                $tmp['urr_status']      = $item ['urr_status'] == 1 ? '充值成功' : ( $item ['urr_status'] == 0 ? '待处理' : '充值失败' ) ;
                $data[] = $tmp;
            }
        }

        $fileName = 'user_recharge_company_'.$startDate.'-'.$endDate;
        $title = ['会员层级','会员账号','会员存款银行账户','上级用户','存入账号','存入金额','系统提交时间','会员填写时间','确定时间','备注','取消理由','操作人','状态'];

        $localFilePath  = 'uploads' . DS . $fileName;
        Loader::model('ReportExcel', 'logic')->ExportList($data, $title, $localFilePath);
        $ossFileName = $localFilePath.'.xls';

        return [
            'errorcode' => $companyRechargeLogic->errorcode,
            'message'   => Config::get('errorcode') [$companyRechargeLogic->errorcode],
            'data'      => ['reportUrl' => $ossFileName],
        ];

    }

    /**
     * 公司入款确认
     *
     * @param Request $request
     * @return array
     */
    public function confirm(Request $request)
    {
        $params ['urr_id'] = $request->param('id');

        $companyRechargeLogic = Loader::model('CompanyRecharge', 'logic');
        $confirmInfo = $companyRechargeLogic->confirm($params);

        return [
            'errorcode' => $companyRechargeLogic->errorcode,
            'message' => Config::get('errorcode') [$companyRechargeLogic->errorcode],
            'data' => output_format($confirmInfo)
        ];
    }

    /**
     * 公司入款取消
     *
     * @param Request $request
     * @return array
     */
    public function cancel(Request $request)
    {
        $params['urr_id']      = $request->param('id');
        $params['urr_reason']  = $request->param('reason');

        $companyRechargeLogic = Loader::model('CompanyRecharge', 'logic');
        $confirmInfo = $companyRechargeLogic->cancel($params);

        return [
            'errorcode' => $companyRechargeLogic->errorcode,
            'message' => Config::get('errorcode') [$companyRechargeLogic->errorcode],
            'data' => output_format($confirmInfo)
        ];
    }

    /**
     * 公司入款恢复取消
     *
     * @param Request $request
     * @return array
     */
    public function reCancel(Request $request)
    {
        $params ['urr_id'] = $request->param('id');

        $companyRechargeLogic = Loader::model('CompanyRecharge', 'logic');
        $confirmInfo = $companyRechargeLogic->reCancel($params);

        return [
            'errorcode' => $companyRechargeLogic->errorcode,
            'message' => Config::get('errorcode') [$companyRechargeLogic->errorcode],
            'data' => output_format($confirmInfo)
        ];
    }


    private function _packUserCompanyInfo($info) {
        return [
            'id'                      => $info ['urr_id'],
            'ulName'                  => $info ['ul_name'],
            'no'                      => $info ['urr_no'],
            'uid'                     => $info ['user_id'],
            'username'                => $info ['user_name'],
            'userRealname'            => $info ['user_realname'],
            'parentUsername'          => $info ['parent_user_name'],
            'bankName'                => $info ['bank_name'],
            'detail'                  => $info ['urr_detail'],
            'rechargeUserName'        => $info ['urr_recharge_user_name'],
            'amount'                  => $info ['urr_amount'],
            'rechargeDiscount'        => $info ['urr_recharge_discount'],
            'totalAmount'             => $info ['urr_total_amount'],
            'rechargeAccountBankName' => $info ['recharge_account_bank_name'],
            'rechargeAccountName'     => $info ['recharge_account_name'],
            'rechargeTime'            => $info ['urr_recharge_time'],
            'createtime'              => $info ['urr_createtime'],
            'remark'                  => $info ['urr_remark'],
            'operatorName'            => $info ['operator_name'],
            'status'                  => $info ['urr_status'],
            'confirmTime'             => $info ['urr_confirm_time'],
            'reason'                  => $info ['urr_reason'],
            'userLargeAmount'         => $info['user_large_amount'],
            'transferType'            => Config::get('status.company_recharge_type') [$info ['urr_transfer_type']]
        ];
    }

}
