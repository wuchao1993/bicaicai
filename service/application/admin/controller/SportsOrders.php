<?php
/**
 * 体育订单
 * @createTime 2017/6/20 16:33
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class SportsOrders {

    /**
     * 注单列表
     * @param Request $request
     * @return array
     */
    public function getList(Request $request) {
        $params['event_type']  = $request->param('eventType');
        $params['start_time']  = $request->param('startTime');
        $params['end_time']    = $request->param('endTime');
        $params['sport_id']    = $request->param('sportId');
        $params['schedule_id'] = $request->param('scheduleId');
        $params['status']      = $request->param('status');
        $params['user_name']   = $request->param('userName');
        $params['order_no']    = $request->param('orderNo');
        $params['order_by']    = $request->param('orderBy');
        $params['page']        = $request->param('page');
        $params['page_size']   = $request->param('num');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $data = $ordersLogic->getList($params);

        return return_result($ordersLogic->errorcode, output_format($data));
    }

    /**
     * 注单详情
     * @param Request $request
     * @return array
     */
    public function info(Request $request) {
        $orderNo     = $request->param('orderNo');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $data = $ordersLogic->getInfoByOrderNo($orderNo);

        return return_result($ordersLogic->errorcode, output_format($data));
    }

    /**
     * 获取筛选用的订单状态
     * @return array
     */
    public function getStatus() {
        $status = Config::get('common.order_status_name');
        return return_result(EC_SUCCESS, $status);
    }

    /**
     * 获取筛选用的赛事类型
     * @return array
     */
    public function getEventsType() {
        $status = Config::get('common.events_type');
        $status['outright'] = '冠军';
        return return_result(EC_SUCCESS, $status);
    }

    /**
     * 未结算订单撤单
     * @param Request $request
     * @return array
     */
    public function cancel(Request $request) {
        $orderNo     = $request->param('orderNo');
        $remark      = $request->param('remark');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $ordersLogic->cancel($orderNo, $remark);

        return return_result($ordersLogic->errorcode);
    }

    /**
     * 结算
     * @param Request $request
     * @return array
     */
    public function clearing(Request $request) {
        $orderNo     = $request->param('orderNo');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $ordersLogic->clearing($orderNo);

        return return_result($ordersLogic->errorcode);
    }

    /**
     * 撤销结算
     * @param Request $request
     * @return mixed
     */
    public function cancelClearing(Request $request) {
        $orderNo = $request->param('orderNo');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $ordersLogic->cancelClearing($orderNo);

        return return_result($ordersLogic->errorcode);
    }

    /**
     * 未审核注单列表
     * @param Request $request
     * @return mixed
     */
    public function getUncheckedList(Request $request) {
        $params['check_status'] = 'wait';
        $params['page']         = $request->param('page');
        $params['page_size']    = $request->param('num');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $data = $ordersLogic->getList($params);

        return return_result($ordersLogic->errorcode, output_format($data));
    }

    /**
     * 审核滚球订单
     * @param Request $request
     * @return mixed
     */
    public function check(Request $request) {
        $orderNo = $request->param('orderNo');
        $status  = $request->param('status');
        $remark  = $request->param('remark');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $ordersLogic->check($orderNo, $status, $remark);

        return return_result($ordersLogic->errorcode);
    }

    /**
     * 综合过关设置其中一个无效
     * @param Request $request
     * @return array
     */
    public function parlayCancel(Request $request) {
        $orderNo = $request->param('orderNo');
        $gameId  = $request->param('gameId');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $ordersLogic->parlayCancel($orderNo, $gameId);

        return return_result($ordersLogic->errorcode);
    }

    /**
     * 综合过关其中一个算奖
     * @param Request $request
     * @return array
     */
    public function parlayClearing(Request $request) {
        $orderNo = $request->param('orderNo');
        $gameId  = $request->param('gameId');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $ordersLogic->parlayClearing($orderNo, $gameId);

        return return_result($ordersLogic->errorcode);
    }

    /**
     * 综合过关其中一个撤销结算
     * @param Request $request
     * @return mixed
     */
    public function parlayCancelClearing(Request $request) {
        $orderNo = $request->param('orderNo');
        $gameId  = $request->param('gameId');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $ordersLogic->parlayCancelClearing($orderNo, $gameId);

        return return_result($ordersLogic->errorcode);
    }

    /**
     * 修改异常订单状态
     */
    public function editAbnormalOrder(Request $request) {
        $orderNo = $request->param('orderNo');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $ordersLogic->editAbnormalOrder($orderNo);

        return return_result($ordersLogic->errorcode);
    }

    /**
     * 导出订单列表
     * @param Request $request
     * @return url
     */
    public function exportOrder(Request $request){
        $params['event_type']  = $request->param('eventType');
        $params['start_time']  = $request->param('startTime');
        $params['end_time']    = $request->param('endTime');
        $params['sport_id']    = $request->param('sportId');
        $params['user_name']   = $request->param('userName');
        $ordersLogic = Loader::model('SportsOrders', 'logic');
        $reportUrl = $ordersLogic->getExportOrderList($params);
          
        return return_result($ordersLogic->errorcode,$reportUrl);

    }

}