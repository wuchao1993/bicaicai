<?php
/**
 * 玩法分组业务逻辑
 * @createTime 2017/4/13 14:37
 */

namespace app\api\logic;

use think\Cache;
use think\Config;
use think\Model;
use think\Loader;

class PlayTypeGroups extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 根据球类id获取玩法列表
     * @param $sportId
     * @return bool
     */
    public function getPlayTypeGroupsBySportId($sportId) {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . __FUNCTION__ . '_' . $sportId;
        if ($data = Cache::get($cacheKey)) {
            return $data;
        }
        $where = [
            'sptg_status' => Config::get('status.play_type_status')['yes'],
            'sptg_st_id'  => $sportId
        ];
        $data = Loader::model('SportsPlayTypeGroups')
            ->where($where)
            ->order('sptg_sort', 'DESC')
            ->field('sptg_st_id AS sport_id,sptg_name AS name,sptg_eng_name AS eng_name')
            ->select();

        Cache::set($cacheKey, $data, Config::get('common.cache_time')['play_type_groups']);
        return $data ?: [];
    }

    /**
     * 根据球类id获取玩法列表
     * @param $sportId
     * @param $eventType 赛事类型，today,early等
     * @return bool
     */
    public function getPlayTypeGroups($sportId, $eventType) {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . __FUNCTION__ . '_' . $sportId . '_' . $eventType;
        if ($data = Cache::get($cacheKey)) {
            return $data;
        }
        $where = [
            'sptg_status' => Config::get('status.play_type_status')['yes'],
            'sptg_st_id'  => $sportId
        ];
        $where[] = ['exp', 'FIND_IN_SET(\'' . $eventType . '\',sptg_event_types)'];

        $data = Loader::model('SportsPlayTypeGroups')
            ->where($where)
            ->order('sptg_sort', 'DESC')
            ->field('sptg_st_id AS sport_id,sptg_name AS name,sptg_eng_name AS eng_name')
            ->select();

        Cache::set($cacheKey, $data, Config::get('common.cache_time')['play_type_groups']);
        return $data ?: [];
    }
}