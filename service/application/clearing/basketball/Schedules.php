<?php
/**
 * 篮球比赛业务
 * @createTime 2017/4/26 11:43
 */

namespace app\clearing\basketball;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class Schedules extends \app\common\basketball\Schedules {
    /**
     * 撤销人工标记为撤单的单子
     * @return bool
     */
    public function cancelMarkedOrders() {
        $where = [
            'sbs_check_status' => Config::get('status.basketball_schedule_check_status')['wait_cancel'],
            'sbs_clearing' => Config::get('status.basketball_schedule_clearing')['no'],
        ];
        $schedules = Loader::model('SportsBasketballSchedules')
            ->where($where)
            ->field('sbs_id,sbs_master_game_id')
            ->select();
        if (!$schedules) {
            return false;
        }

        $cacheTag           = md5(__CLASS__ . __FUNCTION__);
        $scheduleLockPrefix = Config::get('cache_option.prefix')['sports_basketball_schedule_lock'];
        $orderLockPrefix    = Config::get('cache_option.prefix')['sports_order_lock'];

        //给这些对阵加锁
        foreach($schedules as $schedule) {
            $scheduleLockKey = $scheduleLockPrefix . $schedule->sbs_id;
            Cache::tag($cacheTag)->set($scheduleLockKey, true);
        }

        $sportsTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName('basketball');

        foreach($schedules as $schedule) {
            $whereCommon = [
                'so_source_ids_from' => Config::get('status.order_source_ids_from')['schedule'],
                'so_st_id'           => $sportsTypeInfo['st_id'],
                'so_status'          => ['IN', [
                    Config::get('status.order_status')['wait'],
                    Config::get('status.order_status')['wait_hand_clearing'],
                    Config::get('status.order_status')['clearing'], //这个要等变成distribute才能撤单
                    Config::get('status.order_status')['distribute'],
                ]],
            ];
            $where = array_merge($whereCommon, [['exp', 'FIND_IN_SET(' . $schedule->sbs_id . ',so_source_ids)']]);

            $offset = 0;
            $limit = 100;
            do {
                //控制一次处理的订单数，否则下面给订单加锁会很慢
                $orders = Loader::model('SportsOrders')
                    ->order('so_event_type DESC')
                    ->where($where)
                    ->limit($offset, $limit)
                    ->field('so_no,so_status,so_event_type')
                    ->select();
                if($orders) {
                    //给这些订单加正在算奖锁; 不要把锁写到下面一个foreach
                    foreach($orders as $order) {
                        $orderLockKey = $orderLockPrefix . $order->so_no;
                        Cache::tag($cacheTag)->set($orderLockKey, true);
                    }

                    foreach($orders as $order) {
                        if ($order->so_status == Config::get('status.order_status')['distribute']) {
                            if ($order->so_event_type == Config::get('status.order_event_type')['parlay']) {
                                Loader::model('common/Orders', 'logic')->handleDistributedReturn($order->so_no, $schedule->sbs_master_game_id);
                                Loader::model('common/Orders', 'logic')->handleCancel($order->so_no, '人工撤单', 'hand_cancel', $schedule->sbs_master_game_id);
                            } else {
                                Loader::model('common/Orders', 'logic')->handleDistributedCancel($order->so_no, '人工撤单');
                            }
                        } elseif (in_array($order->so_status, [Config::get('status.order_status')['wait'], Config::get('status.order_status')['wait_hand_clearing']])) {
                            Loader::model('common/Orders', 'logic')->handleCancel($order->so_no, '人工撤单', 'hand_cancel', $schedule->sbs_master_game_id);
                        }
                        Cache::rm($orderLockPrefix . $order->so_no);
                    }
                    $offset += $limit;
                }
            } while ($orders);

            $whereCommon['so_source_ids'] = $schedule->sbs_id;
            $orderCount = Loader::model('SportsOrders')->where($whereCommon)->count();
            if (!$orderCount) {
                //改为结算状态
                $this->updateClearingStatusById($schedule->sbs_id, Config::get('status.basketball_schedule_clearing')['yes']);

                //修改为已撤单状态
                $this->updateCheckStatusById($schedule->sbs_id, Config::get('status.basketball_schedule_check_status')['canceled']);
            }
        }

        //释放锁
        Cache::clear($cacheTag);
        return true;
    }

