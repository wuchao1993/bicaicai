<?php
/**
 * 采集系统状态
 * @createTime 2017/7/20 15:41
 */

namespace app\common\logic;

use curl\Curlrequest;
use think\Cache;
use think\Config;

class System {

    /**
     * 请求采集api
     * @return mixed
     */
    public function checkCollectStatus() {
        $collectConfig = Config::load(APP_PATH . 'config/collect/config.php');
        $url = $collectConfig['collect_url']['system'];

        $result = json_decode(Curlrequest::post($url, [], [], 5), true);
        $response = [];
        if (!$result || $result['errorcode'] != EC_SUCCESS) {
            $status = false;
            $response['startTime'] = $result['data']['ss_maintain_startTime'];
            $response['endTime'] = $result['data']['ss_maintain_endTime'];
        } else {
            $status = true;
        }
        $response['status'] = $status;
        //把状态写到redis
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'collect_status';
        Cache::set($cacheKey, $response, Config::get('common.cache_time')['collect_status']);

        return $response;
    }
}