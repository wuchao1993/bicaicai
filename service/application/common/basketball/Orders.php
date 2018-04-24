<?php
/**
 * 公共订单业务逻辑
 * @createTime 2017/5/10 14:17
 */

namespace app\common\basketball;

use think\Cache;
use think\Loader;
use think\Model;
use think\Db;
use think\Config;

class Orders extends \app\common\logic\Orders {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 获取订单信息
     * @param $betInfo
     * @return bool
     */
    public function getScheduleOrderInfo($betInfo) {
        $betInfo['match_name']      = '';
        $betInfo['home_name']       = '';
        $betInfo['guest_name']      = '';
        $betInfo['game_type']       = '';
        $betInfo['bet_info_string'] = '';
        $betInfo['play_type_name']  = Config::get('common.play_type')['basketball'][$betInfo['play_type']];

        $gameInfo = Loader::model('Games', 'basketball')->getInfoByGameId($betInfo['game_id'], '', true);
        if(!$gameInfo) {
            return $betInfo;
        }

        $matchInfo    = Loader::model('Matches', 'basketball')->getInfoById($gameInfo['sbg_sbm_id']);
        $scheduleInfo = Loader::model('Schedules', 'basketball')->getInfoById($gameInfo['sbg_sbs_id'], '', true);

        $betInfo['match_name']        = isset($matchInfo['sbm_name']) ? $matchInfo['sbm_name'] : '';
        $betInfo['home_name']         = isset($scheduleInfo['sbs_home_name']) ? $scheduleInfo['sbs_home_name'] : '';
        $betInfo['guest_name']        = isset($scheduleInfo['sbs_guest_name']) ? $scheduleInfo['sbs_guest_name'] : '';
        $betInfo['game_type']         = Config::get('status.basketball_game_type')[$gameInfo['sbg_game_type']];
        $betInfo['begin_time']        = $scheduleInfo['sbs_begin_time'];
        $betInfo['master_game_id']    = $scheduleInfo['sbs_master_game_id'];
        $betInfo['bet_schedule_time'] = $betInfo['quarter'] . ' ' . $betInfo['timer'];

        //处理每种玩法的下注信息
        $betInfo['bet_info_string'] = $this->handleBetInfoStr($betInfo);

        //让球玩法需要在球队后面加上让球数
        if ($betInfo['play_type'] == 'handicap') {
            if ($betInfo['strong'] == 'H') {
                $betInfo['home_name'] .= ' ' . $betInfo['ratio'];
            } elseif ($betInfo['strong'] == 'C') {
                $betInfo['guest_name'] .= ' ' . $betInfo['ratio'];
            }
        }

        return $betInfo;
    }

    /**
     * 获取冠军订单信息
     * @param $betInfo
     * @return bool
     */
    public function getOutrightOrderInfo($betInfo) {
        $betInfo['match_name']      = '';
        $betInfo['game_type']       = '';
        $betInfo['bet_info_string'] = '';
        $betInfo['result']          = '';
        $betInfo['play_type_name']  = Config::get('common.play_type')['basketball'][$betInfo['play_type']];

        $gameInfo = Loader::model('Outright', 'basketball')->getInfoByGameId($betInfo['game_id'], '', true);
        if(!$gameInfo) {
            return $betInfo;
        }

        $matchInfo = Loader::model('Matches', 'basketball')->getInfoById($gameInfo['sbo_sbm_id']);
        $betInfo['match_name'] = $matchInfo ? $matchInfo['sbm_name'] : '';
        $betInfo['game_type'] = $gameInfo['sbo_game_type'];

        //获取下注球队
        $odds = json_decode($gameInfo['sbo_odds'], true);
        $betInfo['bet_info_string'] = $odds[$betInfo['odds_key']]['team'];

        //赛果
        if ($gameInfo['sbo_result']) {
            $betInfo['result'] = json_decode($gameInfo['sbo_result'], true);
        }

        return $betInfo;
    }
}