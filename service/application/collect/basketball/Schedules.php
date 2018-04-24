<?php
/**
 * 对阵业务
 * @createTime 2017/4/26 11:43
 */

namespace app\collect\basketball;

use think\Config;
use think\Loader;
use think\Log;
use think\Model;

class Schedules extends Model {

    public $uniqueIdArr = [];

    /**
     * 判断对阵是否存在，不存在则插入，存在更新
     * @param $type 赛事类型
     * @param $params 盘口信息
     * @return bool
     */
    public function checkSchedule($type, $params) {
        //生成对阵的uniqueId
        //如果出现以下两种情况会有问题
        //1. 这两支球队在同一天踢了不止一场比赛
        //2. 跨天变更比赛时间的情况通过下面checkScheduleDelay方法解决
        //只截取年月日小时，是为了比赛在小时级别的延迟或提前后还是返回同一场比赛
        $date = substr(trim($params['sbg_datetime']), 0, 13);
        $uniqueId = $params['unique_id'] = md5(trim($params['sbg_league']) . trim($params['sbg_team_h']) . trim($params['sbg_team_c']) . $date);
        Log::record(__METHOD__ .  ' unique_id: ' . $params['sbg_team_h'] . '|' . $params['sbg_team_c'] . '|' . $date, APP_LOG_TYPE);
        if (isset($this->uniqueIdArr[$uniqueId])) {
            return $this->uniqueIdArr[$uniqueId];
        }

        $quarter = [
            'Q1' => '第一节',
            'Q2' => '第二节',
            'Q3' => '第三节',
            'Q4' => '第四节',
            'HT' => '半场',
            'H1' => '上半场',
            'H2' => '下半场',
            'OT' => '加时',
        ];
        if (isset($params['sbg_nowsession'])) {
            $params['sbg_nowsession'] = $quarter[$params['sbg_nowsession']];
        }

        $schedulesModel = Loader::model('SportsBasketballSchedules');

        //不用ON DUPLICATE KEY UPDATE，自增id会跳空
        $scheduleInfo = $schedulesModel->where(['sbs_unique_id' => $uniqueId])->field('sbs_id')->find();
        if ($scheduleInfo) {
            if ($type == 'in_play_now') {
                $update = [
                    'sbs_sbm_id'       => $params['match_id'],
                    'sbs_home_score'   => $params['sbg_score_h'],
                    'sbs_guest_score'  => $params['sbg_score_c'],
                    'sbs_quarter'      => $params['sbg_nowsession'],
                    'sbs_timer'        => gmstrftime('%M:%S', $params['sbg_lasttime']),
                    'sbs_begin_time'   => $params['sbg_datetime'],
                    'sbs_in_play_now'  => $params['sbg_running'],
                    'sbs_status'       => $params['sbg_play'],
                    'sbs_modify_time'  => date('Y-m-d H:i:s'),
                ];
            } elseif(in_array($type, ['today', 'early'])) {
                $update = [
                    'sbs_sbm_id'       => $params['match_id'],
                    'sbs_begin_time'   => $params['sbg_datetime'],
                    'sbs_in_play_now'  => $params['sbg_running'],
                    'sbs_status'       => $params['sbg_play'],
                    'sbs_modify_time'  => date('Y-m-d H:i:s'),
                ];
            }
            $ret = $schedulesModel->where(['sbs_unique_id' => $uniqueId])->update($update);
            if ($ret === false) {
                return false;
            }
            $scheduleId = $scheduleInfo->sbs_id;
        } else {
            $insert = [];
            if ($type == 'in_play_now') {
                $insert = [
                    'sbs_unique_id'    => $uniqueId,
                    'sbs_sbm_id'       => $params['match_id'],
                    'sbs_home_id'      => $params['home_id'],
                    'sbs_guest_id'     => $params['guest_id'],
                    'sbs_home_name'    => $params['sbg_team_h'],
                    'sbs_guest_name'   => $params['sbg_team_c'],
                    'sbs_home_score'   => $params['sbg_score_h'],
                    'sbs_guest_score'  => $params['sbg_score_c'],
                    'sbs_quarter'      => $params['sbg_nowsession'],
                    'sbs_timer'        => gmstrftime('%M:%S', $params['sbg_lasttime']),
                    'sbs_begin_time'   => $params['sbg_datetime'],
                    'sbs_in_play_now'  => $params['sbg_running'],
                    'sbs_status'       => $params['sbg_play'],
                    'sbs_create_time'  => date('Y-m-d H:i:s'),
                    'sbs_modify_time'  => date('Y-m-d H:i:s'),
                ];
            } elseif(in_array($type, ['today', 'early'])) {
                $insert = [
                    'sbs_unique_id'    => $uniqueId,
                    'sbs_sbm_id'       => $params['match_id'],
                    'sbs_home_id'      => $params['home_id'],
                    'sbs_guest_id'     => $params['guest_id'],
                    'sbs_home_name'    => $params['sbg_team_h'],
                    'sbs_guest_name'   => $params['sbg_team_c'],
                    'sbs_begin_time'   => $params['sbg_datetime'],
                    'sbs_in_play_now'  => $params['sbg_running'],
                    'sbs_status'       => $params['sbg_play'],
                    'sbs_create_time'  => date('Y-m-d H:i:s'),
                    'sbs_modify_time'  => date('Y-m-d H:i:s'),
                ];
            }

            $scheduleId = $schedulesModel->insertGetId($insert);
            if (!$scheduleId) {
                return false;
            }

            //判断比赛延期的逻辑
            $this->checkScheduleDelay($scheduleId, $params);
        }

        //返回id
        $this->uniqueIdArr[$uniqueId] = $scheduleId;
        return $scheduleId;
    }

