<?php
/**
 * 算奖逻辑
 * @createTime 2017/5/8 14:56
 */

namespace app\clearing\tennis;

use think\Cache;
use think\Config;
use think\Loader;

class BonusCalculate extends \app\common\logic\BonusCalculate {

    /**
     * 对阵表算奖
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function calculateSchedules() {
        //获取比赛结束并且未算奖的对阵
        $schedulesInfo = Loader::model('Schedules', 'tennis')->getNoClearing();
        if(!$schedulesInfo) {
            return false;
        }

        //给这些比赛加正在算奖锁;
        $cacheTag           = md5(__CLASS__ . __FUNCTION__);
        $scheduleLockPrefix = Config::get('cache_option.prefix')['sports_tennis_schedule_lock'];
        $orderLockPrefix    = Config::get('cache_option.prefix')['sports_order_lock'];

        foreach($schedulesInfo as $scheduleInfo) {
            $scheduleLockKey = $scheduleLockPrefix . $scheduleInfo->sts_id;
            Cache::tag($cacheTag)->set($scheduleLockKey, true, Config::get('common.cache_time')['schedule_lock']);
        }

        $sportsTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName('tennis');

        foreach($schedulesInfo as $scheduleInfo) {
            //判断对阵是否有赛果
            $resultCount = Loader::model('SportsTennisResults')->where(['str_game_id' => $scheduleInfo->sts_master_game_id])->count();
            if (!$resultCount) {
                Cache::rm($scheduleLockPrefix . $scheduleInfo->sts_id);
                continue;
            }

            //获取这些对阵的订单ID
            $where = [
                'so_check_status'    => Config::get('status.order_check_status')['yes'],
                'so_status'          => Config::get('status.order_status')['wait'],
                'so_source_ids_from' => Config::get('status.order_source_ids_from')['schedule'],
                'so_st_id'           => $sportsTypeInfo['st_id'],
            ];
            $where[] = ['exp', 'FIND_IN_SET(' . $scheduleInfo->sts_id . ',so_source_ids)'];

            $orderCount = Loader::model('SportsOrders')->where($where)->count();
            if ($orderCount > 0) {
                $offset = 0;
                $limit = 100;
                do {
                    //控制一次处理的订单数，否则下面给订单加锁会很慢
                    $orders = Loader::model('SportsOrders')->where($where)->limit($offset, $limit)->column('so_no');
                    if($orders) {
                        //给这些订单加正在算奖锁; 不要把锁写到下面一个foreach
                        foreach($orders as $orderNo) {
                            $orderLockKey = $orderLockPrefix . $orderNo;
                            Cache::tag($cacheTag)->set($orderLockKey, true, Config::get('common.cache_time')['order_lock']);
                        }

                        foreach($orders as $orderNo) {
                            $this->calculateOrder($orderNo);
                            Cache::rm($orderLockPrefix . $orderNo);
                        }
                        $offset += $limit;
                    }
                } while ($orders);
            } else {
                //该对阵没有未结算订单直接修改状态为已结算
                //比赛结束才能改为已结算
                if ($scheduleInfo->sts_status == Config::get('status.tennis_schedule_status')['game_over']) {
                    Loader::model('common/Schedules', 'tennis')->updateClearingStatusById($scheduleInfo->sts_id, Config::get('status.tennis_schedule_clearing')['yes']);
                }
            }

            Cache::rm($scheduleLockPrefix . $scheduleInfo->sts_id);
        }

        //释放锁
        Cache::clear($cacheTag);
        return true;
    }

    /**
     * 冠军算奖
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function calculateOutright() {
        //返回已出赛果并且未算奖的冠军盘口id
        $gameIds = Loader::model('Outright', 'tennis')->getNoClearing();
        if(!$gameIds) {
            return false;
        }

        //给这些冠军盘口加正在算奖锁;
        $cacheTag           = md5(__CLASS__ . __FUNCTION__);
        $outrightLockPrefix = Config::get('cache_option.prefix')['sports_tennis_outright_lock'];
        $orderLockPrefix    = Config::get('cache_option.prefix')['sports_order_lock'];

        foreach($gameIds as $gameId) {
            $outrightLockKey = $outrightLockPrefix . $gameId;
            Cache::tag($cacheTag)->set($outrightLockKey, true, Config::get('common.cache_time')['schedule_lock']);
        }

        $sportsTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName('tennis');

        foreach($gameIds as $key => $gameId) {
            //获取这些盘口的订单ID
            $where = [
                'so_check_status'    => Config::get('status.order_check_status')['yes'],
                'so_status'          => Config::get('status.order_status')['wait'],
                'so_source_ids'      => $gameId,
                'so_source_ids_from' => Config::get('status.order_source_ids_from')['outright'],
                'so_st_id'           => $sportsTypeInfo['st_id'],
            ];

            $orderCount = Loader::model('SportsOrders')->where($where)->count();
            if ($orderCount > 0) {
                $offset = 0;
                $limit = 100;
                do {
                    //控制一次处理的订单数，否则下面给订单加锁会很慢
                    $orders = Loader::model('SportsOrders')->where($where)->limit($offset, $limit)->column('so_no');
                    if($orders) {
                        //给这些订单加正在算奖锁; 不要把锁写到下面一个foreach
                        foreach($orders as $orderNo) {
                            $orderLockKey = $orderLockPrefix . $orderNo;
                            Cache::tag($cacheTag)->set($orderLockKey, true, Config::get('common.cache_time')['order_lock']);
                        }

                        foreach($orders as $orderNo) {
                            $this->calculateOrder($orderNo);
                            Cache::rm($orderLockPrefix . $orderNo);
                        }
                        $offset += $limit;
                    }
                } while ($orders);
            } else {
                //该对阵没有未结算订单直接修改状态为已结算
                Loader::model('Outright', 'tennis')->updateOutrightClearing($gameId);
            }

            Cache::rm($outrightLockPrefix . $gameId);
        }

        //释放锁
        Cache::clear($cacheTag);
        return true;
    }
}