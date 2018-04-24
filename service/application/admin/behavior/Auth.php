<?php

/**
 * 权限控制行为
 * @createTime 2017/3/22 11:04
 */
namespace app\admin\behavior;

use jwt\Jwt;
use think\Cache;
use think\Config;
use think\Controller;
use think\Request;
use think\Loader;

class Auth {
    
    /**
     * 验证Token
     */
    public function run() {
        // 排除不执行校验Toen的方法, 配置文件中配置
        $request = Request::instance ();
        $authAllow = Config::get ( 'common.admin_auth_allow' );
        $controller = $request->controller ();
        $action = $request->controller () . '/' . $request->action ();
        if (in_array ( $controller, $authAllow ['controller'] ) || in_array ( $action, $authAllow ['action'] )) {
            return;
        }

        // 从header获取Token和cookie
        $request = Request::instance ();
        $token = $request->header ( 'auth-token' );
        $cookie = $request->header ( 'cookie' );
        
        // 验证token合法性
        $tokenInfo = Jwt::decode ( $token, Config::get ( 'token_sign_key' ) );
        if (false === $tokenInfo) {
            $data = [
                    'errorcode' => EC_AD_TOKEN_ERROR,
                    'message' => Config::get ( 'errorcode' ) [EC_AD_TOKEN_ERROR]
            ];
            json ( $data, 200, get_cross_headers () )->send ();
            exit ();
        }

        $twoAuth = Cache::tag('member')->get(Config::get('token_cache_key') . $tokenInfo->uid. '_twoAuth');
        if($twoAuth == TWO_FACTOR_ENABLE){
            $data = [
                'errorcode' => EC_USER_NEED_TWO_FACTOR,
                'message' => Config::get ( 'errorcode' ) [EC_USER_NEED_TWO_FACTOR]
            ];
            json ( $data, 200, get_cross_headers () )->send ();
            exit ();
        }
        
        // 验证是否被人踢下线或者token过期
        // redis里存每个uid对应token的创建时间，如果redis里的创建时间大于token里面的过期时间，则自动退出,如果cookie不一样，则认为是不同用户，强制退出
        $tokenExpireTime = Cache::tag ( 'member' )->get ( Config::get ( 'token_cache_key' ) . $tokenInfo->uid . '_expire' );
        $tokenCookie = Cache::tag ( 'member' )->get ( Config::get ( 'token_cache_key' ) . $tokenInfo->uid );

        if (empty ( $tokenExpireTime ) || time() > $tokenExpireTime || empty ( $tokenCookie ) || $cookie != $tokenCookie) {
            $data = [
                    'errorcode' => EC_AD_OTHER_LOGIN,
                    'message' => Config::get ( 'errorcode' ) [EC_AD_OTHER_LOGIN]
            ];
            json ( $data, 200, get_cross_headers () )->send ();
            exit ();
        }

        // 校验是否有权限访问
        $generalLogic = Loader::model ( 'General', 'logic', '', 'admin' );
        $isAccess = $generalLogic->checkAccess ( $tokenInfo->uid, $action );
        if ($isAccess == false) {
            $data = [
                    'errorcode' => EC_AD_ACCESS_FAILED,
                    'message' => Config::get ( 'errorcode' ) [EC_AD_ACCESS_FAILED]
            ];
            json ( $data, 200, get_cross_headers () )->send ();
            exit ();
        }

        // 验证通过，记录UID
        define ( 'MEMBER_ID', $tokenInfo->uid );
        define ( 'MEMBER_NAME', $tokenInfo->nickname );

        // 刷新token
        $memberInfo           = [
            'uid'      => $tokenInfo->uid,
            'nickname' => $tokenInfo->nickname,
            'cookie'   => $cookie,
        ];
        Loader::model ( 'Member', 'logic')->generateToken($memberInfo);
    }
}