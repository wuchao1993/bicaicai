<?php
/**
 * 大厅控制器
 * @createTime 2017/4/1 16:06
 */

namespace app\api\controller;

use think\Loader;
use think\Config;
use think\Request;

class Home {

    /**
     * 获取大厅首页公告列表
     * @return array
     */
    public function notice() {
        $noticeLogic = Loader::model('Notice', 'logic');
        $data = $noticeLogic->getHomeNotice();
        return [
            'errorcode' => $noticeLogic->errorcode,
            'message'   => Config::get('errorcode')[$noticeLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    /**
     * 获取大厅首页活动列表
     * @return array
     */
    public function activity() {
        $actLogic = Loader::model('Activity', 'logic');
        $data = $actLogic->getHomeActivity();
        return [
            'errorcode' => $actLogic->errorcode,
            'message'   => Config::get('errorcode')[$actLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    /**
     * 获取活动列表
     * @return array
     */
    public function activityList() {
        $actLogic = Loader::model('Activity', 'logic');
        $data = $actLogic->getActivity();
        return [
            'errorcode' => $actLogic->errorcode,
            'message'   => Config::get('errorcode')[$actLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    /**
     * 获取活动详情
     * @param Request $request
     * @return array
     */
    public function activityInfo(Request $request) {
        $id = $request->param('activityId');
        $actLogic = Loader::model('Activity', 'logic');
        $data = $actLogic->getActivityInfoById($id);
        return [
            'errorcode' => $actLogic->errorcode,
            'message'   => Config::get('errorcode')[$actLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    /**
     * 体育项目
     * @return array
     */
    public function sportsType() {
        $sportsLogic = Loader::model('SportsTypes', 'logic');
        $data = $sportsLogic->getHomeSportsType();
        return [
            'errorcode' => $sportsLogic->errorcode,
            'message'   => Config::get('errorcode')[$sportsLogic->errorcode],
            'data'      => output_format($data),
        ];
    }
}