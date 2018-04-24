<?php
/**
 * 足球冠军业务
 * @createTime 2017/5/17 15:07
 */

namespace app\clearing\tennis;

use think\Config;
use think\Loader;
use think\Model;

class Outright extends Model {

    /**
     * 返回已出赛果并且未算奖的盘口id
     * @return bool
     */
    public function getNoClearing() {
        $where = [
            'sto_clearing' => Config::get('status.tennis_outright_clearing')['no'],
            'sto_result' => ['neq', ''],
        ];
        $ret = Loader::model('SportsTennisOutright')
            ->where($where)
            ->column('sto_game_id');
        return $ret ? $ret : false;
    }

    /**
     * 修改盘口的结算状态
     * @param $gameId
     * @return bool
     */
    public function updateOutrightClearing($gameId) {
        $update = [
            'sto_clearing' => Config::get('status.tennis_outright_clearing')['yes'],
            'sto_modify_time' => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('SportsTennisOutright')
            ->where(['sto_game_id' => $gameId])
            ->update($update);
        //TODO 日志
        return $ret ? $ret : false;
    }
}