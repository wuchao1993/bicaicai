<?php
/**
 * 活动模型
 * @createTime 2017/4/4 10:25
 */

namespace app\common\model;

use think\Model;
use think\Config;
use think\Loader;

class SportsScheduleKeep extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'ssk_id';

    /***
     * @desc 判断该收藏是否存在
     * @param $scheduleParam
     * @return bool
     */
    public function getKeepInfo($scheduleParam){
        $condition["user_id"] = USER_ID;
        $condition["ssk_schedule_id"] = $scheduleParam['schedule_id'];
        $condition["st_id"] = $scheduleParam['st_id'];
        $result = $this->where($condition)->column("ssk_status");
        return $result[0];
    }

    /***
     * @desc 获取比赛未结束的赛事id
     * @param $param
     * @return mixed
     */
    public function getSskScheduleIds($param){
        $scheduleCondition['st_id'] = Config::get("sports.sport_types")[$param['sport']];
        $scheduleCondition['ssk_status'] = Config::get("sports.sport_keep_status")["enable"];
        $sskScheduleIds = $this->where($scheduleCondition)->column("ssk_schedule_id");
        if(!$sskScheduleIds){
            return false;
        }
        $tableName = "common/Sports".ucfirst($param["sport"])."Schedules";

        $fieldId = Config::get("sports.schedule_type")[$param['sport']]["id"];
        $fieldStatus = Config::get("sports.schedule_type")[$param['sport']]["status"];

        $scheduleWhere[$fieldId] = ['in',$sskScheduleIds];
        $scheduleWhere[$fieldStatus] = ['IN',[Config::get("sports.schedule_status")["middle_match"],
            Config::get("sports.schedule_status")["before_match"],
            Config::get("sports.schedule_status")["matching"],
        ]];

        $newSskScheduleIds = Loader::model($tableName)->where($scheduleWhere)->column($fieldId);
        return $newSskScheduleIds;

    }
}