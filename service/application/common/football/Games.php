<?php
/**
 * 足球盘口业务
 * @createTime 2017/5/10 14:25
 */

namespace app\common\football;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class Games extends Model {

    /**
     * 根据盘口id获取信息
     * @param $id 盤口id
     * @param $field
     * @param bool $isCache 是否走缓存
     * @return bool|mixed
     */
    public function getInfoByGameId($id, $field = '', $isCache = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'games:football_game_info_'  . md5($id  . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsFootballGames')->field($field)->find($id);
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::set($cacheKey, $info, Config::get('common.cache_time')['game_info']);
        return $info;
    }

    /**
     * 获取主盘口id
     * @param $gameId 盘口id
     * @return mixed
     */
    public function getMasterGameIdByGameId($gameId) {
        $gameInfo = Loader::model('SportsFootballGames')
            ->where(['sfg_game_id' => $gameId])
            ->field('sfg_sfs_id,sfg_master,sfg_important')
            ->find();
        if (!$gameInfo) {
            return false;
        }
        if ($gameInfo->sfg_master == 1) {
            return $gameId;
        }

        $where = [
            'sfg_sfs_id'    => $gameInfo->sfg_sfs_id,
            'sfg_master'    => Config::get('status.football_game_master')['yes'],
            'sfg_important' => $gameInfo->sfg_important,
        ];
        $masterGameInfo = Loader::model('SportsFootballGames')->where($where)->column('sfg_game_id');
        return $masterGameInfo[0];
    }

    /**
     * 获取主盘口id
     * @param $scheduleId 对阵id
     * @return mixed
     */
    public function getMasterGameIdByScheduleId($scheduleId) {
        $where = [
            'sfs_id' => $scheduleId
        ];
        $info = Loader::model('SportsFootballSchedules')
            ->where($where)
            ->field('sfs_master_game_id')
            ->find();
        if (!$info) {
            return false;
        }
        return $info->sfs_master_game_id;
    }

    /**
     * 玩法跟对应的数据库字段
     * @param $payType 玩法
     * @param $eventType 赛事类型，parlay
     * @return mixed
     */
    public function getPlayTypeField($payType, $eventType) {
        $map = [
            'ft1x2'              => 'sfg_ft_1x2',
            'ft_handicap'        => 'sfg_ft_handicap',
            'ft_ou'              => 'sfg_ft_ou',
            'ft_oe'              => 'sfg_ft_oe',
            'ft_correct_score'   => 'sfg_ft_correct_score',
            'ft_total_goals'     => 'sfg_ft_total_goals',
            'ht_ft'              => 'sfg_ht_ft',
            '1h1x2'              => 'sfg_1h_1x2',
            '1h_handicap'        => 'sfg_1h_handicap',
            '1h_ou'              => 'sfg_1h_ou',
            '1h_oe'              => 'sfg_1h_oe',
            '1h_correct_score'   => 'sfg_1h_correct_score',
            '1h_total_goals'     => 'sfg_1h_total_goals',
            'parlay_ft1x2'       => 'sfg_parlay_ft_1x2',
            'parlay_ft_handicap' => 'sfg_parlay_ft_handicap',
            'parlay_ft_ou'       => 'sfg_parlay_ft_ou',
            'parlay_ft_oe'       => 'sfg_parlay_ft_oe',
            'parlay_1h1x2'       => 'sfg_parlay_1h_1x2',
            'parlay_1h_handicap' => 'sfg_parlay_1h_handicap',
            'parlay_1h_ou'       => 'sfg_parlay_1h_ou',
            'parlay_1h_oe'       => 'sfg_parlay_1h_oe',
        ];
        if ($eventType == 'parlay') {
            return $map[$eventType . '_' . $payType];
        }
        return $map[$payType];
    }
}