    /**
     * 更新对阵状态
     * @param $data
     * @return bool
     */
    public function updateStatus($data) {
        $schedulesModel = Loader::model('SportsBasketballSchedules');
        $table = $schedulesModel->getTable();
        $sql = "UPDATE {$table} SET `sbs_status`= CASE `sbs_id` ";
        $scheduleIdStr = '';
        foreach($data as $item) {
            $sql .= "WHEN {$item['schedule_id']} THEN '{$item['status']}' ";
            $scheduleIdStr .= $item['schedule_id'] . ',';
        }
        $scheduleIdStr = trim($scheduleIdStr, ',');
        $sql .= "END WHERE `sbs_id` IN ({$scheduleIdStr})";

        $ret = $schedulesModel->execute($sql);
        if ($ret === false) {
            return false;
        }
        return true;
    }

    /**
     * 判断比赛是否改期
     * @param $newId 新的比赛id
     * @param $params 盘口数据
     * @return bool
     */
    public function checkScheduleDelay($newId, $params) {
        //盘口是否存在
        $gamesModel = Loader::model('SportsBasketballGames');
        $gameInfo = $gamesModel->where(['sbg_game_id' => $params['sbg_id']])->field('sbg_sbs_id')->find();
        if (!$gameInfo) {
            return true;
        }

        //盘口已存在，判断是否同一只球队
        $schedulesModel = Loader::model('SportsBasketballSchedules');
        $scheduleInfo = $schedulesModel->where(['sbs_id' => $gameInfo->sbg_sbs_id])->field('sbs_home_name,sbs_guest_name')->find();
        if ($scheduleInfo->sbs_home_name != $params['sbg_team_h'] || $scheduleInfo->sbs_guest_name != $params['sbg_team_c']) {
            return true;
        }

        //修改比赛为改期状态，同时记录下新的比赛ID
        $update = [
            'sbs_status' => Config::get('status.basketball_schedule_status')['game_rescheduled'],
            'sbs_new_id' => $newId
        ];
        $ret = $schedulesModel->where(['sbs_id' => $gameInfo->sbg_sbs_id])->update($update);

        return $ret === false ? false : true;
    }

    /**
     * 修改比赛的主盘口ID
     * @param $scheduleId 比赛ID
     * @param $masterGameId 主盘口ID
     * @return bool
     */
    public function updateScheduleMasterGameId($scheduleId, $masterGameId) {
        $update = [
            'sbs_master_game_id' => $masterGameId
        ];
        $ret = Loader::model('SportsBasketballSchedules')->where(['sbs_id' => $scheduleId])->update($update);

        return $ret === false ? false : true;
    }
}