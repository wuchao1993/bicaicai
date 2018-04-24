<?php
/**
 * 足球冠军业务
 * @createTime 2017/5/17 15:07
 */

namespace app\clearing\basketball;

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
            'sbo_clearing' => Config::get('status.basketball_outright_clearing')['no'],
            'sbo_result' => ['neq', ''],
        ];
        $ret = Loader::model('SportsBasketballOutright')
            ->where($where)
            ->column('sbo_game_id');
        return $ret ? $ret : false;
    }

    /**
     * 修改盘口的结算状态
     * @param $gameId
     * @return bool
     */
    public function updateOutrightClearing($gameId) {
        $update = [
            'sbo_clearing' => Config::get('status.basketball_outright_clearing')['yes'],
            'sbo_modify_time' => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('SportsBasketballOutright')
            ->where(['sbo_game_id' => $gameId])
            ->update($update);
        //TODO 日志
        return $ret ? $ret : false;
    }
}