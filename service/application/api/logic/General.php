<?php
/**
 * 业务逻辑
 * @createTime 2017/4/1 17:10
 */

namespace app\api\logic;

use think\Loader;
use jwt\Jwt;
use think\Config;
use think\Cache;
use think\Model;

class General extends Model{

    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 刷新token，延长token过期时间
     * @param $token
     * @return bool
     */
    public function refreshToken($token) {
        //验证token合法性
        $tokenInfo = Jwt::decode($token, Config::get('token_sign_key'));
        if(false === $tokenInfo) {
            $this->errorcode = Jwt::$errorcode;
            return false;
        }

        //验证是否被人踢下线
        //redis里存每个uid对应token的创建时间，如果redis里的创建时间和当前Token里的创建时间不一样，说明有新的token生成
        $tokenCreateTime = Cache::get(Config::get('common.token_cache_key') . $tokenInfo->uid);
        if ($tokenCreateTime != $tokenInfo->iat) {
            $this->errorcode = EC_USER_OTHER_LOGIN;
            return false;
        }

        //生成新的token
        $userInfo = [
            'uid' => $tokenInfo->uid,
            'user_name' => $tokenInfo->user_name,
        ];
        return Loader::model('User', 'logic')->generateToken($userInfo);
    }
}