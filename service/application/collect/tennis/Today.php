<?php
/**
 * 获取今日赛事数据
 * @createTime 2017/4/29 15:00
 */

namespace app\collect\tennis;

use think\Config;
use think\Loader;
use think\Model;

class Today extends Model {

    /**
     * 采集数据入库
     * @return bool
     */
    public function collect() {
        //获取采集数据
        $data = Loader::model('Tennis', 'service')->collectToday();

        //优先处理已经隐藏的盘口
        Loader::model('Games', 'tennis')->hideGame('today', $data);

        $teamsLogic     = Loader::model('Teams', 'tennis');
        $matchesLogic   = Loader::model('Matches', 'tennis');
        $schedulesLogic = Loader::model('Schedules', 'tennis');
        $gamesLogic     = Loader::model('Games', 'tennis');

        $flag = 0;
        $valueArr = [];
        $count = count($data);
        foreach($data as $item) {
            ++$flag;

            //这种盘口不要
            if ($item['stg_ismaster'] == 0 && $item['stg_ptype'] == '') {
                continue;
            }

            //球队入库
            $item['home_id'] = $teamsLogic->checkTeamByName($item['stg_team_h']);
            if (false === $item['home_id']) {
                return false;
            }
            $item['guest_id'] = $teamsLogic->checkTeamByName($item['stg_team_c']);
            if (false === $item['guest_id']) {
                return false;
            }

            //联赛入库
            $item['match_id'] = $matchesLogic->checkMatchByName($item['stg_league']);
            if (false === $item['match_id']) {
                return false;
            }

            //对阵信息入库
            $item['schedule_id'] = $schedulesLogic->checkSchedule('today', $item);
            if (false === $item['schedule_id']) {
                return false;
            }

            //回写主盘口id
            if ($item['stg_ismaster'] == Config::get('status.tennis_game_master')['yes']) {
                $schedulesLogic->updateScheduleMasterGameId($item['schedule_id'], $item['stg_id']);
            }

            //100条数据的value一次insert
            $valueArr[] = $gamesLogic->getTodayAndEarlyValue($item);
            if ($flag % 100 == 0 || $flag == $count) {
                $ret = $gamesLogic->checkTodayAndEarlyGames($valueArr);
                $valueArr = [];
                if ($ret === false) {
                    return false;
                }
            }
        }
        return true;
    }
}