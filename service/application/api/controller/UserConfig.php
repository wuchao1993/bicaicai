<?php

namespace app\api\controller;

use think\Request;
use think\Config;
use think\Loader;
use think\Hook;

class UserConfig {

    public function __construct() {
        Hook::listen('auth_check');
    }

    /**
     * 绑定用户真实信息
     * @param Request $request
     * @return mixed
     */
    public function bindRealInfo(Request $request) {
        $realName      = $request->param('realName');
        $fundsPassword = $request->param('fundsPassword');

        $userConfigLogic = Loader::model('UserConfig', 'logic');
        $userConfigLogic->bindRealInfo($realName, $fundsPassword);

        return [
            'errorcode' => $userConfigLogic->errorcode,
            'message'   => Config::get('errorcode')[$userConfigLogic->errorcode],
        ];

    }


    /**
     * 绑定提现银行卡
     * @param Request $request
     * @return mixed
     */
    public function bindCard(Request $request) {

        $params = $request->param();

        $userConfigLogic = Loader::model('userConfig', 'logic');

        $data = $userConfigLogic->bindCard($params);

        return [
            'errorcode' => $userConfigLogic->errorcode,
            'message'   => Config::get('errorcode')[$userConfigLogic->errorcode],
        ];
    }

}