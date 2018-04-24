<?php
/**
 * 审核不通过的滚球订单撤票
 * @createTime 2017/5/9 15:16
 */

namespace app\clearing\controller;

use think\Loader;
use think\Cache;
use think\Config;

class Orders {
    /**
     * 滚球订单撤单
     */
    public function inPlayNowOrderCancel() {
        //判断是否有锁
        $cacheKey = Config::get('cache_option.prefix')['sports_clearing_lock'] . __CLASS__ . __FUNCTION__;
        if (Cache::get($cacheKey)) {
            return 'LOCKED';
        }

        Cache::set($cacheKey, true);
        Loader::model('Orders', 'logic')->inPlayNowOrderCancel();
        Cache::rm($cacheKey);

        return true;
    }

    /**
     * 滚球订单验证
     */
    public function inPlayNowOrderCheck() {
        Loader::model('Orders', 'logic')->inPlayNowOrderCheck();
    }

    /**
     * 已改为人工撤单，取消系统撤单
     */
    /**
    public function cancelAbnormalOrders() {
        Loader::model('Orders', 'logic')->cancelAbnormalOrders();
    }
    **/

    /***********************************
     *
     * 撤销标记等待撤单的比赛
     *
     ***********************************/
    public function cancelFootballMarkedOrders() {
        Loader::model('Schedules', 'football')->cancelMarkedOrders();
    }

    public function cancelBasketballMarkedOrders() {
        Loader::model('Schedules', 'basketball')->cancelMarkedOrders();
    }

    public function cancelTennisMarkedOrders() {
        Loader::model('Schedules', 'tennis')->cancelMarkedOrders();
    }

    /***********************************
     *
     * 撤销标记等待撤销结算的比赛
     *
     ***********************************/
    public function cancelClearingFootballMarkedOrders() {
        Loader::model('Schedules', 'football')->cancelClearingMarkedOrders();
    }

    public function cancelClearingBasketballMarkedOrders() {
        Loader::model('Schedules', 'basketball')->cancelClearingMarkedOrders();
    }

    public function cancelClearingTennisMarkedOrders() {
        Loader::model('Schedules', 'tennis')->cancelClearingMarkedOrders();
    }

    /***********************************
     *
     * 结算标记等待结算的比赛
     *
     ***********************************/
    public function clearFootballMarkedOrders() {
        Loader::model('Schedules', 'football')->clearMarkedOrders();
    }

    public function clearBasketballMarkedOrders() {
        Loader::model('Schedules', 'basketball')->clearMarkedOrders();
    }

    public function clearTennisMarkedOrders() {
        Loader::model('Schedules', 'tennis')->clearMarkedOrders();
    }
}