<?php
/**
 * 赛事信息业务逻辑
 * @createTime 2017/11/16 10:45
 */

namespace app\api\logic;

use think\Cache;
use think\Config;
use think\helper\Time;
use think\Loader;
use think\Model;

class Events extends Model {

    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 赛程表
     * @param string $sport 球类
     * @return mixed
     */
    public function calendar($sport = '') {
        //缓存获取数据
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'calendar_' . ($sport ?: 'all');
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        $sTime = date('Y-m-d H:i:s');
        $eTime = date('Y-m-d H:i:s', Time::daysAfter(4));
        $footballSchedules = $basketballSchedules = $tennisSchedules = [];

        if (empty($sport) || $sport == 'football') {
            $where = [
                'sfs_begin_time' => ['between', [$sTime, $eTime]],
                'sfs_game_type'  => '', //过滤特殊盘口
                'sfs_new_id'     => 0,  //过滤延赛盘口
            ];
            $field = [
                'sfs_begin_time AS begin_time',
                'sfs_home_name AS home_name',
                'sfs_guest_name AS guest_name',
                '\'football\' AS sport_type'
            ];
            $footballSchedules = Loader::model('sportsFootballSchedules')->where($where)->field($field)->select();
        }

        if (empty($sport) || $sport == 'basketball') {
            $where = [
                'sbs_begin_time' => ['between', [$sTime, $eTime]],
                'sbs_new_id'     => 0,  //过滤延赛盘口
            ];
            $field = [
                'sbs_begin_time AS begin_time',
                'sbs_home_name AS home_name',
                'sbs_guest_name AS guest_name',
                '\'basketball\' AS sport_type'
            ];
            $basketballSchedules = Loader::model('sportsBasketballSchedules')->where($where)->field($field)->select();
        }

        if (empty($sport) || $sport == 'tennis') {
            $where = [
                'sts_begin_time' => ['between', [$sTime, $eTime]],
                'sts_new_id'     => 0,  //过滤延赛盘口
            ];
            $field = [
                'sts_begin_time AS begin_time',
                'sts_home_name AS home_name',
                'sts_guest_name AS guest_name',
                '\'tennis\' AS sport_type'
            ];
            $tennisSchedules = Loader::model('sportsTennisSchedules')->where($where)->field($field)->select();
        }

        $schedules = array_merge($footballSchedules, $basketballSchedules, $tennisSchedules);

        $data = [];
        if ($schedules) {
            foreach ($schedules as $key => $row) {
                $volume[$key]  = $row->begin_time;
            }

            array_multisort($volume, SORT_ASC, $schedules);

            foreach($schedules as $schedule) {
                $timeArr = explode(' ', $schedule['begin_time']);
                $schedule['begin_time'] = $timeArr[1];
                $data[$timeArr[0]][] = $schedule;
            }
        }

        Cache::set($cacheKey, $data, Config::get('common.cache_time')['calendar']);
        return $data;
    }

    /**
     * 获取正在滚球的球类和比赛数量
     * @return bool|mixed
     */
    public function inPlayNowSports() {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'in_play_now_sports';
        $data = Cache::get($cacheKey);
        if (!$data) {
            $field = 'st_id AS sport_id,st_name AS name,st_eng_name AS eng_name,st_status AS status';
            $data = Loader::model('SportsTypes')->where(['st_status' => Config::get('status.sports_types_status')['normal']])->order('st_sort asc')->column($field);
            if (!$data) {
                return false;
            }
            $data = array_values($data);
            Cache::set($cacheKey, $data, Config::get('common.cache_time')['in_play_now_sports']);
        }

        //计算每种体育赛事的比赛数量
        foreach($data as $key => $item) {
            $eventsTypeNum = Loader::model('Events', $item['eng_name'])->countEventsTypeNum();
            if ($eventsTypeNum['in_play_now']) {
                $data[$key]['eventNum'] = $eventsTypeNum['in_play_now'];
            } else {
                unset($data[$key]);
            }
        }

        return array_values($data);
    }
}