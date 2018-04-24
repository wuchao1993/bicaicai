<?php
/**
 * 篮球联赛业务逻辑
 * @createTime 2017/8/7 17:14
 */

namespace app\api\tennis;

use think\Cache;
use think\Config;
use think\Db;
use think\Loader;
use think\Model;

class Matches extends \app\common\tennis\Matches {
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
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'tennis_' . $eventType . '_' . $playTypeGroup;
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
                    $key = str_replace('stm_', '', $k);
                    $value[$key] = $v;
                }
            );

            //计算联赛的对阵数量
            if ($playTypeGroup == 'outright') {
                $where['sto_is_show'] = Config::get('status.tennis_outright_is_show')['yes'];
                $where['sto_stm_id'] = $item['stm_id'];
                $value['eventNum'] = Loader::model('SportsTennisOutright')->where($where)->count();
            } else {
                $where['sts_stm_id'] = $item['stm_id'];
                $where['sts_check_status'] = Config::get('status.tennis_schedule_check_status')['normal'];
                $where['sts_status'] = ['in', [
                    Config::get('status.tennis_schedule_status')['not_begin'],
                    Config::get('status.tennis_schedule_status')['in_game'],
                ]];
                $value['eventNum'] = Loader::model('SportsTennisSchedules')->where($where)->count();
            }

            //是否热门联赛归类
            if ($item['stm_is_hot'] == Config::get('status.tennis_match_is_hot')['yes']) {
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
            unset($where['sto_stm_id']);
            $sql = Loader::model('SportsTennisOutright')
                ->fetchSql(true)
                ->alias('o')
                ->where($where)
                ->field('stm_id,stm_name,stm_is_hot')
                ->join($join)
                ->group('stm_id')
                ->order('stm_sort desc')
                ->select();
        } else {
            unset($where['stg_stm_id']);
            $sql = Loader::model('SportsTennisGames')
                ->fetchSql(true)
                ->alias('g')
                ->where($where)
                ->field('stm_id,stm_name,stm_is_hot')
                ->join($join)
                ->group('stm_id')
                ->order('stm_sort desc')
                ->select();
        }
        if ($sql) {
            $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'tennis_' . $eventType . '_' . $playType;
            Cache::set($cacheKey, $sql);
            return true;
        }
        return false;
    }
}