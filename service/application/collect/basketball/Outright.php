<?php
/**
 * 获取冠军赛事数据
 * @createTime 2017/5/01 16:00
 */

namespace app\collect\basketball;

use think\Config;
use think\Loader;
use think\Model;

class Outright extends Model {

    /**
     * 采集数据入库
     * @return bool
     */
    public function collect() {
        //获取采集数据
        $data = Loader::model('Basketball', 'service')->collectOutright();

        //优先处理已经隐藏的盘口
        $this->hideGame($data);

        $matchesLogic = Loader::model('Matches', 'basketball');
        $valueArr = [];
        foreach($data as $item) {
            //联赛入库
            $item['match_id'] = $matchesLogic->checkMatchByName($item['sbc_league']);
            if (false === $item['match_id']) {
                return false;
            }
            $valueArr[] = $this->getValue($item);
        }
        $ret = $this->checkGames($valueArr);
        if ($ret === false) {
            return false;
        }

        return true;
    }

    /**
     * 组合value字符串
     * @param $params
     * @return mixed
     */
    public function getValue($params) {
        if (is_array($params['sbc_team_data'])) {
            $odds = json_encode($params['sbc_team_data'], JSON_UNESCAPED_UNICODE);
        }

        $isShow = Config::get('status.basketball_outright_is_show')['yes'];
        $valueArr = [
            $params['sbc_id'],
            $params['match_id'],
            "'{$params['sbc_game_type']}'",
            "'{$odds}'",
            "'{$params['sbc_datetime']}'",
            "''",
            $isShow,
            '\'' . date('Y-m-d H:i:s') . '\'',
            '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        return implode(',', $valueArr);
    }

    /**
     * 判断盘口是否存在，不存在则插入，存在更新
     * @param $valueArr 盘口信息
     * @return bool
     */
    public function checkGames($valueArr) {
        //组合values
        $valueStr = '';
        foreach($valueArr as $value) {
            $valueStr .= '(' . $value .  '),';
        }
        $valueStr = trim($valueStr, ',');

        //组合update
        $updateArr = [
            'sbo_sbm_id=VALUES(sbo_sbm_id)',
            'sbo_game_type=VALUES(sbo_game_type)',
            'sbo_odds=VALUES(sbo_odds)',
            'sbo_end_time=VALUES(sbo_end_time)',
            'sbo_is_show=VALUES(sbo_is_show)',
            'sbo_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
        ];
        $update = implode(',', $updateArr);

        //组合field
        $fieldArr = [
            'sbo_game_id',
            'sbo_sbm_id',
            'sbo_game_type',
            'sbo_odds',
            'sbo_end_time',
            'sbo_result',
            'sbo_is_show',
            'sbo_create_time',
            'sbo_modify_time',
        ];
        $field = implode(',', $fieldArr);

        $outrightModel = Loader::model('SportsBasketballOutright');

        //执行sql
        $table = $outrightModel->getTable();
        $sql = "INSERT INTO {$table} ({$field}) VALUES {$valueStr} ON DUPLICATE KEY UPDATE {$update}";
        $ret = $outrightModel->execute($sql);
        if ($ret === false) {
            return false;
        }

        //返回insert id
        return $outrightModel->getLastInsID();
    }

    /**
     * 处理隐藏的盘口
     * @param $data
     * @return bool
     */
    public function hideGame($data) {
        $showGameIdArr = array_column($data, 'sbc_id');
        $where = [
            'sbo_is_show' => Config::get('status.basketball_outright_is_show')['yes']
        ];
        $gameIdArr = Loader::model('SportsBasketballOutright')->where($where)->column('sbo_game_id');

        $hideGameIdArr = array_diff($gameIdArr, $showGameIdArr);
        if ($hideGameIdArr) {
            $updateWhere = [
                'sbo_game_id' => ['IN', $hideGameIdArr],
            ];
            $updateData = [
                'sbo_is_show' => Config::get('status.basketball_outright_is_show')['no']
            ];
            $ret = Loader::model('SportsBasketballOutright')->save($updateData, $updateWhere);
            if (false === $ret) {
                return false;
            }
        }
        return true;
    }
}