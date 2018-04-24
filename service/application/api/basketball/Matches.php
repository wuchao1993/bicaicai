<?php
/**
 * 篮球联赛业务逻辑
 * @createTime 2017/8/7 17:14
 */

namespace app\api\basketball;

use think\Cache;
use think\Config;
use think\Db;
use think\Loader;
use think\Model;

class Matches extends \app\common\basketball\Matches {
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
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'basketball_' . $eventType . '_' . $playTypeGroup;
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
                    $key = str_replace('sbm_', '', $k);
                    $value[$key] = $v;
                }
            );

            //计算联赛的对阵数量
            if ($playTypeGroup == 'outright') {
                $where['sbo_is_show'] = Config::get('status.basketball_outright_is_show')['yes'];
                $where['sbo_sbm_id'] = $item['sbm_id'];
                $value['eventNum'] = Loader::model('SportsBasketballOutright')->where($where)->count();
            } else {
                $where['sbs_sbm_id'] = $item['sbm_id'];
                $where['sbs_check_status'] = Config::get('status.basketball_schedule_check_status')['normal'];
                $where['sbs_status'] = ['in', [
                    Config::get('status.basketball_schedule_status')['not_begin'],
                    Config::get('status.basketball_schedule_status')['in_game'],
                    Config::get('status.basketball_schedule_status')['half_time'],
                ]];
                $value['eventNum'] = Loader::model('SportsBasketballSchedules')->where($where)->count();
            }

            //是否热门联赛归类
            if ($item['sbm_is_hot'] == Config::get('status.basketball_match_is_hot')['yes']) {
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
            unset($where['sbo_sbm_id']);
            $sql = Loader::model('SportsBasketballOutright')
                ->fetchSql(true)
                ->alias('o')
                ->where($where)
                ->field('sbm_id,sbm_name,sbm_is_hot')
                ->join($join)
                ->group('sbm_id')
                ->order('sbm_sort desc')
                ->select();
        } else {
            unset($where['sbg_sbm_id']);
            $sql = Loader::model('SportsBasketballGames')
                ->fetchSql(true)
                ->alias('g')
                ->where($where)
                ->field('sbm_id,sbm_name,sbm_is_hot')
                ->join($join)
                ->group('sbm_id')
                ->order('sbm_sort desc')
                ->select();
        }
        if ($sql) {
            $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'basketball_' . $eventType . '_' . $playType;
            Cache::set($cacheKey, $sql);
            return true;
        }
        return false;
    }
}