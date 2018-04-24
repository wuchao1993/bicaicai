<?php
/**
 * 盘口业务
 * @createTime 2017/9/26 14:43
 */

namespace app\collect\tennis;

use think\Config;
use think\Loader;
use think\Log;
use think\Cache;

class Games extends \app\common\tennis\Games {

    public $uniqueIdArr = [];

    /**
     * 判断盘口是否存在，不存在则插入，存在更新
     * @param $valueArr 盘口信息
     * @return bool
     */
    public function checkTodayAndEarlyGames($valueArr) {
        //组合values
        $valueStr = '';
        foreach($valueArr as $value) {
            $valueStr .= '(' . $value .  '),';
        }
        $valueStr = trim($valueStr, ',');

        $updateArr = [
            'stg_sts_id=VALUES(stg_sts_id)',
            'stg_1x2=VALUES(stg_1x2)',
            'stg_handicap=VALUES(stg_handicap)',
            'stg_ou=VALUES(stg_ou)',
            'stg_ou_pg=VALUES(stg_ou_pg)',
            'stg_correct_score=VALUES(stg_correct_score)',
            'stg_parlay_1x2=VALUES(stg_parlay_1x2)',
            'stg_parlay_handicap=VALUES(stg_parlay_handicap)',
            'stg_parlay_ou=VALUES(stg_parlay_ou)',
            'stg_parlay_ou_pg=VALUES(stg_parlay_ou_pg)',
            'stg_parlay=VALUES(stg_parlay)',
            'stg_parlay_min=VALUES(stg_parlay_min)',
            'stg_parlay_max=VALUES(stg_parlay_max)',
            'stg_game_type=VALUES(stg_game_type)',
            'stg_master=VALUES(stg_master)',
            'stg_is_period=VALUES(stg_is_period)',
            'stg_is_show=VALUES(stg_is_show)',
            'stg_event_type=VALUES(stg_event_type)',
            'stg_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $gamesModel = Loader::model('SportsTennisGames');

        //执行sql
        $table = $gamesModel->getTable();
        $sql = "INSERT INTO {$table} VALUES {$valueStr} ON DUPLICATE KEY UPDATE {$update}";
        $ret = $gamesModel->execute($sql);
        if ($ret === false) {
            return false;
        }

        //返回insert id
        return $gamesModel->getLastInsID();
    }

    /**
     * 判断盘口是否存在，不存在则插入，存在更新
     * @param $valueArr 盘口信息
     * @return bool
     */
    public function checkInPlayNowGames($valueArr) {
        //组合values
        $valueStr = '';
        foreach($valueArr as $value) {
            $valueStr .= '(' . $value .  '),';
        }
        $valueStr = trim($valueStr, ',');

        $fieldArr = [
            'stg_game_id',
            'stg_sts_id',
            'stg_stm_id',
            'stg_home_id',
            'stg_guest_id',
            'stg_1x2',
            'stg_handicap',
            'stg_ou',
            'stg_ou_pg',
            'stg_parlay',
            'stg_game_type',
            'stg_master',
            'stg_is_period',
            'stg_is_show',
            'stg_event_type',
            'stg_create_time',
            'stg_modify_time',
        ];
        $field = implode(',', $fieldArr);

        $updateArr = [
            'stg_sts_id=VALUES(stg_sts_id)',
            'stg_stm_id=VALUES(stg_stm_id)',
            'stg_1x2=VALUES(stg_1x2)',
            'stg_handicap=VALUES(stg_handicap)',
            'stg_ou=VALUES(stg_ou)',
            'stg_ou_pg=VALUES(stg_ou_pg)',
            'stg_parlay=VALUES(stg_parlay)',
            'stg_game_type=VALUES(stg_game_type)',
            'stg_master=VALUES(stg_master)',
            'stg_is_period=VALUES(stg_is_period)',
            'stg_is_show=VALUES(stg_is_show)',
            'stg_event_type=VALUES(stg_event_type)',
            'stg_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $gamesModel = Loader::model('SportsTennisGames');

        //执行sql
        $table = $gamesModel->getTable();
        $sql = "INSERT INTO {$table} ({$field}) VALUES {$valueStr} ON DUPLICATE KEY UPDATE {$update}";
        $ret = $gamesModel->execute($sql);
        if ($ret === false) {
            return false;
        }

        //返回insert id
        return $gamesModel->getLastInsID();
    }

    /**
     * 组合insert的value
     * @param $params
     * @return mixed
     */
    public function getTodayAndEarlyValue($params) {
        //数组转换成json
        $params = $this->formatToJson($params);
        $isShow = Config::get('status.tennis_game_is_show')['yes'];

        $gameType = '';
        if (!empty($params['stg_ptype'])) {
            $gameType = Config::get('status.tennis_game_type_name')[$params['stg_ptype']];
            if (empty($gameType)) {
                Log::record(__METHOD__ .  ' new game type: ' . var_export($params['stg_ptype'], true), APP_LOG_TYPE);
            }
        }

        $valueArr = [
            $params['stg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['stg_1x2']}'",
            "'{$params['stg_handicap']}'",
            "'{$params['stg_ou']}'",
            "'{$params['stg_gm_ou']}'",
            "'{$params['stg_correct_score']}'",
            "'{$params['stg_parlay_1x2']}'",
            "'{$params['stg_parlay_handicap']}'",
            "'{$params['stg_parlay_ou']}'",
            "'{$params['stg_parlay_gm_ou']}'",
            $params['stg_parlay'],
            max(Config::get('common.parlay_count')['min'], $params['stg_minlimit']),
            min(Config::get('common.parlay_count')['max'], $params['stg_maxlimit']),
            "'{$gameType}'",
            $params['stg_ismaster'],
            $params['stg_is_period'],
            $isShow,
            $params['stg_date_type'],
            '\'' . date('Y-m-d H:i:s') . '\'',
            '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        return implode(',', $valueArr);
    }

    /**
     * 组合滚球insert的value
     * @param $params
     * @return mixed
     */
    public function getInPlayNowValue($params) {
        //数组转换成json
        $params = $this->formatToJson($params);
        $isShow = Config::get('status.tennis_game_is_show')['yes'];
        $parlay = Config::get('status.basketball_game_parlay')['no'];

        $gameType = '';
        if (!empty($params['stg_ptype'])) {
            $gameType = Config::get('status.tennis_game_type_name')[$params['stg_ptype']];
            if (empty($gameType)) {
                \think\Log::record(__METHOD__ .  ' new game type: ' . var_export($params['stg_ptype'], true), APP_LOG_TYPE);
            }
        }

        $valueArr = [
            $params['stg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['stg_1x2']}'",
            "'{$params['stg_handicap']}'",
            "'{$params['stg_ou']}'",
            "'{$params['stg_gm_ou']}'",
            $parlay,
            "'{$gameType}'",
            $params['stg_ismaster'],
            $params['stg_is_period'],
            $isShow,
            $params['stg_date_type'],
            '\'' . date('Y-m-d H:i:s') . '\'',
            '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        return implode(',', $valueArr);
    }

    /**
     * 数组转换成json
     * @param $params
     * @return mixed
     */
    public function formatToJson($params) {
        foreach($params as $key => $item) {
            if (is_array($item)) {
                $params[$key] = json_encode($item);
            }
        }
        return $params;
    }

    /**
     * 处理隐藏的盘口
     * @param $data
     * @param $type
     * @return bool
     */
    public function hideGame($type, $data) {
        $showGameIdArr = array_column($data, 'stg_id');
        $where = [
            'stg_event_type' => Config::get('status.tennis_game_event_type')[$type],
            'stg_is_show' => Config::get('status.tennis_game_is_show')['yes']
        ];
        $gameIdArr = Loader::model('SportsTennisGames')->where($where)->column('stg_game_id');

        $hideGameIdArr = array_diff($gameIdArr, $showGameIdArr);
        if ($hideGameIdArr) {
            $updateWhere = [
                'stg_game_id' => ['IN', $hideGameIdArr],
            ];
            $updateData = [
                'stg_is_show' => Config::get('status.tennis_game_is_show')['no']
            ];
            $ret = Loader::model('SportsTennisGames')->save($updateData, $updateWhere);
            if (false === $ret) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取盘口信息里的各个id
     * @param $gameId
     * @return mixed
     */
    public function getIdsInfoByGameId($gameId) {
        $cacheKey = Config::get('cache_option.prefix')['sports_collect'] . 'tennis_game_info:' . $gameId;
        $cache = Cache::get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $gameInfo = Loader::model('SportsTennisGames')
            ->where(['stg_game_id' => $gameId])
            ->column('stg_sts_id,stg_stm_id,stg_home_id,stg_guest_id', 'stg_game_id');
        if (!$gameInfo) {
            return false;
        }
        $gameInfo = array_values($gameInfo);
        Cache::set($cacheKey, $gameInfo[0]);
        return $gameInfo[0];
    }
}