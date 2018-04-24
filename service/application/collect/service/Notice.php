<?php
/**
 *公告采集数据
 * @createTime 2017/7/20 15:41
 */

namespace app\collect\service;

use curl\Curlrequest;
use think\Config;

class Notice {

    /**
     * 请求采集api
     * @param string $date 2017-05-01 日期
     * @return mixed
     */
    public function collect($date = '') {
        if ($date && date('Y-m-d', strtotime($date)) !== $date) {
            return false;
        }
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $url = Config::get('collect_url')['notice'];

        $data = json_decode(Curlrequest::post($url, ['datetime' => $date]), true);
        if (!$data || $data['errorcode'] != EC_SUCCESS) {
            return [];
        }
        return $data['data'];
    }
}