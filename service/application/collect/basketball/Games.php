<?php
/**
 * 足球盘口业务
 * @createTime 2017/4/27 15:25
 */

namespace app\collect\basketball;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class Games extends \app\common\basketball\Games {

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
            'sbg_sbs_id=VALUES(sbg_sbs_id)',
            'sbg_1x2=VALUES(sbg_1x2)',
            'sbg_handicap=VALUES(sbg_handicap)',
            'sbg_ou=VALUES(sbg_ou)',
            'sbg_ou_team=VALUES(sbg_ou_team)',
            'sbg_oe=VALUES(sbg_oe)',
            'sbg_parlay_1x2=VALUES(sbg_parlay_1x2)',
            'sbg_parlay_handicap=VALUES(sbg_parlay_handicap)',
            'sbg_parlay_ou=VALUES(sbg_parlay_ou)',
            'sbg_parlay_ou_team=VALUES(sbg_parlay_ou_team)',
            'sbg_parlay_oe=VALUES(sbg_parlay_oe)',
            'sbg_parlay=VALUES(sbg_parlay)',
            'sbg_parlay_min=VALUES(sbg_parlay_min)',
            'sbg_parlay_max=VALUES(sbg_parlay_max)',
            'sbg_game_type=VALUES(sbg_game_type)',
            'sbg_master=VALUES(sbg_master)',
            'sbg_is_period=VALUES(sbg_is_period)',
            'sbg_is_show=VALUES(sbg_is_show)',
            'sbg_event_type=VALUES(sbg_event_type)',
            'sbg_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $gamesModel = Loader::model('SportsBasketballGames');

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
            'sbg_game_id',
            'sbg_sbs_id',
            'sbg_sbm_id',
            'sbg_home_id',
            'sbg_guest_id',
            'sbg_1x2',
            'sbg_handicap',
            'sbg_ou',
            'sbg_ou_team',
            'sbg_oe',
            'sbg_parlay',
            'sbg_game_type',
            'sbg_master',
            'sbg_is_period',
            'sbg_is_show',
            'sbg_event_type',
            'sbg_create_time',
            'sbg_modify_time',
        ];
        $field = implode(',', $fieldArr);

        $updateArr = [
            'sbg_sbs_id=VALUES(sbg_sbs_id)',
            'sbg_sbm_id=VALUES(sbg_sbm_id)',
            'sbg_1x2=VALUES(sbg_1x2)',
            'sbg_handicap=VALUES(sbg_handicap)',
            'sbg_ou=VALUES(sbg_ou)',
            'sbg_ou_team=VALUES(sbg_ou_team)',
            'sbg_oe=VALUES(sbg_oe)',
            'sbg_parlay=VALUES(sbg_parlay)',
            'sbg_game_type=VALUES(sbg_game_type)',
            'sbg_master=VALUES(sbg_master)',
            'sbg_is_period=VALUES(sbg_is_period)',
            'sbg_is_show=VALUES(sbg_is_show)',
            'sbg_event_type=VALUES(sbg_event_type)',
            'sbg_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $gamesModel = Loader::model('SportsBasketballGames');

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
        
        $isShow = Config::get('status.basketball_game_is_show')['yes'];

        $gameType = '';
        if (!empty($params['sbg_ptype'])) {
            $gameType = Config::get('status.basketball_game_type_name')[$params['sbg_ptype']];
            if (empty($gameType)) {
                \think\Log::record(__METHOD__ .  ' new game type: ' . var_export($params['sbg_ptype'], true), APP_LOG_TYPE);
            }
        }

        empty($params['sbg_minlimit']) && $params['sbg_minlimit'] = 0;
        empty($params['sbg_maxlimit']) && $params['sbg_maxlimit'] = 0;

        $valueArr = [
            $params['sbg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['sbg_1x2']}'",
            "'{$params['sbg_handicap']}'",
            "'{$params['sbg_ou']}'",
            "'{$params['sbg_team_ou']}'",
            "'{$params['sbg_oe']}'",
            "'{$params['sbg_parlay_1x2']}'",
            "'{$params['sbg_parlay_handicap']}'",
            "'{$params['sbg_parlay_ou']}'",
            "'{$params['sbg_parlay_team_ou']}'",
            "'{$params['sbg_parlay_oe']}'",
            $params['sbg_parlay'],
            max(Config::get('common.parlay_count')['min'], $params['sbg_minlimit']),
            min(Config::get('common.parlay_count')['max'], $params['sbg_maxlimit']),
            "'{$gameType}'",
            $params['sbg_ismaster'],
            $params['sbg_is_period'],
            $isShow,
            $params['sbg_date_type'],
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
        $isShow = Config::get('status.basketball_game_is_show')['yes'];
        $parlay = Config::get('status.basketball_game_parlay')['no'];

        $gameType = '';
        if (!empty($params['sbg_ptype'])) {
            $gameType = Config::get('status.basketball_game_type_name')[$params['sbg_ptype']];
            if (empty($gameType)) {
                \think\Log::record(__METHOD__ .  ' new game type: ' . var_export($params['sbg_ptype'], true), APP_LOG_TYPE);
            }
        }

        $valueArr = [
            $params['sbg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['sbg_1x2']}'",
            "'{$params['sbg_handicap']}'",
            "'{$params['sbg_ou']}'",
            "'{$params['sbg_team_ou']}'",
            "'{$params['sbg_oe']}'",
            $parlay,
            "'{$gameType}'",
            $params['sbg_ismaster'],
            $params['sbg_is_period'],
            $isShow,
            $params['sbg_date_type'],
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
        $showGameIdArr = array_column($data, 'sbg_id');
        $where = [
            'sbg_event_type' => Config::get('status.basketball_game_event_type')[$type],
            'sbg_is_show' => Config::get('status.basketball_game_is_show')['yes']
        ];
        $gameIdArr = Loader::model('SportsBasketballGames')->where($where)->column('sbg_game_id');

        $hideGameIdArr = array_diff($gameIdArr, $showGameIdArr);
        if ($hideGameIdArr) {
            $updateWhere = [
                'sbg_game_id' => ['IN', $hideGameIdArr],
            ];
            $updateData = [
                'sbg_is_show' => Config::get('status.basketball_game_is_show')['no']
            ];
            $ret = Loader::model('SportsBasketballGames')->save($updateData, $updateWhere);
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
        $cacheKey = 'collect:basketball:' . __FUNCTION__ . '_' . $gameId;
        $cache = Cache::get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $gameInfo = Loader::model('SportsBasketballGames')
            ->where(['sbg_game_id' => $gameId])
            ->column('sbg_sbs_id,sbg_sbm_id,sbg_home_id,sbg_guest_id', 'sbg_game_id');
        if (!$gameInfo) {
            return false;
        }
        $gameInfo = array_values($gameInfo);
        Cache::set($cacheKey, $gameInfo[0]);
        return $gameInfo[0];
    }
}