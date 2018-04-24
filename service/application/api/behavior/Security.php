<?php
/**
 * 入口安全控制，签名验证，重放攻击防御
 * @createTime 2017/3/22 11:04
 */

namespace app\api\behavior;

use Filipac\Ip;
use think\Cache;
use think\Config;
use think\Controller;
use think\Log;
use think\Request;
use think\Response;
use think\Hook;
use think\exception\HttpResponseException;

class Security extends Controller {

    /**
     * 验证签名以及重放攻击检测
     */
    public function run() {
        //排除不执行的方法, 配置文件中配置
        $request = Request::instance();
        $authAllow = Config::get('common.security_allow');
        $controller = $request->controller();
        $action = $request->controller() . '/' . $request->action();
        $type = $this->getResponseType();
        if (in_array($controller, $authAllow['controller']) || in_array($action, $authAllow['action'])) {
            return;
        }
        $params = $request->param();
        $requestSign = $request->header('Sign');

        //是否需要验证签名
        $ip = Ip::get();
        if (Config::get('sign_check') && !empty($params) && !in_array($ip, Config::get('sign_uncheck_ip'))) {
            $linkString = build_link_string($params);
            $signKey = Config::get('sign_key');
            $sign = md5($linkString . $signKey);
            Log::record('linkString:' . $linkString . ' | signKey:' . $signKey . ' | sign:' . $sign . ' | requestSign:' . $requestSign, APP_LOG_TYPE);
            if ($requestSign !== $sign) {
                $data = ['errorcode' => EC_SIGN_ERROR, 'message' => Config::get('errorcode')[EC_SIGN_ERROR]];
                $response = Response::create($data, $type);
                Hook::listen('Cross', $response);
                throw new HttpResponseException($response);
            }
        }

        //重放攻击验证，timestamp和nonce不为空的时候才验证
        //验证timestamp, 一分钟内有效
        $validTime = 60;
        if (isset($params['timestamp']) && $params['timestamp'] > 0 && time() - $params['timestamp'] > $validTime) {
            $data = ['errorcode' => EC_API_TIMEOUT, 'message' => Config::get('errorcode')[EC_API_TIMEOUT]];
            $response = Response::create($data, $type);
            Hook::listen('Cross', $response);
            throw new HttpResponseException($response);
        }

        //验证nonce是否存在
        if (isset($params['nonce']) && !empty($params['nonce'])) {
            $nonceCacheKey = Config::get('cache_option.prefix')['sports_api'] . 'replay_attacks_nonce_' . $params['nonce'];
            $nonce = Cache::get($nonceCacheKey);
            if ($nonce) {
                $data = ['errorcode' => EC_NONCE_USED, 'message' => Config::get('errorcode')[EC_NONCE_USED]];
                $response = Response::create($data, $type);
                Hook::listen('Cross', $response);
                throw new HttpResponseException($response);
            }
            Cache::set($nonceCacheKey, $params['nonce'], $validTime);
        }
    }

}