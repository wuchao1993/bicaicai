<?php
/**
 * 足球比赛业务
 * @createTime 2017/4/26 11:43
 */

namespace app\clearing\football;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class Schedules extends \app\common\football\Schedules {
    /**
     * 撤销人工标记为撤单的单子
     * @return bool
     */
    public function cancelMarkedOrders() {
        $where = [
            'sfs_check_status' => Config::get('status.football_schedule_check_status')['wait_cancel'],
            'sfs_clearing' => Config::get('status.football_schedule_clearing')['no'],
        ];
        $schedules = Loader::model('SportsFootballSchedules')
            ->where($where)
            ->field('sfs_id,sfs_master_game_id')
            ->select();
        if (!$schedules) {
            return false;
        }

        $cacheTag           = md5(__CLASS__ . __FUNCTION__);
        $scheduleLockPrefix = Config::get('cache_option.prefix')['sports_football_schedule_lock'];
        $orderLockPrefix    = Config::get('cache_option.prefix')['sports_order_lock'];

        //给这些对阵加锁
        foreach($schedules as $schedule) {
            $scheduleLockKey = $scheduleLockPrefix . $schedule->sfs_id;
            Cache::tag($cacheTag)->set($scheduleLockKey, true);
        }

        $sportsTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName('football');

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
            $where = array_merge($whereCommon, [['exp', 'FIND_IN_SET(' . $schedule->sfs_id . ',so_source_ids)']]);

            //优先处理串关的单子，因为下面要根据单关的单子是否处理完来修改比赛状态
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
                                Loader::model('common/Orders', 'logic')->handleDistributedReturn($order->so_no, $schedule->sfs_master_game_id);
                                Loader::model('common/Orders', 'logic')->handleCancel($order->so_no, '人工撤单', 'hand_cancel', $schedule->sfs_master_game_id);
                            } else {
                                Loader::model('common/Orders', 'logic')->handleDistributedCancel($order->so_no, '人工撤单');
                            }
                        } elseif (in_array($order->so_status, [Config::get('status.order_status')['wait'], Config::get('status.order_status')['wait_hand_clearing']])) {
                            Loader::model('common/Orders', 'logic')->handleCancel($order->so_no, '人工撤单', 'hand_cancel', $schedule->sfs_master_game_id);
                        }
                        Cache::rm($orderLockPrefix . $order->so_no);
                    }
                    $offset += $limit;
                }
            } while ($orders);

            $whereCommon['so_source_ids'] = $schedule->sfs_id;
            $orderCount = Loader::model('SportsOrders')->where($whereCommon)->count();
            if (!$orderCount) {
                //改为结算状态
                $this->updateClearingStatusById($schedule->sfs_id, Config::get('status.football_schedule_clearing')['yes']);

                //修改为已撤单状态
                $this->updateCheckStatusById($schedule->sfs_id, Config::get('status.football_schedule_check_status')['canceled']);
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
            'sfs_check_status' => Config::get('status.football_schedule_check_status')['wait_cancel_clearing'],
            //'sfs_clearing' => Config::get('status.football_schedule_clearing')['yes'], //未结算的比赛也能撤
        ];
        $schedules = Loader::model('SportsFootballSchedules')
            ->where($where)
            ->field('sfs_id,sfs_master_game_id')
            ->select();
        if (!$schedules) {
            return false;
        }

        $cacheTag           = md5(__CLASS__ . __FUNCTION__);
        $scheduleLockPrefix = Config::get('cache_option.prefix')['sports_football_schedule_lock'];
        $orderLockPrefix    = Config::get('cache_option.prefix')['sports_order_lock'];

        //给这些对阵加锁
        foreach($schedules as $schedule) {
            $scheduleLockKey = $scheduleLockPrefix . $schedule->sfs_id;
            Cache::tag($cacheTag)->set($scheduleLockKey, true);
        }

        $sportsTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName('football');

        foreach($schedules as $schedule) {
            $where = [
                'so_source_ids_from' => Config::get('status.order_source_ids_from')['schedule'],
                'so_st_id'           => $sportsTypeInfo['st_id'],
                'so_status'          => Config::get('status.order_status')['distribute'],
            ];
            $where[] = ['exp', 'FIND_IN_SET(' . $schedule->sfs_id . ',so_source_ids)'];

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
                        Loader::model('common/Orders', 'logic')->handleDistributedReturn($orderNo, $schedule->sfs_master_game_id);
                        Cache::rm($orderLockPrefix . $orderNo);
                    }
                    $offset += $limit;
                }
            } while ($orders);

            $orderCount = Loader::model('SportsOrders')->where($where)->count();
            if (!$orderCount) {
                //改为未结算状态
                $this->updateClearingStatusById($schedule->sfs_id, Config::get('status.football_schedule_clearing')['no']);

                //修改状态为等待人工结算
                $this->updateCheckStatusById($schedule->sfs_id, Config::get('status.football_schedule_check_status')['wait_hand_clearing']);
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
            'sfs_check_status' => Config::get('status.football_schedule_check_status')['wait_clearing'],
            'sfs_clearing' => Config::get('status.football_schedule_clearing')['no'],
        ];
        $scheduleIds = Loader::model('SportsFootballSchedules')
            ->where($where)
            ->column('sfs_id');
        if (!$scheduleIds) {
            return false;
        }

        $cacheTag           = md5(__CLASS__ . __FUNCTION__);
        $scheduleLockPrefix = Config::get('cache_option.prefix')['sports_football_schedule_lock'];
        $orderLockPrefix    = Config::get('cache_option.prefix')['sports_order_lock'];

        //给这些对阵加锁
        foreach($scheduleIds as $scheduleId) {
            $scheduleLockKey = $scheduleLockPrefix . $scheduleId;
            Cache::tag($cacheTag)->set($scheduleLockKey, true);
        }

        $sportsTypeInfo = Loader::model('SportsTypes', 'logic')->getInfoByEngName('football');

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
                $this->updateClearingStatusById($scheduleId, Config::get('status.football_schedule_clearing')['yes']);

                //修改状态为等待人工结算
                $this->updateCheckStatusById($scheduleId, Config::get('status.football_schedule_check_status')['normal']);
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
            'sfs_clearing' => Config::get('status.football_schedule_clearing')['no'],
            'sfs_status' => ['IN', [
                Config::get('status.football_schedule_status')['game_over'],
                Config::get('status.football_schedule_status')['half_time'],
                Config::get('status.football_schedule_status')['2h_in_game'],
            ]],
            'sfs_check_status' => ['IN', [
                Config::get('status.football_schedule_check_status')['normal'],
                Config::get('status.football_schedule_check_status')['halt_sales'],
            ]],
        ];

        $ret = Loader::model('SportsFootballSchedules')
            ->where($where)
            ->field('sfs_id,sfs_master_game_id,sfs_status')
            ->select();
        return $ret ? $ret : false;
    }
}