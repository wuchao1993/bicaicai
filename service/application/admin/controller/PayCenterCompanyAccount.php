<?php
namespace app\admin\controller;
use think\Loader;
use think\Request;
use think\Config;

class PayCenterCompanyAccount{

    /**
     * 公司付款账户列表
     */
    public function getBankAccountList(Request $request){
        $params = $request->post();
        $params['tag'] = $params['ulId'];
        unset($params['ulId']);
        $payCenter = Loader::model('PayCenterCompanyAccount', 'logic');
        $data = $payCenter->getBankAccountList($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 添加公司付款账户
     */
    public function addBankAccount(Request $request){
        $params = $request->post();
        $params['tag'] = $params['ulId'];
        unset($params['ulId']);
        $payCenter = Loader::model('PayCenterCompanyAccount', 'logic');
        $data = $payCenter->addBankAccount($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }


    /**
     * 编辑公司付款账户
     * @param Request $request
     * @return array
     */
    public function editBankAccount(Request $request){
        $params = $request->post();
        $params['tag'] = $params['ulId'];
        unset($params['ulId']);
        $payCenter = Loader::model('PayCenterCompanyAccount', 'logic');
        $data = $payCenter->editBankAccount($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }                 

    /**
     * 公司入款账户详情
     */
    public function getBankAccountDetail(Request $request){
        $params['id'] = $request->param('id');
        $payCenter = Loader::model('PayCenterCompanyAccount', 'logic');
        $data = $payCenter->getBankAccountDetail($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 删除公司入款账户
     */
    public function deleteBankAccount(Request $request){
        $params['id'] = $request->param('id');
        $payCenter = Loader::model('PayCenterCompanyAccount', 'logic');
        $data = $payCenter->deleteBankAccount($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 修改公司入款账户状态
     */
    public function changeBankAccountStatus(Request $request){
        $params['id'] = $request->param('id');
        $params['status'] = $request->param('status');
        $payCenter = Loader::model('PayCenterCompanyAccount', 'logic');
        $data = $payCenter->changeBankAccountStatus($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }

    /**
     * 银行列表
     */
    public function getBankAccountBankList(Request $request){
        $params = [];
        $payCenter = Loader::model('PayCenterCompanyAccount', 'logic');
        $data = $payCenter->getBankAccountBankList($params);

        return send_response($data, $payCenter->errorcode, $payCenter->errorMessage);
    }


}