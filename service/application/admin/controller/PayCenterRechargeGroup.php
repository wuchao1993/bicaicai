<?php
namespace app\admin\controller;

use think\Loader;
use think\Request;

class PayCenterRechargeGroup{

    /**
     * @param Request $request
     * @return array
     */
    public function getList(Request $request){
        $params['groupId'] = $request->param('groupId');
        $logic = Loader::model('PayCenterRechargeGroup', 'logic');
        $data = $logic->getList($params);

        return send_response($data, $logic->errorcode, $logic->errorMessage);
    }


    /**
     * 新增支付类型分组
     * @param Request $request
     * @return array
     */
    public function add(Request $request){
        $params = $request->post();
        $logic = Loader::model('PayCenterRechargeGroup', 'logic');
        $data = $logic->add($params);

        return send_response($data, $logic->errorcode, $logic->errorMessage);
    }


    /**
     * 修改支付类型分组
     * @param Request $request
     * @return array
     */
    public function edit(Request $request){
        $params = $request->post();
        $logic = Loader::model('PayCenterRechargeGroup', 'logic');
        $data = $logic->edit($params);

        return send_response($data, $logic->errorcode, $logic->errorMessage);
    }


    /**
     * 支付类型分组删除
     * @param Request $request
     * @return array
     */
    public function delete(Request $request){
        $params = $request->post();
        $logic = Loader::model('PayCenterRechargeGroup', 'logic');
        $data = $logic->delete($params);

        return send_response($data, $logic->errorcode, $logic->errorMessage);
    }


    /**
     * 修改支付类型分组状态
     * @param Request $request
     * @return array
     */
    public function changeStatus(Request $request){
        $params = $request->post();
        $logic = Loader::model('PayCenterRechargeGroup', 'logic');
        $data = $logic->changeStatus($params);

        return send_response($data, $logic->errorcode, $logic->errorMessage);
    }



}