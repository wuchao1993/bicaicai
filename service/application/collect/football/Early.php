<?php
/**
 * 获取早盘赛事数据
 * @createTime 2017/4/25 15:41
 */

namespace app\collect\football;

use think\Config;
use think\Loader;
use think\Model;

class Early extends Model {

    /**
     * 采集数据入库
     * @return bool
     */
    public function collect() {
        //获取采集数据
        $data = Loader::model('Football', 'service')->collectEarly();

        //优先处理已经关闭的盘口
        Loader::model('Games', 'football')->hideGame('early', $data);

        $teamsLogic     = Loader::model('Teams', 'football');
        $matchesLogic   = Loader::model('Matches', 'football');
        $schedulesLogic = Loader::model('Schedules', 'football');
        $gamesLogic     = Loader::model('Games', 'football');

        $flag = 0;
        $valueArr = [];
        $count = count($data);
        foreach($data as $item) {
            ++$flag;

            //球队入库
            $item['home_id'] = $teamsLogic->checkTeamByName($item['sfg_team_h']);
            if (false === $item['home_id']) {
                return false;
            }
            $item['guest_id'] = $teamsLogic->checkTeamByName($item['sfg_team_c']);
            if (false === $item['guest_id']) {
                return false;
            }

            //联赛入库
            $item['match_id'] = $matchesLogic->checkMatchByName($item['sfg_league']);
            if (false === $item['match_id']) {
                return false;
            }

            //对阵信息入库
            $item['schedule_id'] = $schedulesLogic->checkSchedule('early', $item);
            if (false === $item['schedule_id']) {
                return false;
            }

            //回写主盘口id
            if ($item['sfg_ismaster'] == Config::get('status.football_game_master')['yes']) {
                $schedulesLogic->updateScheduleMasterGameId($item['schedule_id'], $item['sfg_id']);
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