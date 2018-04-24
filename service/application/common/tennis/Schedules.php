<?php
/**
 * 对阵业务逻辑
 * @createTime 2017/8/15 17:14
 */

namespace app\common\tennis;

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
        $field = '
        sts_home_game_score,
        sts_guest_game_score,
        sts_home_set_score,
        sts_guest_set_score,
        sts_home_point_score,
        sts_guest_point_score,
        sts_timer';

        $info = Loader::model('SportsTennisSchedules')
            ->field($field)
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
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'schedules:tennis_schedule_info_'  . md5($id . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsTennisSchedules')->field($field)->find($id);
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
            'sts_check_status' => $status,
            'sts_modify_time' => date('Y-m-d H:i:s')
        ];
        if ($remark) {
            $update['sts_remark'] = $remark;
        }
        $ret = Loader::model('SportsTennisSchedules')->where(['sts_id' => $scheduleId])->update($update);
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
            'sts_status' => $status,
            'sts_modify_time' => date('Y-m-d H:i:s')
        ];
        $ret = Loader::model('SportsTennisSchedules')->where(['sts_id' => $scheduleId])->update($update);
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
            'sts_clearing' => $status,
            'sts_modify_time' => date('Y-m-d H:i:s')
        ];
        $ret = Loader::model('SportsTennisSchedules')->where(['sts_id' => $scheduleId])->update($update);
        return false === $ret ? false : true;
    }
}