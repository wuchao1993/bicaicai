<?php
/**
 * 派发奖金
 * @createTime 2017/5/27 10:08
 */

namespace app\clearing\logic;

use think\Config;
use think\Loader;
use think\Db;
use think\Cache;

class BonusDistribute {
    /**
     * 派奖
     */
    public function distribute() {
        //获取等待派奖的订单
        $where = [
            'so_status' => Config::get('status.order_status')['clearing'],
        ];
        $offset = 0;
        $limit = 100;
        $orderLockPrefix = Config::get('cache_option.prefix')['sports_order_lock'];

        do {
            $orders = Loader::model('SportsOrders')->where($where)->limit($offset, $limit)->column('so_no');
            if ($orders) {
                //给这些订单加锁; 不要把锁写到下面一个foreach
                foreach($orders as $orderNo) {
                    $orderLockKey = $orderLockPrefix . $orderNo;
                    Cache::set($orderLockKey, true);
                }

                foreach($orders as $orderNo) {
                    Loader::model('Orders', 'logic')->handleBonusDistribute($orderNo);
                    Cache::rm($orderLockPrefix . $orderNo);//释放锁
                }
                $offset += $limit;
            }
        } while ($orders);

        return true;
    }
}