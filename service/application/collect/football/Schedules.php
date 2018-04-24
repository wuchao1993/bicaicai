<?php
/**
 * 对阵业务
 * @createTime 2017/4/26 11:43
 */

namespace app\collect\football;

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
        $date = substr(trim($params['sfg_datetime']), 0, 13);
        $uniqueId = $params['unique_id'] = md5(trim($params['sfg_team_h']) . trim($params['sfg_team_c']) . $date . $params['sfg_ptype']);
        if (isset($this->uniqueIdArr[$uniqueId])) {
            return $this->uniqueIdArr[$uniqueId];
        }

        $schedulesModel = Loader::model('SportsFootballSchedules');

        //不用ON DUPLICATE KEY UPDATE，自增id会跳空
        $scheduleInfo = $schedulesModel->where(['sfs_unique_id' => $uniqueId])->field('sfs_id')->find();
        if ($scheduleInfo) {
            if ($type == 'in_play_now') {
                $update = [
                    'sfs_sfm_id'       => $params['match_id'],
                    'sfs_home_score'   => $params['sfg_score_h'],
                    'sfs_guest_score'  => $params['sfg_score_c'],
                    'sfs_home_red'     => $params['sfg_redcard_h'],
                    'sfs_guest_red'    => $params['sfg_redcard_c'],
                    'sfs_latest_score' => $params['sfg_latest_score'] ?: 'N',
                    'sfs_retimeset'    => $params['sfg_retimeset'],
                    'sfs_timer'        => $params['sfg_timer'],
                    'sfs_begin_time'   => $params['sfg_datetime'],
                    'sfs_game_type'    => $params['sfg_ptype'],
                    'sfs_in_play_now'  => $params['sfg_running'],
                    'sfs_neutral'      => $params['sfg_neutral'],
                    'sfs_status'       => $params['sfg_play'],
                    'sfs_modify_time'  => date('Y-m-d H:i:s'),
                ];
            } elseif(in_array($type, ['today', 'early'])) {
                $update = [
                    'sfs_sfm_id'       => $params['match_id'],
                    'sfs_begin_time'   => $params['sfg_datetime'],
                    'sfs_game_type'    => $params['sfg_ptype'],
                    'sfs_in_play_now'  => $params['sfg_running'],
                    'sfs_neutral'      => $params['sfg_neutral'],
                    'sfs_status'       => $params['sfg_play'],
                    'sfs_modify_time'  => date('Y-m-d H:i:s'),
                ];
            }
            $ret = $schedulesModel->where(['sfs_unique_id' => $uniqueId])->update($update);
            if ($ret === false) {
                return false;
            }
            $scheduleId = $scheduleInfo->sfs_id;
        } else {
            $insert = [];
            if ($type == 'in_play_now') {
                $insert = [
                    'sfs_unique_id'    => $uniqueId,
                    'sfs_sfm_id'       => $params['match_id'],
                    'sfs_home_id'      => $params['home_id'],
                    'sfs_home_name'    => $params['sfg_team_h'],
                    'sfs_guest_name'   => $params['sfg_team_c'],
                    'sfs_guest_id'     => $params['guest_id'],
                    'sfs_home_score'   => $params['sfg_score_h'],
                    'sfs_guest_score'  => $params['sfg_score_c'],
                    'sfs_home_red'     => $params['sfg_redcard_h'],
                    'sfs_guest_red'    => $params['sfg_redcard_c'],
                    'sfs_latest_score' => $params['sfg_latest_score'] ?: 'N',
                    'sfs_retimeset'    => $params['sfg_retimeset'],
                    'sfs_timer'        => $params['sfg_timer'],
                    'sfs_begin_time'   => $params['sfg_datetime'],
                    'sfs_game_type'    => $params['sfg_ptype'],
                    'sfs_in_play_now'  => $params['sfg_running'],
                    'sfs_neutral'      => $params['sfg_neutral'],
                    'sfs_status'       => $params['sfg_play'],
                    'sfs_create_time'  => date('Y-m-d H:i:s'),
                    'sfs_modify_time'  => date('Y-m-d H:i:s'),
                ];
            } elseif(in_array($type, ['today', 'early'])) {
                $insert = [
                    'sfs_unique_id'    => $uniqueId,
                    'sfs_sfm_id'       => $params['match_id'],
                    'sfs_home_id'      => $params['home_id'],
                    'sfs_home_name'    => $params['sfg_team_h'],
                    'sfs_guest_name'   => $params['sfg_team_c'],
                    'sfs_guest_id'     => $params['guest_id'],
                    'sfs_begin_time'   => $params['sfg_datetime'],
                    'sfs_game_type'    => $params['sfg_ptype'],
                    'sfs_in_play_now'  => $params['sfg_running'],
                    'sfs_neutral'      => $params['sfg_neutral'],
                    'sfs_status'       => $params['sfg_play'],
                    'sfs_create_time'  => date('Y-m-d H:i:s'),
                    'sfs_modify_time'  => date('Y-m-d H:i:s'),
                ];
            }

            $scheduleId = $schedulesModel->insertGetId($insert);
            if (!$scheduleId) {
                return false;
            }

            //判断比赛延期或提前的逻辑
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
        $schedulesModel = Loader::model('SportsFootballSchedules');
        $table = $schedulesModel->getTable();
        $sql = "UPDATE {$table} SET `sfs_status`= CASE `sfs_id` ";
        $scheduleIdStr = '';
        foreach($data as $item) {
            $sql .= "WHEN {$item['schedule_id']} THEN '{$item['status']}' ";
            $scheduleIdStr .= $item['schedule_id'] . ',';
        }
        $scheduleIdStr = trim($scheduleIdStr, ',');
        $sql .= "END WHERE `sfs_id` IN ({$scheduleIdStr})";

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
        $gamesModel = Loader::model('SportsFootballGames');
        $gameInfo = $gamesModel->where(['sfg_game_id' => $params['sfg_id']])->field('sfg_sfs_id')->find();
        if (!$gameInfo) {
            return true;
        }

        //盘口已存在，判断是否同一只球队
        $schedulesModel = Loader::model('SportsFootballSchedules');
        $scheduleInfo = $schedulesModel->where(['sfs_id' => $gameInfo->sfg_sfs_id])->field('sfs_home_name,sfs_guest_name')->find();
        if ($scheduleInfo->sfs_home_name != $params['sfg_team_h'] || $scheduleInfo->sfs_guest_name != $params['sfg_team_c']) {
            return true;
        }

        //修改比赛为改期状态，同时记录下新的比赛ID
        $update = [
            'sfs_status' => Config::get('status.football_schedule_status')['game_rescheduled'],
            'sfs_new_id' => $newId
        ];
        $ret = $schedulesModel->where(['sfs_id' => $gameInfo->sfg_sfs_id])->update($update);

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
            'sfs_master_game_id' => $masterGameId
        ];
        $ret = Loader::model('SportsFootballSchedules')->where(['sfs_id' => $scheduleId])->update($update);

        return $ret === false ? false : true;
    }
}