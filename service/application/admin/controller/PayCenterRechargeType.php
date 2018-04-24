<?php
namespace app\admin\controller;
use think\Loader;
use think\Request;
use think\Config;

class PayCenterRechargeType{

    /**
     * @param Request $request
     * @return array
     */
    public function getList(Request $request){
        $params['groupId'] = $request->param('groupId');
        $logic = Loader::model('PayCenterRechargeType', 'logic');
        $data = $logic->getList($params);

        return send_response($data, $logic->errorcode, $logic->errorMessage);
    }


    /**
     * @param Request $request
     * @return array
     */
    public function updateGroup(Request $request){
        $params = $request->post();
        $logic = Loader::model('PayCenterRechargeType', 'logic');
        $data = $logic->updateGroup($params);

        return send_response($data, $logic->errorcode, $logic->errorMessage);
    }

}