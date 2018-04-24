<?php
/**
 * 对阵业务
 * @createTime 2017/4/26 11:43
 */

namespace app\collect\tennis;

use think\Config;
use think\Loader;
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
        $date = substr(trim($params['stg_datetime']), 0, 13);
        $uniqueId = $params['unique_id'] = md5(trim($params['stg_team_h']) . trim($params['stg_team_c']) . $date);
        if (isset($this->uniqueIdArr[$uniqueId])) {
            return $this->uniqueIdArr[$uniqueId];
        }

        $nowServer = [
            '0' => 'c', //客队发球
            '1' => 'h', //主队发球
            '2' => ''   //无人发球
        ];
        $showDelay = [
            'Y' => 'yes',
            'N' => 'no',
        ];

        $schedulesModel = Loader::model('SportsTennisSchedules');

        //不用ON DUPLICATE KEY UPDATE，自增id会跳空
        $scheduleInfo = $schedulesModel->where(['sts_unique_id' => $uniqueId])->field('sts_id')->find();
        if ($scheduleInfo) {
            if ($type == 'in_play_now') {
                $params['stg_nowserver'] = $nowServer[$params['stg_nowserver']];
                $update = [
                    'sts_stm_id'            => $params['match_id'],
                    'sts_home_game_score'   => $params['stg_scoregameh'],
                    'sts_guest_game_score'  => $params['stg_scoregamec'],
                    'sts_home_set_score'    => $params['stg_scoreseth'],
                    'sts_guest_set_score'   => $params['stg_scoresetc'],
                    'sts_home_point_score'  => $params['stg_scorepointh'],
                    'sts_guest_point_score' => $params['stg_scorepointc'],
                    'sts_home_score_1st'    => $params['stg_1st_h'],
                    'sts_guest_score_1st'   => $params['stg_1st_c'],
                    'sts_home_score_2nd'    => $params['stg_2nd_h'],
                    'sts_guest_score_2nd'   => $params['stg_2nd_c'],
                    'sts_home_score_3rd'    => $params['stg_3rd_h'],
                    'sts_guest_score_3rd'   => $params['stg_3rd_c'],
                    'sts_home_score_4th'    => $params['stg_4th_h'],
                    'sts_guest_score_4th'   => $params['stg_4th_c'],
                    'sts_home_score_5th'    => $params['stg_5th_h'],
                    'sts_guest_score_5th'   => $params['stg_5th_c'],
                    'sts_now_server'        => $params['stg_nowserver'],
                    'sts_timer'             => $params['stg_timer'],
                    'sts_best'              => $params['stg_best'],
                    'sts_show_delay'        => Config::get('status.tennis_schedule_show_delay')[$showDelay[$params['stg_showdelay']]],
                    'sts_begin_time'        => $params['stg_datetime'],
                    'sts_in_play_now'       => $params['stg_running'],
                    'sts_status'            => $params['stg_play'],
                    'sts_modify_time'       => date('Y-m-d H:i:s'),
                ];
            } elseif(in_array($type, ['today', 'early'])) {
                $update = [
                    'sts_stm_id'      => $params['match_id'],
                    'sts_begin_time'  => $params['stg_datetime'],
                    'sts_in_play_now' => $params['stg_running'],
                    'sts_best'        => $params['stg_best'],
                    'sts_status'      => $params['stg_play'],
                    'sts_modify_time' => date('Y-m-d H:i:s'),
                ];
            }
            $ret = $schedulesModel->where(['sts_unique_id' => $uniqueId])->update($update);
            if ($ret === false) {
                return false;
            }
            $scheduleId = $scheduleInfo->sts_id;
        } else {
            $insert = [];
            if ($type == 'in_play_now') {
                $params['stg_nowserver'] = $nowServer[$params['stg_nowserver']];
                $insert = [
                    'sts_unique_id'         => $uniqueId,
                    'sts_stm_id'            => $params['match_id'],
                    'sts_home_id'           => $params['home_id'],
                    'sts_guest_id'          => $params['guest_id'],
                    'sts_home_name'         => $params['stg_team_h'],
                    'sts_guest_name'        => $params['stg_team_c'],
                    'sts_home_game_score'   => $params['stg_scoregameh'],
                    'sts_guest_game_score'  => $params['stg_scoregamec'],
                    'sts_home_set_score'    => $params['stg_scoreseth'],
                    'sts_guest_set_score'   => $params['stg_scoresetc'],
                    'sts_home_point_score'  => $params['stg_scorepointh'],
                    'sts_guest_point_score' => $params['stg_scorepointc'],
                    'sts_home_score_1st'    => $params['stg_1st_h'],
                    'sts_guest_score_1st'   => $params['stg_1st_c'],
                    'sts_home_score_2nd'    => $params['stg_2nd_h'],
                    'sts_guest_score_2nd'   => $params['stg_2nd_c'],
                    'sts_home_score_3rd'    => $params['stg_3rd_h'],
                    'sts_guest_score_3rd'   => $params['stg_3rd_c'],
                    'sts_home_score_4th'    => $params['stg_4th_h'],
                    'sts_guest_score_4th'   => $params['stg_4th_c'],
                    'sts_home_score_5th'    => $params['stg_5th_h'],
                    'sts_guest_score_5th'   => $params['stg_5th_c'],
                    'sts_now_server'        => $params['stg_nowserver'],
                    'sts_timer'             => $params['stg_timer'],
                    'sts_best'              => $params['stg_best'],
                    'sts_show_delay'        => Config::get('status.tennis_schedule_show_delay')[$showDelay[$params['stg_showdelay']]],
                    'sts_begin_time'        => $params['stg_datetime'],
                    'sts_in_play_now'       => $params['stg_running'],
                    'sts_status'            => $params['stg_play'],
                    'sts_create_time'       => date('Y-m-d H:i:s'),
                    'sts_modify_time'       => date('Y-m-d H:i:s'),
                ];
            } elseif(in_array($type, ['today', 'early'])) {
                $insert = [
                    'sts_unique_id'   => $uniqueId,
                    'sts_stm_id'      => $params['match_id'],
                    'sts_home_id'     => $params['home_id'],
                    'sts_guest_id'    => $params['guest_id'],
                    'sts_home_name'   => $params['stg_team_h'],
                    'sts_guest_name'  => $params['stg_team_c'],
                    'sts_begin_time'  => $params['stg_datetime'],
                    'sts_in_play_now' => $params['stg_running'],
                    'sts_status'      => $params['stg_play'],
                    'sts_best'        => $params['stg_best'],
                    'sts_create_time' => date('Y-m-d H:i:s'),
                    'sts_modify_time' => date('Y-m-d H:i:s'),
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
     * 更新比赛结束状态
     * @param $data
     * @return bool
     */
    public function updateStatus($data) {
        $schedulesModel = Loader::model('SportsTennisSchedules');
        $table = $schedulesModel->getTable();
        $sql = "UPDATE {$table} SET `sts_status`= CASE `sts_id` ";
        $scheduleIdStr = '';
        foreach($data as $item) {
            $sql .= "WHEN {$item['schedule_id']} THEN '{$item['status']}' ";
            $scheduleIdStr .= $item['schedule_id'] . ',';
        }
        $scheduleIdStr = trim($scheduleIdStr, ',');
        $sql .= "END WHERE `sts_id` IN ({$scheduleIdStr})";

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
        $gamesModel = Loader::model('SportsTennisGames');
        $gameInfo = $gamesModel->where(['stg_game_id' => $params['stg_id']])->field('stg_sts_id')->find();
        if (!$gameInfo) {
            return true;
        }

        //盘口已存在，判断是否同一只球队
        $schedulesModel = Loader::model('SportsTennisSchedules');
        $scheduleInfo = $schedulesModel->where(['sts_id' => $gameInfo->stg_sts_id])->field('sts_home_name,sts_guest_name')->find();
        if ($scheduleInfo->sts_home_name != $params['stg_team_h'] || $scheduleInfo->sts_guest_name != $params['stg_team_c']) {
            return true;
        }

        //修改比赛为改期状态，同时记录下新的比赛ID
        $update = [
            'sts_status' => Config::get('status.tennis_schedule_status')['game_rescheduled'],
            'sts_new_id' => $newId
        ];
        $ret = $schedulesModel->where(['sts_id' => $gameInfo->stg_sts_id])->update($update);

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
            'sts_master_game_id' => $masterGameId
        ];
        $ret = Loader::model('SportsTennisSchedules')->where(['sts_id' => $scheduleId])->update($update);

        return $ret === false ? false : true;
    }
}