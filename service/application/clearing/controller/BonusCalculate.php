<?php
/**
 * 算奖
 * @createTime 2017/5/8 14:50
 */

namespace app\clearing\controller;

use think\Cache;
use think\Config;
use think\Loader;

class BonusCalculate {

    /**
     * 足球对阵算奖
     */
    public function calculateFootballSchedules() {
        //判断是否有锁
        $cacheKey = Config::get('cache_option.prefix')['sports_clearing_lock'] . __CLASS__ . __FUNCTION__;
        if (Cache::get($cacheKey)) {
            return 'LOCKED';
        }

        set_time_limit(60);
        Cache::set($cacheKey, true, Config::get('common.cache_time')['lock']);
        Loader::model('BonusCalculate', 'football')->calculateSchedules();
        Cache::rm($cacheKey);

        return true;
    }

    /**
     * 足球冠军算奖
     */
    public function calculateFootballOutright() {
        //判断是否有锁
        $cacheKey = Config::get('cache_option.prefix')['sports_clearing_lock'] . __CLASS__ . __FUNCTION__;
        if (Cache::get($cacheKey)) {
            return 'LOCKED';
        }

        set_time_limit(60);
        Cache::set($cacheKey, true, Config::get('common.cache_time')['lock']);
        Loader::model('BonusCalculate', 'football')->calculateOutright();
        Cache::rm($cacheKey);

        return true;
    }

    /**
     * 篮球对阵算奖
     */
    public function calculateBasketballSchedules() {
        //判断是否有锁
        $cacheKey = Config::get('cache_option.prefix')['sports_clearing_lock'] . __CLASS__ . __FUNCTION__;
        if (Cache::get($cacheKey)) {
            return 'LOCKED';
        }

        set_time_limit(60);
        Cache::set($cacheKey, true, Config::get('common.cache_time')['lock']);
        Loader::model('BonusCalculate', 'basketball')->calculateSchedules();
        Cache::rm($cacheKey);

        return true;
    }

    /**
     * 篮球冠军算奖
     */
    public function calculateBasketballOutright() {
        //判断是否有锁
        $cacheKey = Config::get('cache_option.prefix')['sports_clearing_lock'] . __CLASS__ . __FUNCTION__;
        if (Cache::get($cacheKey)) {
            return 'LOCKED';
        }

        set_time_limit(60);
        Cache::set($cacheKey, true, Config::get('common.cache_time')['lock']);
        Loader::model('BonusCalculate', 'basketball')->calculateOutright();
        Cache::rm($cacheKey);

        return true;
    }

    /**
     * 网球对阵算奖
     */
    public function calculateTennisSchedules() {
        //判断是否有锁
        $cacheKey = Config::get('cache_option.prefix')['sports_clearing_lock'] . __CLASS__ . __FUNCTION__;
        if (Cache::get($cacheKey)) {
            return 'LOCKED';
        }

        set_time_limit(60);
        Cache::set($cacheKey, true, Config::get('common.cache_time')['lock']);
        Loader::model('BonusCalculate', 'tennis')->calculateSchedules();
        Cache::rm($cacheKey);

        return true;
    }

    /**
     * 网球冠军算奖
     */
    public function calculateTennisOutright() {
        //判断是否有锁
        $cacheKey = Config::get('cache_option.prefix')['sports_clearing_lock'] . __CLASS__ . __FUNCTION__;
        if (Cache::get($cacheKey)) {
            return 'LOCKED';
        }

        set_time_limit(60);
        Cache::set($cacheKey, true, Config::get('common.cache_time')['lock']);
        Loader::model('BonusCalculate', 'tennis')->calculateOutright();
        Cache::rm($cacheKey);

        return true;
    }
}