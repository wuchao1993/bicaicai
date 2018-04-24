<?php
/**
 * 公共接口
 * @createTime 2017/3/22 11:04
 */

namespace app\api\controller;

use Filipac\Ip;
use think\Cache;
use think\Config;
use think\Hook;
use think\Loader;
use think\Request;

class General {

    /**
     * 返回当前服务器时间戳
     * @return array
     */
    public function getTimestamp() {
        return return_result(EC_SUCCESS, ['timestamp' => time()]);
    }

    /**
     * 返回ip白名单
     * @return mixed
     */
    public function getIpWhiteList() {
        $ips = Loader::model('Config')->getConfig('SHOW_ALL_IPS');
        return return_result(EC_SUCCESS, $ips);
    }

    /**
     * 判断是否在白名单里
     * @return array
     */
    public function checkIpWhiteList() {
        $ip = Ip::get();
        $ips = Loader::model('Config')->getConfig('SHOW_ALL_IPS');
        if (is_array($ips) && in_array($ip, $ips)) {
            $data['in_white_list'] = true;
        } else {
            $data['in_white_list'] = false;
        }
        return return_result(EC_SUCCESS, output_format($data));
    }

    /**
     * 系统维护状态
     * @return array
     */
    public function checkSystemStatus()
    {
        //获取维护状态
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'collect_status';
        $collectStatusInfo = Cache::get($cacheKey);
        if ($collectStatusInfo && $collectStatusInfo['status'] == false) {
            $response = [];
            if($collectStatusInfo['startTime'] && $collectStatusInfo['endTime']){
                $response = [
                    'startTime' => $collectStatusInfo['startTime'],
                    'endTime' => $collectStatusInfo['endTime'],
                ];
            }
            return return_result(COLLECT_SYSTEM_MAINTENANCE, $response);
        }
        return return_result(EC_SUCCESS);
    }

    /**
     * 刷新Token
     * @param Request $request
     * @return array
     */
    public function refreshToken(Request $request) {
        $token = $request->header('Auth-Token');
        $generalLogic = Loader::model('General', 'logic');
        $token = $generalLogic->refreshToken($token);
        if (false === $token) {
            return [
                'errorcode' => $generalLogic->errorcode,
                'message'   => Config::get('errorcode')[$generalLogic->errorcode]
            ];
        }

        return json([
            'errorcode' => EC_SUCCESS,
            'message'   => Config::get('errorcode')[EC_SUCCESS],
        ], 200, ['Auth-Token' => $token]);
    }

    /**
     * 获取验证码
     * 如果是post请求则返回图片内容
     * @param Request $request
     * @return array|\think\Response
     */
    public function captcha(Request $request) {
        $response = captcha('', Config::get('captcha'));
        if ($request->isPost()) {
            $data = 'data:image/png;base64,' . base64_encode($response->getData());
            return return_result(EC_SUCCESS, $data);
        } elseif ($request->isGet()) {
            return $response;
        }
    }

    /**
     * 获取站点配置
     * @param Request $request
     * @return array
     */
    public function getSiteConfig(Request $request) {
        $siteType  = $request->post('siteType');
        $terminal  = $request->post('terminal');
        $siteConfigLogic = Loader::model('common/SiteConfig');
        $result = $siteConfigLogic->getConfig($siteType, $terminal);

        return send_response($result, EC_SUCCESS);
    }

    /**
     * 汇报登录状态
     */
    public function reportingOnlineStatus() {
        Hook::listen('auth_check');
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'user_online_status:'  . USER_ID;
        Cache::set($cacheKey, true, Config::get('common.cache_time')['user_online']);

        return return_result(EC_SUCCESS);
    }
}
