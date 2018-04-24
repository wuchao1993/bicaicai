<?php
/**
 * 获取冠军赛事数据
 * @createTime 2017/5/01 16:00
 */

namespace app\collect\tennis;

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
        $data = Loader::model('Tennis', 'service')->collectOutright();

        //优先处理已经隐藏的盘口
        $this->hideGame($data);

        $matchesLogic = Loader::model('Matches', 'tennis');
        $valueArr = [];
        foreach($data as $item) {
            //联赛入库
            $item['match_id'] = $matchesLogic->checkMatchByName($item['stc_league']);
            if (false === $item['match_id']) {
                return false;
            }

            //100条数据的value一次insert
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
        if (is_array($params['stc_team_data'])) {
            $odds = json_encode($params['stc_team_data'], JSON_UNESCAPED_UNICODE);
        }

        $isShow = Config::get('status.tennis_outright_is_show')['yes'];
        $valueArr = [
            $params['stc_id'],
            $params['match_id'],
            "'{$params['stc_game_type']}'",
            "'{$odds}'",
            "'{$params['stc_datetime']}'",
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
            'sto_stm_id=VALUES(sto_stm_id)',
            'sto_game_type=VALUES(sto_game_type)',
            'sto_odds=VALUES(sto_odds)',
            'sto_end_time=VALUES(sto_end_time)',
            'sto_modify_time=' . '\'' . date('Y-m-d H:i:s') . '\'',
            'sto_is_show=VALUES(sto_is_show)'
        ];
        $update = implode(',', $updateArr);

        //组合field
        $fieldArr = [
            'sto_game_id',
            'sto_stm_id',
            'sto_game_type',
            'sto_odds',
            'sto_end_time',
            'sto_result',
            'sto_is_show',
            'sto_create_time',
            'sto_modify_time',
        ];
        $field = implode(',', $fieldArr);

        $outrightModel = Loader::model('SportsTennisOutright');

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
        $showGameIdArr = array_column($data, 'stc_id');
        $where = [
            'sto_is_show' => Config::get('status.tennis_outright_is_show')['yes']
        ];
        $gameIdArr = Loader::model('SportsTennisOutright')->where($where)->column('sto_game_id');

        $hideGameIdArr = array_diff($gameIdArr, $showGameIdArr);
        if ($hideGameIdArr) {
            $updateWhere = [
                'sto_game_id' => ['IN', $hideGameIdArr],
            ];
            $updateData = [
                'sto_is_show' => Config::get('status.tennis_outright_is_show')['no']
            ];
            $ret = Loader::model('SportsTennisOutright')->save($updateData, $updateWhere);
            if (false === $ret) {
                return false;
            }
        }
        return true;
    }
}