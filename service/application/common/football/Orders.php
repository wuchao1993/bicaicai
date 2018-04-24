<?php
/**
 * 公共订单业务逻辑
 * @createTime 2017/5/10 14:17
 */

namespace app\common\football;

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
        $betInfo['play_type_name']  = Config::get('common.play_type')['football'][$betInfo['play_type']];

        $gameInfo = Loader::model('Games', 'football')->getInfoByGameId($betInfo['game_id'], '', true);
        if(!$gameInfo) {
            return $betInfo;
        }

        $matchInfo    = Loader::model('Matches', 'football')->getInfoById($gameInfo['sfg_sfm_id']);
        $scheduleInfo = Loader::model('Schedules', 'football')->getInfoById($gameInfo['sfg_sfs_id'], '', true);

        $betInfo['match_name']        = isset($matchInfo['sfm_name']) ? $matchInfo['sfm_name'] : '';
        $betInfo['home_name']         = isset($scheduleInfo['sfs_home_name']) ? $scheduleInfo['sfs_home_name'] : '';
        $betInfo['guest_name']        = isset($scheduleInfo['sfs_guest_name']) ? $scheduleInfo['sfs_guest_name'] : '';
        $betInfo['game_type']         = $gameInfo['sfg_game_type'];
        $betInfo['begin_time']        = $scheduleInfo['sfs_begin_time'];
        $betInfo['master_game_id']    = $scheduleInfo['sfs_master_game_id'];
        $betInfo['bet_schedule_time'] = $betInfo['timer'];

        //处理每种玩法的下注信息
        $betInfo['bet_info_string'] = $this->handleBetInfoStr($betInfo);

        //让球玩法需要在球队后面加上让球数
        if (false !== strpos($betInfo['play_type'], '_handicap')) {
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
        $betInfo['play_type_name']  = Config::get('common.play_type')['football'][$betInfo['play_type']];

        $gameInfo = Loader::model('Outright', 'football')->getInfoByGameId($betInfo['game_id'], '', true);
        if(!$gameInfo) {
            return $betInfo;
        }

        $matchInfo = Loader::model('Matches', 'football')->getInfoById($gameInfo['sfo_sfm_id']);
        $betInfo['match_name'] = $matchInfo ? $matchInfo['sfm_name'] : '';
        $betInfo['game_type'] = $gameInfo['sfo_game_type'];

        //获取下注球队
        $odds = json_decode($gameInfo['sfo_odds'], true);
        $betInfo['bet_info_string'] = $odds[$betInfo['odds_key']]['team'];

        //赛果
        if ($gameInfo['sfo_result']) {
            $betInfo['result'] = json_decode($gameInfo['sfo_result'], true);
        }

        return $betInfo;
    }
}