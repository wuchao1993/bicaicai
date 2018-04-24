<?php

namespace app\common\logic;

use think\Loader;
use think\Config;
use think\Cache;
use think\Model;

class UserBankRelation extends Model {

    /**
     * 根据用户id获取银行信息
     * 这边有个坑：数据库设计用户可以绑定多张卡，但是业务逻辑上一个用户只能绑一张。所以这个函数就默认user_id和ub_status是唯一的
     * @param $uid
     * @param string $field
     * @param bool $isCache
     * @return bool|mixed
     */
    public function getInfoByUid($uid, $field = '*', $isCache = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'user:bank_relation_info_' . md5($uid . $field);
        if($isCache) {
            $cache = Cache::get($cacheKey);
            if($cache) {
                return $cache;
            }
        }

        $where = [
            'user_id'   => $uid,
            'ub_status' => Config::get('status.user_bank_status')['enable']
        ];
        $info  = Loader::model('UserBankRelation')->field($field)->where($where)->find();
        if(!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::set($cacheKey, $info, Config::get('common.cache_time')['user_info']);

        return $info;
    }

    public function getBankAccountCount($account) {
        $condition = [
            'ub_bank_account' => $account
        ];

        return Loader::model('UserBankRelation')->where($condition)->count();
    }

    public function getUserAccountCount($userId) {
        $condition = [
            'user_id'   => $userId,
            'ub_status' => 1
        ];

        return Loader::model('UserBankRelation')->where($condition)->count();
    }

    public function getInfo($userBankId) {
        $condition = [
            'ub_id' => $userBankId
        ];

        return Loader::model('UserBankRelation')->where($condition)->find();
    }
}