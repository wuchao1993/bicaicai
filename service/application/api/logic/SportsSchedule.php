<?php

namespace app\api\logic;

use think\Config;
use think\Loader;


class SportsSchedule
{

    /**
     * 定义主键
     * @var string
     */
    public $errorcode = EC_SUCCESS;

    /***
     * @desc 添加或新增收藏记录
     * @param $param
     */
    public function addSportsSchedules($param)
    {

        $stTypeId = Config::get("sports.sport_types")[$param['sport']];
        $scheduleParam['schedule_id'] = $param['scheduleId'];
        $scheduleParam['st_id'] = $stTypeId;
        $scheduleStatus = Loader::model("common/SportsScheduleKeep")->getKeepInfo($scheduleParam);

        $sportsScheduleKeepData["ssk_modifytime"] = current_datetime();
        if ($param['operate'] == 1) {
            if($scheduleStatus == Config::get('sports.sport_keep_status')['enable']){
                $this->errorcode = EC_USER_FAVORITE_MATCHES_ADD_EXISTS_ERROR;
                return false;
            }
        } else if ($param['operate'] == 2) {
            if($scheduleStatus == Config::get('sports.sport_keep_status')['disable']){
                $this->errorcode = EC_USER_FAVORITE_MATCHES_CANCEL_EXISTS_ERROR;
                return false;
            }
        }
        
        $sportsScheduleKeepData['ssk_status'] = $param['operate'];
        if ($scheduleStatus) {
            $condition["ssk_schedule_id"] = $param['scheduleId'];
            $condition["user_id"] = USER_ID;
            $condition["st_id"] = $stTypeId;
            $flag = Loader::model("common/SportsScheduleKeep")->where($condition)->update($sportsScheduleKeepData);
            if ($flag === false) {
                $this->errorcode = EC_USER_FAVORITE_MATCHES_CHANGLE_EXISTS_ERROR;
            }
        } else {
            $sportsScheduleKeepData['user_id'] = USER_ID;
            $sportsScheduleKeepData['ssk_createtime'] = current_datetime();
            $sportsScheduleKeepData['st_id'] = $stTypeId;
            $sportsScheduleKeepData['ssk_schedule_id'] = $param['scheduleId'];
            $flag = Loader::model("common/SportsScheduleKeep")->save($sportsScheduleKeepData);
            if ($flag === false) {
                $this->errorcode = EC_USER_FAVORITE_MATCHES_ADD_ERROR;
            }
        }
        return $flag;
    }

    /***
     * @desc 获取收藏信息
     * @param $param
     * @return mixed
     */
    public function getKeepInfo($param)
    {
        $stIds = Loader::model("common/SportsScheduleKeep")->getSskScheduleIds($param);
        $condition['ssk_schedule_id'] = ["in", $stIds];
        $condition['user_id'] = USER_ID;
        $condition['st_id'] = Config::get("sports.sport_types")[$param['sport']];
        $condition['ssk_status'] = Config::get("sports.sport_keep_status")["enable"];
        $sportsScheduleCount['count'] = Loader::model("common/SportsScheduleKeep")->where($condition)->count();
        return $sportsScheduleCount;
    }


}