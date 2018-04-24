<?php
/**
 * 足球对阵业务逻辑
 * @createTime 2017/4/8 17:14
 */

namespace app\common\football;

use think\Cache;
use think\Config;
use think\Loader;
use think\Model;

class Schedules extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 根据对阵id获取滚球信息
     * @param $id
     * @return bool|mixed
     */
    public function getInPlayNowInfoById($id) {
        $info = Loader::model('SportsFootballSchedules')
            ->field('sfs_home_score,sfs_guest_score,sfs_home_red,sfs_guest_red,sfs_timer')
            ->find($id);
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        return $info;
    }

    /**
     * 根据对阵id获取对阵信息
     * @param $id
     * @param $field
     * @param bool $isCache 是否走缓存
     * @return bool|mixed
     */
    public function getInfoById($id, $field = '', $isCache = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'schedules:football_schedule_info_'  . md5($id . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsFootballSchedules')->field($field)->find($id);
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::set($cacheKey, $info, Config::get('common.cache_time')['schedule_info']);
        return $info;
    }

    /**
     * 修改状态
     * @param $scheduleId
     * @param $status
     * @param $remark
     * @return bool
     */
    public function updateCheckStatusById($scheduleId, $status, $remark = '') {
        $update = [
            'sfs_check_status' => $status,
            'sfs_modify_time' => date('Y-m-d H:i:s')
        ];
        if ($remark) {
            $update['sfs_remark'] = $remark;
        }
        $ret = Loader::model('SportsFootballSchedules')->where(['sfs_id' => $scheduleId])->update($update);
        return false === $ret ? false : true;
    }

    /**
     * 修改状态
     * @param $scheduleId
     * @param $status
     * @return bool
     */
    public function updateStatusById($scheduleId, $status) {
        $update = [
            'sfs_status' => $status,
            'sfs_modify_time' => date('Y-m-d H:i:s')
        ];
        $ret = Loader::model('SportsFootballSchedules')->where(['sfs_id' => $scheduleId])->update($update);
        return false === $ret ? false : true;
    }

    /**
     * 修改状态
     * @param $scheduleId
     * @param $status
     * @return bool
     */
    public function updateClearingStatusById($scheduleId, $status) {
        $update = [
            'sfs_clearing' => $status,
            'sfs_modify_time' => date('Y-m-d H:i:s')
        ];
        $ret = Loader::model('SportsFootballSchedules')->where(['sfs_id' => $scheduleId])->update($update);
        return false === $ret ? false : true;
    }
}