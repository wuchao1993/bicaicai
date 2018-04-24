<?php
/**
 * 篮球赛果业务逻辑
 * @createTime 2017/8/14 16:58
 */

namespace app\common\basketball;

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
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'result:basketball_result_info_'  . md5($gameId . $field);
        if ($isCache) {
            $cache = Cache::get($cacheKey);
            if ($cache) {
                return $cache;
            }
        }
        $info = Loader::model('SportsBasketballResults')->field($field)->find($gameId);
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
        $string['1h_result'] = $string['2h_result'] = $string['result'] = '/';
        $string['1q_result'] = $string['2q_result'] = $string['3q_result'] = '/';
        $string['4q_result'] = $string['ot_result'] = '/';
        if ($info) {
            if ($info['sbr_home_score_1h'] === '' && $info['sbr_guest_score_1h'] === '') {
                $string['1h_result'] = '/';
            } else {
                $string['1h_result'] = $info['sbr_home_score_1h'] . ':' . $info['sbr_guest_score_1h'];
            }
            if ($info['sbr_home_score_2h'] === '' && $info['sbr_guest_score_2h'] === '') {
                $string['2h_result'] = '/';
            } else {
                $string['2h_result'] = $info['sbr_home_score_2h'] . ':' . $info['sbr_guest_score_2h'];
            }
            if ($info['sbr_home_score_1q'] === '' && $info['sbr_guest_score_1q'] === '') {
                $string['1q_result'] = '/';
            } else {
                $string['1q_result'] = $info['sbr_home_score_1q'] . ':' . $info['sbr_guest_score_1q'];
            }
            if ($info['sbr_home_score_2q'] === '' && $info['sbr_guest_score_2q'] === '') {
                $string['2q_result'] = '/';
            } else {
                $string['2q_result'] = $info['sbr_home_score_2q'] . ':' . $info['sbr_guest_score_2q'];
            }
            if ($info['sbr_home_score_3q'] === '' && $info['sbr_guest_score_3q'] === '') {
                $string['3q_result'] = '/';
            } else {
                $string['3q_result'] = $info['sbr_home_score_3q'] . ':' . $info['sbr_guest_score_3q'];
            }
            if ($info['sbr_home_score_4q'] === '' && $info['sbr_guest_score_4q'] === '') {
                $string['4q_result'] = '/';
            } else {
                $string['4q_result'] = $info['sbr_home_score_4q'] . ':' . $info['sbr_guest_score_4q'];
            }
            if ($info['sbr_home_score_ot'] === '' && $info['sbr_guest_score_ot'] === '') {
                $string['ot_result'] = '/';
            } else {
                $string['ot_result'] = $info['sbr_home_score_ot'] . ':' . $info['sbr_guest_score_ot'];
            }
            if ($info['sbr_home_score'] === '' && $info['sbr_guest_score'] === '') {
                $string['result'] = '/';
            } else {
                $string['result'] = $info['sbr_home_score'] . ':' . $info['sbr_guest_score'];
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
        $masterGameId = Loader::model('Games', 'basketball')->getMasterGameIdByScheduleId($id);
        if (!$masterGameId) {
            return false;
        }
        $where = [
            'sbr_game_id' => $masterGameId,
        ];
        $info = Loader::model('SportsBasketballResults')
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
            $resultString = $info['sbr_home_score_' . $betInfo[0]['game_type']] . ':' . $info['sbr_guest_score_' . $betInfo[0]['game_type']];
        } else {
            $resultString = $info['sbr_home_score'] . ':' . $info['sbr_guest_score'];
        }

        return $resultString;
    }
}