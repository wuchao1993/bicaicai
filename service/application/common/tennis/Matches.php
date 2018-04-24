<?php
/**
 * 网球联赛业务逻辑
 * @createTime 2017/9/26 15:46
 */

namespace app\common\tennis;

use think\Cache;
use think\Config;
use think\Loader;
use think\Model;

class Matches extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 根据联赛id获取联赛信息
     * @param $id
     * @param $field
     * @param bool $isCache 是否走缓存
     * @return bool|mixed
     */
    public function getInfoById($id, $field = '', $isCache = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'matches:tennis_match_info_'  . md5($id . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsTennisMatches')->field($field)->find($id);
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::set($cacheKey, $info, Config::get('common.cache_time')['match_info']);
        return $info;
    }
}