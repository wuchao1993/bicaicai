<?php
/**
 * 足球赛果业务逻辑
 * @createTime 2017/7/15 17:14
 */

namespace app\common\football;

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
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'result:football_result_info_'  . md5($gameId . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsFootballResults')->field($field)->find($gameId);
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
        $string['1h_result'] = $string['result'] = '/';
        if ($info) {
            if ($info['sfr_home_score_1h'] === '' && $info['sfr_guest_score_1h'] === '') {
                $string['1h_result'] = '/';
            } else {
                $string['1h_result'] = $info['sfr_home_score_1h'] . ':' . $info['sfr_guest_score_1h'];
            }
            if ($info['sfr_home_score'] === '' && $info['sfr_guest_score'] === '') {
                $string['result'] = '/';
            } else {
                $string['result'] = $info['sfr_home_score'] . ':' . $info['sfr_guest_score'];
            }
        }
        return $string;
    }

    /**
     * 根据对阵id获取非特殊盘口的赛果
     * @param $id
     * @return bool|mixed
     */
    public function getResultByScheduleId($id) {
        $masterGameId = Loader::model('Games', 'football')->getMasterGameIdByScheduleId($id);
        if (!$masterGameId) {
            return false;
        }
        $where = [
            'sfr_game_id' => $masterGameId,
        ];
        $info = Loader::model('SportsFootballResults')
            ->field('sfr_home_score,sfr_guest_score,sfr_home_score_1h,sfr_guest_score_1h')
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
        if((substr($betInfo[0]['play_type'], 0, 2) == '1h')) {
            $resultString = $info['sfr_home_score_1h'] . ':' . $info['sfr_guest_score_1h'];
        } else {
            $resultString = $info['sfr_home_score'] . ':' . $info['sfr_guest_score'];
        }

        return $resultString;
    }
}