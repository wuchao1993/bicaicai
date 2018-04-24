<?php
/**
 * 篮球盘口业务
 * @createTime 2017/8/9 14:25
 */

namespace app\common\basketball;

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
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'games:basketball_game_info_'  . md5($id  . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsBasketballGames')->field($field)->find($id);
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
            'sbs_id' => $scheduleId
        ];
        $info = Loader::model('SportsBasketballSchedules')
            ->where($where)
            ->field('sbs_master_game_id')
            ->find();
        if (!$info) {
            return false;
        }
        return $info->sbs_master_game_id;
    }

    /**
     * 获取主盘口id
     * @param $gameId 盘口id
     * @return mixed
     */
    public function getMasterGameIdByGameId($gameId) {
        $gameInfo = Loader::model('SportsBasketballGames')
            ->where(['sbg_game_id' => $gameId])
            ->field('sbg_sbs_id,sbg_master')
            ->find();
        if (!$gameInfo) {
            return false;
        }
        if ($gameInfo->sbg_master == 1) {
            return $gameId;
        }

        $where = [
            'sbg_sbs_id'    => $gameInfo->sbg_sbs_id,
            'sbg_master'    => Config::get('status.basketball_game_master')['yes'],
        ];
        $masterGameInfo = Loader::model('SportsBasketballGames')->where($where)->column('sbg_game_id');
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
            '1x2'             => 'sbg_1x2',
            'handicap'        => 'sbg_handicap',
            'ou'              => 'sbg_ou',
            'ou_team'         => 'sbg_ou_team',
            'oe'              => 'sbg_oe',
            'parlay_1x2'      => 'sbg_parlay_1x2',
            'parlay_handicap' => 'sbg_parlay_handicap',
            'parlay_ou'       => 'sbg_parlay_ou',
            'parlay_ou_team'  => 'sbg_parlay_ou_team',
            'parlay_oe'       => 'sbg_parlay_oe',
        ];
        if ($eventType == 'parlay') {
            return $map[$eventType . '_' . $payType];
        }
        return $map[$payType];
    }
}