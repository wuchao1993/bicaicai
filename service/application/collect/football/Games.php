<?php
/**
 * 足球盘口业务
 * @createTime 2017/4/27 15:25
 */

namespace app\collect\football;

use think\Config;
use think\Loader;
use think\Cache;

class Games extends \app\common\football\Games {

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
            'sfg_sfs_id=VALUES(sfg_sfs_id)',
            'sfg_ft_1x2=VALUES(sfg_ft_1x2)',
            'sfg_ft_handicap=VALUES(sfg_ft_handicap)',
            'sfg_ft_ou=VALUES(sfg_ft_ou)',
            'sfg_ft_oe=VALUES(sfg_ft_oe)',
            'sfg_ft_correct_score=VALUES(sfg_ft_correct_score)',
            'sfg_ft_total_goals=VALUES(sfg_ft_total_goals)',
            'sfg_ht_ft=VALUES(sfg_ht_ft)',
            'sfg_1h_1x2=VALUES(sfg_1h_1x2)',
            'sfg_1h_handicap=VALUES(sfg_1h_handicap)',
            'sfg_1h_ou=VALUES(sfg_1h_ou)',
            'sfg_1h_oe=VALUES(sfg_1h_oe)',
            'sfg_1h_correct_score=VALUES(sfg_1h_correct_score)',
            'sfg_1h_total_goals=VALUES(sfg_1h_total_goals)',
            'sfg_parlay_ft_1x2=VALUES(sfg_parlay_ft_1x2)',
            'sfg_parlay_ft_handicap=VALUES(sfg_parlay_ft_handicap)',
            'sfg_parlay_ft_ou=VALUES(sfg_parlay_ft_ou)',
            'sfg_parlay_ft_oe=VALUES(sfg_parlay_ft_oe)',
            'sfg_parlay_1h_1x2=VALUES(sfg_parlay_1h_1x2)',
            'sfg_parlay_1h_handicap=VALUES(sfg_parlay_1h_handicap)',
            'sfg_parlay_1h_ou=VALUES(sfg_parlay_1h_ou)',
            'sfg_parlay_1h_oe=VALUES(sfg_parlay_1h_oe)',
            'sfg_parlay=VALUES(sfg_parlay)',
            'sfg_parlay_min=VALUES(sfg_parlay_min)',
            'sfg_parlay_max=VALUES(sfg_parlay_max)',
            'sfg_game_type=VALUES(sfg_game_type)',
            'sfg_master=VALUES(sfg_master)',
            'sfg_important=VALUES(sfg_important)',
            'sfg_is_show=VALUES(sfg_is_show)',
            'sfg_event_type=VALUES(sfg_event_type)',
            'sfg_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $gamesModel = Loader::model('SportsFootballGames');

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
            'sfg_game_id',
            'sfg_sfs_id',
            'sfg_sfm_id',
            'sfg_home_id',
            'sfg_guest_id',
            'sfg_ft_1x2',
            'sfg_ft_handicap',
            'sfg_ft_ou',
            'sfg_ft_oe',
            'sfg_1h_1x2',
            'sfg_1h_handicap',
            'sfg_1h_ou',
            'sfg_parlay',
            'sfg_game_type',
            'sfg_master',
            'sfg_important',
            'sfg_is_show',
            'sfg_event_type',
            'sfg_create_time',
            'sfg_modify_time',
        ];
        $field = implode(',', $fieldArr);

        $updateArr = [
            'sfg_sfs_id=VALUES(sfg_sfs_id)',
            'sfg_sfm_id=VALUES(sfg_sfm_id)',
            'sfg_ft_1x2=VALUES(sfg_ft_1x2)',
            'sfg_ft_handicap=VALUES(sfg_ft_handicap)',
            'sfg_ft_ou=VALUES(sfg_ft_ou)',
            'sfg_ft_oe=VALUES(sfg_ft_oe)',
            'sfg_1h_1x2=VALUES(sfg_1h_1x2)',
            'sfg_1h_handicap=VALUES(sfg_1h_handicap)',
            'sfg_1h_ou=VALUES(sfg_1h_ou)',
            'sfg_parlay=VALUES(sfg_parlay)',
            'sfg_game_type=VALUES(sfg_game_type)',
            'sfg_master=VALUES(sfg_master)',
            'sfg_important=VALUES(sfg_important)',
            'sfg_is_show=VALUES(sfg_is_show)',
            'sfg_event_type=VALUES(sfg_event_type)',
            'sfg_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        $gamesModel = Loader::model('SportsFootballGames');

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
        
        $isShow = Config::get('status.football_game_is_show')['yes'];

        $valueArr = [
            $params['sfg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['sfg_ft_1x2']}'",
            "'{$params['sfg_ft_handicap']}'",
            "'{$params['sfg_ft_ou']}'",
            "'{$params['sfg_ft_oe']}'",
            "'{$params['sfg_ft_correct_score']}'",
            "'{$params['sfg_ft_total_goals']}'",
            "'{$params['sfg_ht_ft']}'",
            "'{$params['sfg_1h_1x2']}'",
            "'{$params['sfg_1h_handicap']}'",
            "'{$params['sfg_1h_ou']}'",
            "'{$params['sfg_1h_oe']}'",
            "'{$params['sfg_1h_correct_score']}'",
            "'{$params['sfg_1h_total_goals']}'",
            "'{$params['sfg_parlay_ft_1x2']}'",
            "'{$params['sfg_parlay_ft_handicap']}'",
            "'{$params['sfg_parlay_ft_ou']}'",
            "'{$params['sfg_parlay_ft_oe']}'",
            "'{$params['sfg_parlay_1h_1x2']}'",
            "'{$params['sfg_parlay_1h_handicap']}'",
            "'{$params['sfg_parlay_1h_ou']}'",
            "'{$params['sfg_parlay_1h_oe']}'",
            $params['sfg_parlay'],
            max(Config::get('common.parlay_count')['min'], $params['sfg_minlimit']),
            min(Config::get('common.parlay_count')['max'], $params['sfg_maxlimit']),
            "'{$params['sfg_ptype']}'",
            $params['sfg_ismaster'],
            $params['sfg_important'],
            $isShow,
            $params['sfg_date_type'],
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

        $params['sfg_parlay'] = Config::get('status.football_game_parlay')['no'];
        $isShow = Config::get('status.football_game_is_show')['yes'];
        $parlay = Config::get('status.football_game_parlay')['no'];

        $valueArr = [
            $params['sfg_id'],
            $params['schedule_id'],
            $params['match_id'],
            $params['home_id'],
            $params['guest_id'],
            "'{$params['sfg_ft_1x2']}'",
            "'{$params['sfg_ft_handicap']}'",
            "'{$params['sfg_ft_ou']}'",
            "'{$params['sfg_ft_oe']}'",
            "'{$params['sfg_1h_1x2']}'",
            "'{$params['sfg_1h_handicap']}'",
            "'{$params['sfg_1h_ou']}'",
            $parlay,
            "'{$params['sfg_ptype']}'",
            $params['sfg_ismaster'],
            $params['sfg_important'],
            $isShow,
            $params['sfg_date_type'],
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
        $showGameIdArr = array_column($data, 'sfg_id');
        $where = [
            'sfg_event_type' => Config::get('status.football_game_event_type')[$type],
            'sfg_is_show' => Config::get('status.football_game_is_show')['yes']
        ];
        $gameIdArr = Loader::model('SportsFootballGames')->where($where)->column('sfg_game_id');

        $hideGameIdArr = array_diff($gameIdArr, $showGameIdArr);
        if ($hideGameIdArr) {
            $updateWhere = [
                'sfg_game_id' => ['IN', $hideGameIdArr],
            ];
            $updateData = [
                'sfg_is_show' => Config::get('status.football_game_is_show')['no']
            ];
            $ret = Loader::model('SportsFootballGames')->save($updateData, $updateWhere);
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
        $cacheKey = Config::get('cache_option.prefix')['sports_collect'] . 'football_game_info:' . $gameId;
        $cache = Cache::get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $gameInfo = Loader::model('SportsFootballGames')
            ->where(['sfg_game_id' => $gameId])
            ->column('sfg_sfs_id,sfg_sfm_id,sfg_home_id,sfg_guest_id', 'sfg_game_id');
        if (!$gameInfo) {
            return false;
        }
        $gameInfo = array_values($gameInfo);
        Cache::set($cacheKey, $gameInfo[0]);
        return $gameInfo[0];
    }
}