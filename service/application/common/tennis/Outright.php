<?php
/**
 * 冠军盘口业务
 * @createTime 2017/9/28 16:25
 */

namespace app\common\tennis;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class Outright extends Model {

    /**
     * 根据盘口id获取信息
     * @param $id
     * @param string $field
     * @param bool $isCache 是否走缓存
     * @return bool|mixed
     */
    public function getInfoByGameId($id, $field = '', $isCache = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'outright:tennis_outright_info_'  . md5($id  . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsTennisOutright')->field($field)->find($id);
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::set($cacheKey, $info, Config::get('common.cache_time')['outright_info']);
        return $info;
    }
}