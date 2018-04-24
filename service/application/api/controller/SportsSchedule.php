<?php
namespace app\api\controller;

use think\Loader;
use think\Request;
use think\Hook;

class SportsSchedule{
    /***
     * @desc 新增，取消收藏信息
     * @param Request $request
     * @return array
     */
    public function keepSchedule(Request $request){
        Hook::listen("auth_check");
        $sportScheduleLogic = Loader::model("SportsSchedule","Logic");
        $sportSchedule = $sportScheduleLogic->addSportsSchedules($request->param());
        return send_response($sportSchedule,$sportScheduleLogic->errorcode);
    }

    /***
     * @desc 获取收藏信息
     * @param Request $request
     * @return array
     */
    public function getKeepInfo(Request $request){
        Hook::listen("auth_check");
        $sportScheduleLogic = Loader::model("SportsSchedule","logic");
        $keepInfo = $sportScheduleLogic->getKeepInfo($request->param());
        return send_response($keepInfo,$sportScheduleLogic->errorcode);
    }

}