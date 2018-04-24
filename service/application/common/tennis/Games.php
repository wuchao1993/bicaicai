<?php
/**
 * 网球盘口业务
 * @createTime 2017/9/26 14:25
 */

namespace app\common\tennis;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class Games extends Model {

    /**
     * 根据盘口id获取信息
     * @param $id
     * @param $field
     * @param bool $isCache 是否走缓存
     * @return bool|mixed
     */
    public function getInfoByGameId($id, $field = '', $isCache = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'games:tennis_game_info_'  . md5($id  . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsTennisGames')->field($field)->find($id);
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::set($cacheKey, $info, Config::get('common.cache_time')['game_info']);
        return $info;
    }

    /**
     * 获取主盘口id
     * @param $scheduleId 对阵id
     * @return mixed
     */
    public function getMasterGameIdByScheduleId($scheduleId) {
        $where = [
            'sts_id' => $scheduleId
        ];
        $info = Loader::model('SportsTennisSchedules')
            ->where($where)
            ->field('sts_master_game_id')
            ->find();
        if (!$info) {
            return false;
        }
        return $info->sts_master_game_id;
    }

    /**
     * 获取主盘口id
     * @param $gameId 盘口id
     * @return mixed
     */
    public function getMasterGameIdByGameId($gameId) {
        $gameInfo = Loader::model('SportsTennisGames')
            ->where(['stg_game_id' => $gameId])
            ->field('stg_sts_id,stg_master')
            ->find();
        if (!$gameInfo) {
            return false;
        }
        if ($gameInfo->stg_master == 1) {
            return $gameId;
        }

        $where = [
            'stg_sts_id' => $gameInfo->stg_sts_id,
            'stg_master' => Config::get('status.tennis_game_master')['yes'],
        ];
        $masterGameInfo = Loader::model('SportsTennisGames')->where($where)->column('stg_game_id');
        return $masterGameInfo[0];
    }

    /**
     * 玩法跟对应的数据库字段
     * @param $payType 玩法
     * @param $eventType 赛事类型，parlay
     * @return mixed
     */
    public function getPlayTypeField($payType, $eventType) {
        $map = [
            '1x2'             => 'stg_1x2',
            'handicap'        => 'stg_handicap',
            'ou'              => 'stg_ou',
            'ou_pg'           => 'stg_ou_pg',
            'correct_score'   => 'stg_correct_score',
            'parlay_1x2'      => 'stg_parlay_1x2',
            'parlay_handicap' => 'stg_parlay_handicap',
            'parlay_ou'       => 'stg_parlay_ou',
            'parlay_ou_pg'    => 'stg_parlay_ou_pg',
        ];
        if ($eventType == 'parlay') {
            return $map[$eventType . '_' . $payType];
        }
        return $map[$payType];
    }
}