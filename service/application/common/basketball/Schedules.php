<?php
/**
 * 篮球对阵业务逻辑
 * @createTime 2017/8/15 17:14
 */

namespace app\common\basketball;

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
        $info = Loader::model('SportsBasketballSchedules')
            ->field('sbs_home_score,sbs_guest_score,sbs_timer')
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
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'schedules:basketball_schedule_info_'  . md5($id . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsBasketballSchedules')->field($field)->find($id);
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
            'sbs_check_status' => $status,
            'sbs_modify_time' => date('Y-m-d H:i:s')
        ];
        if ($remark) {
            $update['sbs_remark'] = $remark;
        }
        $ret = Loader::model('SportsBasketballSchedules')->where(['sbs_id' => $scheduleId])->update($update);
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
            'sbs_status' => $status,
            'sbs_modify_time' => date('Y-m-d H:i:s')
        ];
        $ret = Loader::model('SportsBasketballSchedules')->where(['sbs_id' => $scheduleId])->update($update);
        return false === $ret ? false : true;
    }

    /**
     * 修改结算状态
     * @param $scheduleId
     * @param $status
     * @return bool
     */
    public function updateClearingStatusById($scheduleId, $status) {
        $update = [
            'sbs_clearing' => $status,
            'sbs_modify_time' => date('Y-m-d H:i:s')
        ];
        $ret = Loader::model('SportsBasketballSchedules')->where(['sbs_id' => $scheduleId])->update($update);
        return false === $ret ? false : true;
    }
}