<?php
/**
 * 篮球赛果业务逻辑
 * @createTime 2017/8/14 16:58
 */

namespace app\common\tennis;

use think\Cache;
use think\Config;
use think\Loader;
use think\Model;

class Results extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 根据盘口id获取赛果信息
     * @param $gameId
     * @param $field
     * @param bool $isCache 是否走缓存
     * @return bool|mixed
     */
    public function getInfoByGameId($gameId, $field = '', $isCache = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'result:tennis_result_info_'  . md5($gameId . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsTennisResults')->field($field)->find($gameId);
        if (!$info) {
            return false;
        }
        $info = $info->toArray();
        Cache::tag('result_info_' . $gameId)->set($cacheKey, $info, Config::get('common.cache_time')['result_info']);
        return $info;
    }

    /**
     * 根据盘口id获取赛果的拼接字符串信息
     * @param $gameId
     * @return bool|mixed
     */
    public function getStringInfoByGameId($gameId) {
        $info = $this->getInfoByGameId($gameId);
        $string['1st_result'] = $string['2nd_result'] = $string['3rd_result'] = '/';
        $string['4th_result'] = $string['5th_result'] = $string['handicap_result'] = '/';
        $string['ou_result'] = $string['result'] = '/';
        if ($info) {
            if ($info['str_home_score_1st'] === '' && $info['str_guest_score_1st'] === '') {
                $string['1st_result'] = '/';
            } else {
                $string['1st_result'] = $info['str_home_score_1st'] . ':' . $info['str_guest_score_1st'];
            }
            if ($info['str_home_score_2nd'] === '' && $info['str_guest_score_2nd'] === '') {
                $string['2nd_result'] = '/';
            } else {
                $string['2nd_result'] = $info['str_home_score_2nd'] . ':' . $info['str_guest_score_2nd'];
            }
            if ($info['str_home_score_3rd'] === '' && $info['str_guest_score_3rd'] === '') {
                $string['3rd_result'] = '/';
            } else {
                $string['3rd_result'] = $info['str_home_score_3rd'] . ':' . $info['str_guest_score_3rd'];
            }
            if ($info['str_home_score_4th'] === '' && $info['str_guest_score_4th'] === '') {
                $string['4th_result'] = '/';
            } else {
                $string['4th_result'] = $info['str_home_score_4th'] . ':' . $info['str_guest_score_4th'];
            }
            if ($info['str_home_score_5th'] === '' && $info['str_guest_score_5th'] === '') {
                $string['5th_result'] = '/';
            } else {
                $string['5th_result'] = $info['str_home_score_5th'] . ':' . $info['str_guest_score_5th'];
            }
            if ($info['str_home_score_handicap'] === '' && $info['str_guest_score_handicap'] === '') {
                $string['handicap_result'] = '/';
            } else {
                $string['handicap_result'] = $info['str_home_score_handicap'] . ':' . $info['str_guest_score_handicap'];
            }
            if ($info['str_home_score_ou'] === '' && $info['str_guest_score_ou'] === '') {
                $string['ou_result'] = '/';
            } else {
                $string['ou_result'] = $info['str_home_score_ou'] . ':' . $info['str_guest_score_ou'];
            }
            if ($info['str_home_score'] === '' && $info['str_guest_score'] === '') {
                $string['result'] = '/';
            } else {
                $string['result'] = $info['str_home_score'] . ':' . $info['str_guest_score'];
            }
        }
        return $string;
    }

    /**
     * 根据对阵id获取赛果
     * @param $id
     * @return bool|mixed
     */
    public function getInfoByScheduleId($id) {
        $masterGameId = Loader::model('Games', 'tennis')->getMasterGameIdByScheduleId($id);
        if (!$masterGameId) {
            return false;
        }
        $where = [
            'str_game_id' => $masterGameId,
        ];
        $info = Loader::model('SportsTennisResults')
            ->where($where)
            ->find();
        if(!$info) {
            return false;
        }
        $info = $info->toArray();

        return $info;
    }

    /**
     * 获取结算比分
     * @param $betInfo
     * @return string
     */
    public function getClearingResult($betInfo) {
        $info = $this->getInfoByGameId($betInfo[0]['master_game_id']);
        if($betInfo[0]['game_type']) {
            $resultString = $info['str_home_score_' . $betInfo[0]['game_type']] . ':' . $info['str_guest_score_' . $betInfo[0]['game_type']];
        } else {
            if ($betInfo[0]['play_type'] == 'ou_pg') {
                $resultString = $info['str_home_score_ou'] . ':' . $info['str_guest_score_ou'];
            } else {
                $resultString = $info['str_home_score'] . ':' . $info['str_guest_score'];
            }
        }

        return $resultString;
    }
}