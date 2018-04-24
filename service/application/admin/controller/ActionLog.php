<?php
/**
 * 行为日志控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class ActionLog {

    /**
     * 获取行为日志列表
     * @param Request $request
     * @return array
     */
    public function getActionLogList(Request $request) {
        $params['page'] = $request->param('page') ? $request->param('page') : 1;
        $params['num']  = $request->param('num') ? $request->param('num') : 10;

        if ($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }else {
            $params ['start_date'] = date('Y-m-d 00:00:00');
        }
        if ($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        } else {
            $params ['end_date'] = date('Y-m-d 23:59:59');
        }

        if($request->param('actionName') != '') {
            $params ['action_name'] = $request->param('actionName');
        }

        if($request->param('nickname') != '') {
            $params ['nickname'] = $request->param('nickname');
        }

        if($request->param('actionIp') != '') {
            $params ['action_ip'] = $request->param('actionIp');
        }

        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }

        $actionLogLogic = Loader::model('ActionLog', 'logic');
        $actionLogList  = $actionLogLogic->getList($params);

        return [
            'errorcode' => $actionLogLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogLogic->errorcode],
            'data'      => output_format($actionLogList),
        ];
    }

    /**
     * 获取行为日志信息
     * @param Request $request
     * @return array
     */
    public function getActionLogInfo(Request $request) {
        $id = $request->param('id');

        $actionLogLogic = Loader::model('actionLog', 'logic');
        $actionLogInfo  = $actionLogLogic->getInfo($id);

        return [
            'errorcode' => $actionLogLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogLogic->errorcode],
            'data'      => output_format($actionLogInfo),
        ];
    }

    /**
     * 删除行为日志
     * @param Request $request
     * @return array
     */
    public function delActionLog(Request $request) {
        return [
            'errorcode' => EC_AD_DEL_ERROR,
            'message'   => '删除功能停止使用'
        ];

        $params['id'] = $request->param('id/a');

        $actionLogLogic = Loader::model('actionLog', 'logic');
        $result         = $actionLogLogic->del($params);

        return [
            'errorcode' => $actionLogLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 清空行为日志
     * @param Request $request
     * @return array
     */
    public function clearActionLog() {
        return [
            'errorcode' => EC_AD_DEL_ERROR,
            'message'   => '清空功能停止使用'
        ];

        $actionLogLogic = Loader::model('actionLog', 'logic');
        $result         = $actionLogLogic->clear();

        return [
            'errorcode' => $actionLogLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogLogic->errorcode],
            'data'      => $result,
        ];
    }
}
