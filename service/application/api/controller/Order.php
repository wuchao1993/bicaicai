<?php
/**
 * 下注
 * @createTime 2017/4/15 16:33
 */

namespace app\api\controller;

use think\Hook;
use think\Request;
use think\Loader;
use think\Config;

class Order {

    /**
     * Order constructor.
     */
    public function __construct() {

    }

    /**
     * 下注
     * @param Request $request
     * @return array
     */
    public function bet(Request $request) {
        Hook::listen('auth_check');
        $params['auto_odds']  = $request->post('autoOdds');    //是否自动接受较佳赔率
        $params['bet_amount'] = $request->post('betAmount');   //下注金额
        $params['sport_id']   = $request->post('sportId');     //球类id
        $params['event_type'] = $request->post('eventType');   //赛事类型，滚球，今日，早盘，综合过关
        $betInfo = json_decode(htmlspecialchars_decode($request->post('betInfo')), true);
        $params['bet_info'] = input_format($betInfo, true);
        $orderLogic = Loader::model('Orders', 'logic');
        $data = $orderLogic->bet($params);
        return [
            'errorcode' => $orderLogic->errorcode,
            'message'   => $orderLogic->message ?: Config::get('errorcode')[$orderLogic->errorcode],
            'data'      => $data ? output_format($data) : [],
        ];
    }

    /**
     * 我的注单
     * @param Request $request
     * @return array
     */
    public function mineBet(Request $request) {
        Hook::listen('auth_check');
        $params['sport_id']   = $request->param('sportId');
        $params['status']     = $request->param('status');
        $params['event_type'] = $request->param('eventType');
        $params['start_time'] = $request->param('startTime');
        $params['end_time']   = $request->param('endTime');
        $params['page']       = $request->param('page');
        $ordersLogic = Loader::model('Orders', 'logic');
        $data = $ordersLogic->mineBet($params);
        return [
            'errorcode' => $ordersLogic->errorcode,
            'message'   => Config::get('errorcode')[$ordersLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    /**
     * 注单详情
     * @param Request $request
     * @return array
     */
    public function info(Request $request) {
        $ordersLogic = Loader::model('Orders', 'logic');
        if ($request->has('id')) {
            $id = $request->param('id');
            $data = $ordersLogic->getInfoById($id);
        } elseif($request->has('orderNo')) {
            $orderNo = $request->param('orderNo');
            $data = $ordersLogic->getInfoByOrderNo($orderNo);
        } else {
            return return_result(EC_PARAMS_ILLEGAL);
        }

        return return_result($ordersLogic->errorcode, output_format($data));
    }

    /**
     * 多个注单详情
     * @param Request $request
     * @return array
     */
    public function multiInfo(Request $request) {
        $ordersLogic = Loader::model('Orders', 'logic');
        $orderNos = $request->param('orderNos');
        $data = $ordersLogic->getMultiInfoByOrderNo($orderNos);

        return return_result($ordersLogic->errorcode, output_format($data));
    }

    /**
     * 获取筛选用的赛事类型
     * @return array
     */
    public function eventsType() {
        $status = Config::get('common.events_type');
        $status['outright'] = '冠军';
        return return_result(EC_SUCCESS, $status);
    }
}