<?php
/**
 * 篮球球队业务逻辑
 * @createTime 2017/8/8 17:14
 */

namespace app\common\tennis;

use think\Cache;
use think\Loader;
use think\Model;
use think\Config;

class Teams extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 根据球队id获取球队信息
     * @param $id
     * @return bool|mixed
     */
    public function getInfoById($id) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'teams:tennis_team_info_'  . $id;
        $cache = Cache::get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $info = Loader::model('SportsTennisTeams')->get($id);
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::set($cacheKey, $info, Config::get('common.cache_time')['team_info']);
        return $info;
    }
}