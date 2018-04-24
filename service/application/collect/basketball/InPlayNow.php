<?php
/**
 * 获取滚球赛事数据
 * @createTime 2017/4/25 15:41
 */

namespace app\collect\basketball;

use think\Config;
use think\Loader;
use think\Model;

class InPlayNow extends Model {

    /**
     * 采集数据入库
     * @return bool
     */
    public function collect() {
        //获取采集数据
        $data = Loader::model('Basketball', 'service')->collectInPlayNow();

        //优先处理已经隐藏的盘口
        Loader::model('Games', 'basketball')->hideGame('in_play_now', $data);

        $teamsLogic     = Loader::model('Teams', 'basketball');
        $matchesLogic   = Loader::model('Matches', 'basketball');
        $schedulesLogic = Loader::model('Schedules', 'basketball');
        $gamesLogic     = Loader::model('Games', 'basketball');

        $flag = 0;
        $valueArr = [];
        $count = count($data);
        foreach($data as $item) {
            ++$flag;

            //球队入库
            $item['home_id'] = $teamsLogic->checkTeamByName($item['sbg_team_h']);
            if (false === $item['home_id']) {
                return false;
            }
            $item['guest_id'] = $teamsLogic->checkTeamByName($item['sbg_team_c']);
            if (false === $item['guest_id']) {
                return false;
            }

            //联赛入库
            $item['match_id'] = $matchesLogic->checkMatchByName($item['sbg_league']);
            if (false === $item['match_id']) {
                return false;
            }

            //对阵信息入库
            $item['schedule_id'] = $schedulesLogic->checkSchedule('in_play_now', $item);
            if (false === $item['schedule_id']) {
                return false;
            }

            //回写主盘口id
            if ($item['sbg_ismaster'] == Config::get('status.basketball_game_master')['yes']) {
                $schedulesLogic->updateScheduleMasterGameId($item['schedule_id'], $item['sbg_id']);
            }

            //100条数据的value一次insert
            $valueArr[] = $gamesLogic->getInPlayNowValue($item);
            if ($flag % 100 == 0 || $flag == $count) {
                $ret = $gamesLogic->checkInPlayNowGames($valueArr);
                $valueArr = [];
                if ($ret === false) {
                    return false;
                }
            }
        }

        return true;
    }
}