<?php
/**
 * 足球联赛业务逻辑
 * @createTime 2017/4/8 17:14
 */

namespace app\api\football;

use think\Cache;
use think\Config;
use think\Db;
use think\Loader;
use think\Model;

class Matches extends \app\common\football\Matches {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 返回联赛列表；不返回所有联赛，只返回已展示的赛事联赛
     * 从缓存获取sql语句并执行
     * @param $eventType 赛事类型
     * @param $playTypeGroup 玩法
     * @return bool
     */
    public function getLeagueMatches($eventType, $playTypeGroup) {
        if ($playTypeGroup == 'ft_correct_score') {
            $playTypeGroup = 'correct_score';
        }
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'football_' . $eventType . '_' . $playTypeGroup;
        $sql = Cache::get($cacheKey);
        if (!$sql) {
            $this->errorcode = EC_MATCHES_EMPTY;
            return false;
        }
        $data = Db::query($sql);
        if (!$data) {
            return false;
        }

        $list['hot'] = $list['others'] = $value = [];
        foreach($data as $item) {
            //去掉表前缀
            array_walk($item, function($v, $k) use (&$value) {
                    $key = str_replace('sfm_', '', $k);
                    $value[$key] = $v;
                }
            );

            //计算联赛的对阵数量
            if ($playTypeGroup == 'outright') {
                $where['sfo_is_show'] = Config::get('status.football_outright_is_show')['yes'];
                $where['sfo_sfm_id'] = $item['sfm_id'];
                $value['eventNum'] = Loader::model('SportsFootballOutright')->where($where)->count();
            } else {
                $where = Loader::model('Events', 'football')->getEventTypeWhere($eventType);
                $where['sfs_sfm_id']       = $item['sfm_id'];
                $where['sfs_check_status'] = Config::get('status.football_schedule_check_status')['normal'];
                $where['sfg_master']       = Config::get('status.football_game_master')['yes'];
                $where['sfg_is_show']      = Config::get('status.football_game_is_show')['yes'];
                $join = [
                    ['sports_football_games g', 's.sfs_id=g.sfg_sfs_id', 'LEFT']
                ];
                $value['eventNum'] = Loader::model('SportsFootballSchedules')->alias('s')->where($where)->join($join)->count();
            }

            //是否热门联赛归类
            if ($item['sfm_is_hot'] == Config::get('status.football_match_is_hot')['yes']) {
                $list['hot'][] = $value;
            } else {
                $list['others'][] = $value;
            }
        }
        return $list;
    }

    /**
     * 创建联赛筛选的sql语句缓存
     * @param $eventType 赛事类型
     * @param $playType 玩法
     * @param $where
     * @param $join
     * @return bool
     */
    public function createFilter($eventType, $playType, $where, $join) {
        if ($playType == 'outright') {
            unset($where['sfo_sfm_id']);
            $sql = Loader::model('SportsFootballOutright')
                ->fetchSql(true)
                ->alias('o')
                ->where($where)
                ->field('sfm_id,sfm_name,sfm_is_hot')
                ->join($join)
                ->group('sfm_id')
                ->order('sfm_sort desc')
                ->select();
        } else {
            unset($where['sfg_sfm_id']);
            $sql = Loader::model('SportsFootballGames')
                ->fetchSql(true)
                ->alias('g')
                ->where($where)
                ->field('sfm_id,sfm_name,sfm_is_hot')
                ->join($join)
                ->group('sfm_id')
                ->order('sfm_sort desc')
                ->select();
        }
        if ($sql) {
            $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'football_' . $eventType . '_' . $playType;
            Cache::set($cacheKey, $sql);
            return true;
        }
        return false;
    }
}