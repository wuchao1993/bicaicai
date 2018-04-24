<?php
/**
 * 体育项目业务逻辑
 * @createTime 2017/4/4 16:52
 */

namespace app\api\logic;

use think\Cache;
use think\Config;
use think\Model;
use think\Loader;

class SportsTypes extends \app\common\logic\SportsTypes {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 获取大厅体育赛事列表
     * @return bool
     */
    public function getHomeSportsType() {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . __FUNCTION__;
        $data = Cache::get($cacheKey);
        if (!$data) {
            $field = 'st_id AS id,st_name AS name,st_eng_name AS eng_name,st_status AS status,st_icon AS icon';
            $data = Loader::model('SportsTypes')->order('st_sort asc')->column($field);
            if (!$data) {
                return false;
            }
            $data = array_values($data);
            Cache::set($cacheKey, $data, Config::get('common.cache_time')['sports_type']);
        }

        //计算每种体育赛事的比赛数量
        foreach($data as $key => $item) {
            $data[$key]['icon'] = Config::get('oss_sports_url') . $item['icon'];
            $data[$key]['eventNum'] = 0;
            if ($item['status'] == Config::get('status.sports_types_status')['normal']) {
                $eventsTypeNum = Loader::model('Events', $item['eng_name'])->countEventsTypeNum();
                $data[$key]['eventNum'] = $eventsTypeNum['total'];
            } else {
                $data[$key]['eventNum'] = 0;
            }
        }
        return $data;
    }

    /**
     * 获取正在滚球的球类和比赛数量
     * @return bool|mixed
     */
    public function getInPlayNowSportsType() {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . __FUNCTION__;
        $data = Cache::get($cacheKey);
        if (!$data) {
            $field = 'st_id AS id,st_name AS name,st_eng_name AS eng_name,st_status AS status';
            $data = Loader::model('SportsTypes')->order('st_sort asc')->column($field);
            if (!$data) {
                return false;
            }
            $data = array_values($data);
            Cache::set($cacheKey, $data, Config::get('common.cache_time')['sports_type']);
        }

        //计算每种体育赛事的比赛数量
        foreach($data as $key => $item) {
            $data[$key]['eventNum'] = 0;
            if ($item['status'] == Config::get('status.sports_types_status')['normal']) {
                $eventsTypeNum = Loader::model('Events', $item['eng_name'])->countEventsTypeNum();
                $data[$key]['eventNum'] = $eventsTypeNum['in_play_now'];
            } else {
                $data[$key]['eventNum'] = 0;
            }
        }
        return $data;
    }

    /**
     * PC端接口；根据赛事类型获取球类和玩法
     * @param $eventType
     * @return bool|mixed
     */
    public function getSportsTypeByEventType($eventType) {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . __FUNCTION__;
        $data = Cache::get($cacheKey);
        if (!$data) {
            $field = 'st_id AS id,st_name AS name,st_eng_name AS eng_name,st_status AS status';
            $data = Loader::model('SportsTypes')->order('st_sort asc')->column($field);
            if (!$data) {
                return false;
            }
            $data = array_values($data);
            Cache::set($cacheKey, $data, Config::get('common.cache_time')['sports_type']);
        }

        //计算每种体育赛事的比赛数量，获取玩法
        foreach($data as $key => $item) {
            $data[$key]['play_types'] = Loader::model('PlayTypeGroups', 'logic')->getPlayTypeGroups($item['id'], $eventType);

            //移动端分为波胆（全），波胆（半）；PC端只要全场波胆
            if ($item['eng_name'] == 'football') {
                foreach($data[$key]['play_types'] as $key2 => $val) {
                    if ($val['eng_name'] == 'ft_correct_score') {
                        $data[$key]['play_types'][$key2]['name'] = '波胆';
                        $data[$key]['play_types'][$key2]['eng_name'] = 'correct_score';
                    } elseif ($val['eng_name'] == '1h_correct_score') {
                        unset($data[$key]['play_types'][$key2]);
                    }
                    $data[$key]['play_types'] = array_values($data[$key]['play_types']);
                }
            }
            $data[$key]['eventNum'] = 0;
            if ($item['status'] == Config::get('status.sports_types_status')['normal']) {
                $eventsTypeNum = Loader::model('Events', $item['eng_name'])->countEventsTypeNum();
                $data[$key]['eventNum'] = $eventsTypeNum[$eventType];
            } else {
                $data[$key]['eventNum'] = 0;
            }
        }
        return $data;
    }

    /**
     * 根据球类ID获取每种赛事类型的玩法
     * @param $sportId
     * @return array
     */
    public function getPlayTypeGroupsBySportId($sportId) {
        $eventsType = Config::get('common.events_type');
        $data = [];
        foreach($eventsType as $key => $item) {
            $data[$key] = Loader::model('PlayTypeGroups', 'logic')->getPlayTypeGroups($sportId, $key);
        }
        return $data;
    }

    /**
     * 根据球类id获取赛事类型
     * @param $sportId
     * @return mixed
     */
    public function getEventsTypeBySportId($sportId) {
        $eventsType = Config::get('common.events_type');
        if (empty($eventsType)) {
            $this->errorcode = EC_EVENTS_TYPE_EMPTY;
            return false;
        }
        $sportInfo = Loader::model('SportsTypes')->find($sportId);
        if (!$sportInfo) {
            $this->errorcode = EC_SPORT_INFO_EMPTY;
            return false;
        }

        $eventsTypeNum = Loader::model('Events', $sportInfo['st_eng_name'])->countEventsTypeNum();

        $eventsTypeArr = [];
        foreach($eventsType as $key => $item) {
            $eventsTypeArr[$key]['type_name'] = $item;
            $eventsTypeArr[$key]['type_eng_name'] = $key;
            $eventsTypeArr[$key]['eventNum'] = $eventsTypeNum[$key];
        }
        $data = array_values($eventsTypeArr);
        return $data;
    }
}