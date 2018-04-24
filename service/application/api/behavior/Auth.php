<?php
/**
 * 入口权限验证，验证Token是否有效，是否过期，是否同时在线
 * @createTime 2017/3/30 14:59
 */

namespace app\api\behavior;

use jwt\Jwt;
use passport\Passport;
use think\Cache;
use think\Config;
use think\Controller;
use think\Loader;
use think\Request;
use think\Response;

class Auth extends Controller {

    /**
     * 验证Token
     * @throws \HttpResponseException
     */
    public function run() {
        //从header获取Token
        $request = Request::instance();
        $token   = $request->header('Auth-Token');

        if (!$token) {
            $data = ['errorcode' => EC_USER_NEED_TOKEN, 'message' => Config::get('errorcode')[EC_USER_NEED_TOKEN]];
            response_exception($data);
        }

        //验证token合法性
        $passport = new Passport();
        $result = $passport->setAccessToken($token)->authToken();
        if (!$result) {
            $data = ['errorcode' => $passport->getErrorCode(), 'message' => $passport->getErrorMessage()];
            response_exception($data);
        }

        //验证前端身份
//        $identity = $passport->getIdentityCategory();
//        $authIdentity = $request->header('Auth-Identity');
//        if ($authIdentity && $identity != $authIdentity) {
//            $data = ['errorcode' => EC_USER_IDENTITY_ERROR, 'message' => Config::get('errorcode')[EC_USER_IDENTITY_ERROR]];
//            response_exception($data);
//        }

        //如果用户不存在则创建
        $count = Loader::model('User')->where(['user_id' => $result])->count();
        if (!$count) {
            $userInfo = $passport->getUserInfo();
            $data = [
                'uid'       => $result,
                'user_name' => $userInfo['username'],
                'terminal'  => 'unknown',
            ];
            if (!Loader::model('User', 'logic')->createUser($data)) {
                $data = ['errorcode' => EC_USER_REG_FAILURE, 'message' => Config::get('errorcode')[EC_USER_REG_FAILURE]];
                response_exception($data);
            }
        }

        define('USER_ID', $result);
        define('USER_TOKEN', $token);
    }
}