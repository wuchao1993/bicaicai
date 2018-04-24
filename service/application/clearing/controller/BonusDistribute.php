<?php
/**
 * 派发奖金
 * @createTime 2017/5/8 14:49
 */

namespace app\clearing\controller;

use think\Loader;
use think\Cache;
use think\Config;

class BonusDistribute {
    /**
     * 派奖
     */
    public function index() {
        //判断是否有锁
        $cacheKey = Config::get('cache_option.prefix')['sports_clearing_lock'] . __CLASS__ . __FUNCTION__;
        if (Cache::get($cacheKey)) {
            return 'LOCKED';
        }

        set_time_limit(60);
        Cache::set($cacheKey, true, Config::get('common.cache_time')['lock']);
        Loader::model('BonusDistribute', 'logic')->distribute();
        Cache::rm($cacheKey);

        return true;
    }
}