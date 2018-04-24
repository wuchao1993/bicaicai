<?php
/**
 * 用户验证
 * @createTime 2017/3/22 11:04
 */

namespace app\api\controller;

use Filipac\Ip;
use think\Config;
use think\Loader;
use think\Request;

class VerifyUser {

    /**
     * 验证银行
     * @param Request $request
     * @return \think\response\Json
     */
    public function verifyBank(Request $request) {
        $token        = $request->header('Auth-Token');
        $bankAccount  = $request->post('bankAccount');
        $bankUserName = $request->post('bankUserName');

        $userVerifyLogic = Loader::model('VerifyUser', 'logic');
        $newToken = $userVerifyLogic->verifyBank($token, $bankAccount, $bankUserName);

        return json([
            'errorcode' => $userVerifyLogic->errorcode,
            'message'   => Config::get('errorcode')[$userVerifyLogic->errorcode],
            'data'      => [],
        ], 200, ['Auth-Token' => $newToken, 'Auth-Identity' => $GLOBALS['auth_identity']]);
    }

    /**
     * 修改密码
     * @param Request $request
     * @return array
     */
    public function updatePassword(Request $request) {
        $token         = $request->header('Auth-Token');
        $loginPassword = $request->post('loginPassword');
        $fundPassword  = $request->post('fundPassword');

        $userVerifyLogic= Loader::model('VerifyUser', 'logic');
        $userInfo = $userVerifyLogic->updatePassword($token, $loginPassword, $fundPassword);

        $token = $userInfo['token'];
        unset($userInfo['token']);

        return json([
            'errorcode' => $userVerifyLogic->errorcode,
            'message'   => Config::get('errorcode')[$userVerifyLogic->errorcode],
            'data'      => output_format($userInfo),
        ], 200, ['Auth-Token' => $token, 'Auth-Identity' => $GLOBALS['auth_identity']]);
    }
}
