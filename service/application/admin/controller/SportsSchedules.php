<?php
/**
 * 比赛管理
 * @createTime 2017/6/23 15:33
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;

class SportsSchedules {

    /**
     * 对阵列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request) {
        $params['schedule_id']     = $request->param('scheduleId');
        $params['sport_type']      = $request->param('sportType');
        $params['clearing_status'] = $request->param('clearingStatus');
        $params['status']          = $request->param('status');
        $params['check_status']    = $request->param('checkStatus');
        $params['in_play_now']     = $request->param('inPlayNow');
        $params['start_time']      = $request->param('startTime');
        $params['page']            = $request->param('page');
        $params['page_size']       = $request->param('num');
        $params['end_time']        = $request->param('endTime');
        $params['team_name']       = $request->param('teamName');
        $scheduleLogic = Loader::model('SportsSchedules', 'logic');
        $data = $scheduleLogic->getList($params);

        return return_result($scheduleLogic->errorcode, output_format($data));
    }

    /**
     * 撤销未结算比赛订单
     * @param Request $request
     * @return array
     */
    public function cancel(Request $request) {
        $sportType  = $request->post('sportType');
        $scheduleId = $request->post('scheduleId');
        $remark     = $request->param('remark');
        $scheduleLogic = Loader::model('SportsSchedules', 'logic');
        $scheduleLogic->cancel($sportType, $scheduleId, $remark);

        return return_result($scheduleLogic->errorcode);
    }

    /**
     * 撤销结算
     * @param Request $request
     * @return array
     */
    public function cancelClearing(Request $request) {
        $sportType  = $request->post('sportType');
        $scheduleId = $request->post('scheduleId');
        $scheduleLogic = Loader::model('SportsSchedules', 'logic');
        $scheduleLogic->cancelClearing($sportType, $scheduleId);

        return return_result($scheduleLogic->errorcode);
    }

    /**
     * 人工结算
     * @param Request $request
     * @return array
     */
    public function clearing(Request $request) {
        $sportType  = $request->post('sportType');
        $scheduleId = $request->post('scheduleId');
        $scheduleLogic = Loader::model('SportsSchedules', 'logic');
        $scheduleLogic->clearing($sportType, $scheduleId);

        return return_result($scheduleLogic->errorcode);
    }

    /**
     * 修改销售状态：封盘，开盘
     * @param Request $request
     * @return array
     */
    public function updateSalesStatus(Request $request) {
        $sportType  = $request->post('sportType');
        $scheduleId = $request->post('scheduleId');
        $status     = $request->post('status');
        $scheduleLogic = Loader::model('SportsSchedules', 'logic');
        $scheduleLogic->updateSalesStatus($sportType, $scheduleId, $status);

        return return_result($scheduleLogic->errorcode);
    }

    /**
     * 获取赛果
     * @param Request $request
     * @return mixed
     */
    public function getResult(Request $request) {
        $sportType  = $request->param('sportType');
        $scheduleId = $request->param('scheduleId');

        $scheduleLogic = Loader::model('SportsSchedules', 'logic');
        $data = $scheduleLogic->getResult($sportType, $scheduleId);
        return return_result($scheduleLogic->errorcode, output_format($data));
    }

    /**
     * 手动获取赛果
     * @param Request $request
     * @return mixed
     */
    public function updateResult(Request $request) {
        $sportType = $request->param('sportType');
        $scheduleId = $request->param('scheduleId');

        $scheduleLogic = loader::model('SportsSchedules', 'logic');
        $scheduleLogic->updateResult($sportType, $scheduleId);
        return return_result($scheduleLogic->errorcode);
    }
}