    /**
     * 撤销标记等待撤销结算的比赛
     * @return bool
     */
    public function cancelClearingMarkedOrders() {
        $where = [
            'sbs_check_status' => Config::get('status.basketball_schedule_check_status')['wait_cancel_clearing'],
            //'sbs_clearing' => Config::get('status.basketball_schedule_clearing')['yes'],
        ];
        $schedules = Loader::model('SportsBasketballSchedules')
            ->where($where)
            ->field('sbs_id,sbs_master_game_id')
            ->select();
        if (!$schedules) {
            return false;
        }

        $cacheTag           = md5(__CLASS__ . __FUNCTION__);
        $scheduleLockPrefix = Config::get('cache_option.prefix')['sports_basketball_schedule_lock'];
        $orderLockPrefix    = Config::get('cache_option.prefix')['sports_order_lock'];

        //给这些对阵加锁
        foreach($schedules as $schedule) {
            $scheduleLockKey = $scheduleLockPrefix . $schedule->sbs_id;
            Cache::tag($cacheTag)->set($scheduleLockKey, true);
        }

        $sportsTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName('basketball');

        foreach($schedules as $schedule) {
            $where = [
                'so_source_ids_from' => Config::get('status.order_source_ids_from')['schedule'],
                'so_st_id'           => $sportsTypeInfo['st_id'],
                'so_status'          => Config::get('status.order_status')['distribute'],
            ];
            $where[] = ['exp', 'FIND_IN_SET(' . $schedule->sbs_id . ',so_source_ids)'];

            $offset = 0;
            $limit = 100;
            do {
                //控制一次处理的订单数，否则下面给订单加锁会很慢
                $orders = Loader::model('SportsOrders')->where($where)->limit($offset, $limit)->column('so_no');
                if($orders) {
                    //给这些订单加正在算奖锁; 不要把锁写到下面一个foreach
                    foreach($orders as $orderNo) {
                        $orderLockKey = $orderLockPrefix . $orderNo;
                        Cache::tag($cacheTag)->set($orderLockKey, true);
                    }

                    foreach($orders as $orderNo) {
                        //TODO logo
                        Loader::model('common/Orders', 'logic')->handleDistributedReturn($orderNo, $schedule->sbs_master_game_id);
                        Cache::rm($orderLockPrefix . $orderNo);
                    }
                    $offset += $limit;
                }
            } while ($orders);

            $orderCount = Loader::model('SportsOrders')->where($where)->count();
            if (!$orderCount) {
                //改为未结算状态
                $this->updateClearingStatusById($schedule->sbs_id, Config::get('status.basketball_schedule_clearing')['no']);

                //修改状态为等待人工结算
                $this->updateCheckStatusById($schedule->sbs_id, Config::get('status.basketball_schedule_check_status')['wait_hand_clearing']);
            }
        }

        //释放锁
        Cache::clear($cacheTag);
        return true;
    }

    /**
     * 结算被标记等待人工结算的比赛单子
     * @return bool
     */
    public function clearMarkedOrders() {
        $where = [
            'sbs_check_status' => Config::get('status.basketball_schedule_check_status')['wait_clearing'],
            'sbs_clearing' => Config::get('status.basketball_schedule_clearing')['no'],
        ];
        $scheduleIds = Loader::model('SportsBasketballSchedules')
            ->where($where)
            ->column('sbs_id');
        if (!$scheduleIds) {
            return false;
        }

        $cacheTag           = md5(__CLASS__ . __FUNCTION__);
        $scheduleLockPrefix = Config::get('cache_option.prefix')['sports_basketball_schedule_lock'];
        $orderLockPrefix    = Config::get('cache_option.prefix')['sports_order_lock'];

        //给这些对阵加锁
        foreach($scheduleIds as $scheduleId) {
            $scheduleLockKey = $scheduleLockPrefix . $scheduleId;
            Cache::tag($cacheTag)->set($scheduleLockKey, true);
        }

        $sportsTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName('basketball');

        foreach($scheduleIds as $scheduleId) {
            $where = [
                'so_source_ids_from' => Config::get('status.order_source_ids_from')['schedule'],
                'so_st_id'           => $sportsTypeInfo['st_id'],
                'so_status'          => ['IN', [
                    Config::get('status.order_status')['wait'],
                    Config::get('status.order_status')['wait_hand_clearing'],
                ]],
            ];
            $where[] = ['exp', 'FIND_IN_SET(' . $scheduleId . ',so_source_ids)'];

            $offset = 0;
            $limit = 100;
            do {
                //控制一次处理的订单数，否则下面给订单加锁会很慢
                $orders = Loader::model('SportsOrders')->where($where)->limit($offset, $limit)->column('so_no');
                if($orders) {
                    //给这些订单加正在算奖锁; 不要把锁写到下面一个foreach
                    foreach($orders as $orderNo) {
                        $orderLockKey = $orderLockPrefix . $orderNo;
                        Cache::tag($cacheTag)->set($orderLockKey, true);
                    }

                    foreach($orders as $orderNo) {
                        //TODO logo
                        Loader::model('common/BonusCalculate', 'logic')->calculateOrder($orderNo);
                        Cache::rm($orderLockPrefix . $orderNo);
                    }
                    $offset += $limit;
                }
            } while ($orders);

            $orderCount = Loader::model('SportsOrders')->where($where)->count();
            if (!$orderCount) {
                //改为结算状态
                $this->updateClearingStatusById($scheduleId, Config::get('status.basketball_schedule_clearing')['yes']);

                //修改状态为等待人工结算
                $this->updateCheckStatusById($scheduleId, Config::get('status.basketball_schedule_check_status')['normal']);
            }
        }

        //释放锁
        Cache::clear($cacheTag);
        return true;
    }

    /**
     * 返回比赛已结束未算奖的对阵id
     * @return bool
     */
    public function getNoClearing() {
        $where = [
            'sbs_clearing' => Config::get('status.basketball_schedule_clearing')['no'],
            'sbs_check_status' => ['IN', [
                Config::get('status.basketball_schedule_check_status')['normal'],
                Config::get('status.basketball_schedule_check_status')['halt_sales'],
            ]],
            'sbs_status' => ['IN', [
                Config::get('status.basketball_schedule_status')['in_game'],
                Config::get('status.basketball_schedule_status')['half_time'],
                Config::get('status.basketball_schedule_status')['game_over'],
            ]],
        ];
        $ret = Loader::model('SportsBasketballSchedules')
            ->where($where)
            ->field('sbs_id,sbs_master_game_id,sbs_status')
            ->select();
        return $ret ? $ret : false;
    }
}