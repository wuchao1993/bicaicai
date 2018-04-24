<?php
/**
 * 用户模块控制器
 * @createTime 2017/3/30 16:35
 */

namespace app\api\controller;

use passport\Passport;
use think\Hook;
use think\Request;
use think\Loader;
use think\Config;
use think\Cache;

class User {

    /**
     * 正式账号登录
     * @param Request $request
     * @return array
     */
    public function signIn(Request $request) {
        $params['user_name'] = $request->post('userName');
        $params['password']  = $request->post('password');
        $params['terminal']  = $request->post('terminal', 'unknown');

        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->signIn($params);

        $token = $userInfo['token'];
        unset($userInfo['token']);

        return json([
            'errorcode' => $userLogic->errorcode,
            'message'   => $userLogic->message ?: Config::get('errorcode')[$userLogic->errorcode],
            'data'      => output_format($userInfo),
        ], 200, ['Auth-Token' => $token, 'Auth-Identity' => $userInfo['identity']]);
    }

    /**
     * 正式账号注册
     * @param Request $request
     * @return mixed
     */
    public function signUp(Request $request) {
        //注册都是正式库
        $identity = 'normal';
        Hook::listen('switch_identity', $identity);

        $params['user_name'] = $request->post('userName');
        $params['password']  = $request->post('password');
        $params['captcha']   = $request->post('captcha');
        $params['terminal']  = $request->post('terminal', 'unknown');
        $params['channel']   = $request->post('channel');
        $params['invitation_code']  = $request->post('invitationCode');
        $params['device_unique_id'] = $request->post('deviceUniqueId');

        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->signUp($params);

        $token = $userInfo['token'];
        unset($userInfo['token']);

        return json([
            'errorcode' => $userLogic->errorcode,
            'message'   => $userLogic->message ?: Config::get('errorcode')[$userLogic->errorcode],
            'data'      => output_format($userInfo),
        ], 200, ['Auth-Token' => $token, 'Auth-Identity' => $identity]);
    }

    /**
     * 免费试玩注册
     * @param Request $request
     * @return mixed
     */
    public function guestSignUp(Request $request) {
        $params['terminal'] = $request->post('terminal', 'mobile');
        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->guestSignUp($params);

        $token = $userInfo['token'];
        unset($userInfo['token']);

        return json([
            'errorcode' => $userLogic->errorcode,
            'message'   => $userLogic->message ?: Config::get('errorcode')[$userLogic->errorcode],
            'data'      => output_format($userInfo),
        ], 200, ['Auth-Token' => $token, 'Auth-Identity' => 'guest']);
    }

    /**
     * 特殊代理登录
     * @param Request $request
     * @return array
     */
    public function specialAgentSignIn(Request $request) {
        $params['user_name'] = $request->post('userName');
        $params['password']  = $request->post('password');
        $params['terminal']  = $request->post('terminal', 'unknown');
        $params['special']   = true;

        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->signIn($params);

        $token = $userInfo['token'];
        unset($userInfo['token']);

        return json([
            'errorcode' => $userLogic->errorcode,
            'message'   => $userLogic->message ?: Config::get('errorcode')[$userLogic->errorcode],
            'data'      => output_format($userInfo),
        ], 200, ['Auth-Token' => $token, 'Auth-Identity' => 'special']);
    }

    /**
     * 特殊代理注册下级账号
     * @param Request $request
     * @return array
     */
    public function specialAgentSignUp(Request $request) {
        Hook::listen('auth_check');
        $params['user_name'] = $request->post('userName');
        $params['password']  = $request->post('password');

        $userLogic = Loader::model('User', 'logic');
        $userLogic->specialAgentSignUp($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => $userLogic->message ?: Config::get('errorcode')[$userLogic->errorcode],
        ];
    }

    /**
     * 退出
     * @param Request $request
     * @return array
     */
    public function signOut(Request $request) {
        $passport = new Passport();
        $result = $passport->setAccessToken($request->header('Auth-Token'))->logout();
        if ($result) {
            return return_result(EC_SUCCESS);
        } else {
            return [
                'errorcode' => $passport->getErrorCode(),
                'message'   => $passport->getErrorMessage()
            ];
        }
    }

    /**
     * 判断用户名是否存在
     * @param Request $request
     * @return mixed
     */
    public function check(Request $request) {
        $userName = $request->post('userName');
        $userLogic = Loader::model('User', 'logic');
        $userLogic->checkUserByUserName($userName);
        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => $userLogic->message ?: Config::get('errorcode')[$userLogic->errorcode],
        ];
    }

    /**
     * 获取用户详细信息
     * @param Request $request
     * @return mixed
     */
    public function getInfo(Request $request) {
        $uid = $request->get('uid');
        $userLogic = Loader::model('User', 'logic');
        $userInfo = $userLogic->getInfoByUid($uid);
        $errorcode = $userInfo ? EC_SUCCESS : EC_USER_INFO_NONE;
        return [
            'errorcode' => $errorcode,
            'message'   => Config::get('errorcode')[$errorcode],
            'data'      => output_format($userInfo),
        ];
    }

    /**
     * 获取用户银行卡列表
     * @return mixed
     */
    public function getBanks() {
        $userLogic = Loader::model('User', 'logic');
        $bankList = $userLogic->getBanks();
        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode')[$userLogic->errorcode],
            'data' => $bankList,
        ];
    }

    /**
     * 第三方帐号登录
     * @param Request $request
     * @return array
     */
    public function thirdSignIn(Request $request) {
        $params['user_third_token'] = $request->post('openid');
        $params['user_third_type']  = $request->post('type');
        $params['user_nickname']    = $request->post('nickName');
        $params['terminal']         = $request->post('terminal', 'unknown');

        $userLogic = Loader::model('User', 'logic');
        $userInfo  = $userLogic->thirdSignIn($params);

        $token = $userInfo['token'];
        unset($userInfo['token']);

        return json([
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode')[$userLogic->errorcode],
            'data'      => output_format($userInfo),
        ], 200, ['Auth-Token' => $token, 'Auth-Identity' => $GLOBALS['auth_identity']]);
    }

    /**
     * 第三方帐号完善注册
     * @param Request $request
     * @return array
     */
    public function thirdSignUpImprove(Request $request) {
        $params['user_third_token'] = $request->post('openid');
        $params['user_third_type']  = $request->post('type');
        $params['user_name']        = $request->post('userName');
        $params['password']         = $request->post('password');
        $params['terminal']         = $request->post('terminal', 'unknown');
        $params['channel']          = $request->post('channel');
        $params['invitation_code']  = $request->post('invitationCode');
        $params['device_unique_id'] = $request->post('deviceUniqueId');

        $userLogic = Loader::model('User', 'logic');
        $result  = $userLogic->thirdSignUpImprove($params);

        return [
            'errorcode' => $userLogic->errorcode,
            'message'   => Config::get('errorcode')[$userLogic->errorcode],
            'data'      => $result,
        ];
    }
}
