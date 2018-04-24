<?php
/**
 * 订单处理
 * @createTime 2017/5/27 10:08
 */

namespace app\clearing\logic;

use think\Config;
use think\Loader;
use think\Db;
use think\Cache;

class Orders extends \app\common\logic\Orders {
    /**
     * 异常订单撤单; 已改为人工撤单，不系统撤
     */
    /**
    public function cancelAbnormalOrders() {
        //获取异常状态订单
        $where = [
            'so_status' => Config::get('status.order_status')['result_abnormal'],
        ];
        $orderLockPrefix = Config::get('cache_option.prefix')['sports_order_lock'];

        $orders = Loader::model('SportsOrders')->where($where)->column('so_no');
        if ($orders) {
            //给这些订单加锁; 不要把锁写到下面一个foreach
            foreach($orders as $orderNo) {
                $orderLockKey = $orderLockPrefix . $orderNo;
                Cache::set($orderLockKey, true);
            }

            foreach($orders as $orderNo) {
                Loader::model('Orders', 'logic')->handleCancel($orderNo, '比赛异常');
                Cache::rm($orderLockPrefix . $orderNo);//释放锁
            }
        }
    }**/

    /**
     * 滚球订单验证
     */
    public function inPlayNowOrderCheck() {
        //获取这些盘口的订单ID
        $where = [
            'so_event_type' => Config::get('status.order_event_type')['in_play_now'],
            'so_check_status' => Config::get('status.order_check_status')['wait'],
            'so_source_ids_from' => Config::get('status.order_source_ids_from')['schedule'],
        ];
        $orders = Loader::model('SportsOrders')
            ->where($where)
            ->field('so_id,so_source_ids,so_st_id,so_bet_info,so_create_time')
            ->select();

        foreach($orders as $order) {
            $order = $order->toArray();
            $sportInfo = Loader::model('SportsTypes', 'logic')->getInfoById($order['so_st_id']);
            switch($sportInfo['st_eng_name']) {
                case 'football' :
                    $ret = Loader::model('Orders', 'football')->checkInPlayNowOrder($order);
                    //TODO 日志
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 滚球订单撤单
     */
    public function inPlayNowOrderCancel() {
        //获取未派奖等待撤票的订单
        $where = [
            'so_status' => Config::get('status.order_status')['wait_cancel'],
        ];
        $offset = 0;
        $limit = 100;
        $orderLockPrefix = Config::get('cache_option.prefix')['sports_order_lock'];

        do {
            $orders = Loader::model('SportsOrders')->where($where)->limit($offset, $limit)->column('so_no');
            if ($orders) {
                //给这些订单加锁
                foreach($orders as $orderNo) {
                    $orderLockKey = $orderLockPrefix . $orderNo;
                    Cache::set($orderLockKey, true);
                }

                foreach($orders as $orderNo) {
                    Loader::model('Orders', 'logic')->handleCancel($orderNo);
                    Cache::rm($orderLockPrefix . $orderNo);//释放锁
                }
                $offset += $limit;
            }
        } while ($orders);

        return true;
    }
}