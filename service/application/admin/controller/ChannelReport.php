<?php

/**
 * 报表控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class ChannelReport {

    /**
     * 代理报表
     *
     * @param Request $request
     * @return array
     */
    public function getChannelReportList(Request $request) {
        // $params ['page'] = $request->param('page', 1);
        // $params ['num']  = $request->param('num', 10);
        // $params ['channelName']  = $request->param('channelName');

        //会员注册时间
        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        } else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }
        $reportLogic = Loader::model('ChannelReport', 'logic');
        $reportList  = $reportLogic->getChannelReportList($params);

        foreach($reportList ['list'] as &$info) {
            $info = $this->_packChannelReportList($info);
        }

        return [
            'errorcode' => $reportLogic->errorcode,
            'message'   => Config::get('errorcode') [$reportLogic->errorcode],
            'data'      => output_format($reportList)
        ];
    }

    private function _packChannelReportList($info) {
        return [
             'id'                   => $info ['id'],
            'channelName'                  => $info ['channel_name'],
            'channelCreateTime'              => $info ['create_time'],
            'regPersons'             => $info ['reg_persons'],
        ];
    }

}
