<?php
/**
 * 用户行为控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class Action {

    /**
     * 获取用户行为列表
     * @param Request $request
     * @return array
     */
    public function getActionList(Request $request) {
        $params['page'] = $request->param('page', 1);
        $params['num']  = $request->param('num', 10);
        $actionLogic    = Loader::model('Action', 'logic');
        $actionList     = $actionLogic->getList($params);

        return [
            'errorcode' => $actionLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogic->errorcode],
            'data'      => output_format($actionList),
        ];
    }

    /**
     * 获取用户行为信息
     * @param Request $request
     * @return array
     */
    public function getActionInfo(Request $request) {
        $id = $request->param('id');

        $actionLogic = Loader::model('action', 'logic');
        $actionInfo  = $actionLogic->getInfo($id);

        return [
            'errorcode' => $actionLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogic->errorcode],
            'data'      => output_format($actionInfo),
        ];
    }

    /**
     * 新增用户行为
     * @param Request $request
     * @return array
     */
    public function addAction(Request $request) {
        $params['name']   = $request->param('name');
        $params['title']  = $request->param('title');
        $params['remark'] = $request->param('remark', '');
        $params['rule']   = $request->param('rule');
        $params['log']    = $request->param('log');
        $params['type']   = $request->param('type', 1);

        $actionLogic = Loader::model('action', 'logic');
        $actionInfo  = $actionLogic->add($params);

        return [
            'errorcode' => $actionLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogic->errorcode],
            'data'      => output_format($actionInfo),
        ];
    }

    /**
     * 编辑用户行为
     * @param Request $request
     * @return array
     */
    public function editAction(Request $request) {
        $params['id']     = $request->param('id');
        $params['name']   = $request->param('name');
        $params['title']  = $request->param('title');
        $params['remark'] = $request->param('remark', '');
        $params['rule']   = $request->param('rule');
        $params['log']    = $request->param('log');
        $params['type']   = $request->param('type', 1);

        $actionLogic = Loader::model('action', 'logic');
        $result      = $actionLogic->edit($params);

        return [
            'errorcode' => $actionLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 删除用户行为
     * @param Request $request
     * @return array
     */
    public function delAction(Request $request) {
        $params['id'] = $request->param('id/a');

        $actionLogic = Loader::model('action', 'logic');
        $result      = $actionLogic->del($params);

        return [
            'errorcode' => $actionLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 修用户行为状态
     * @param Request $request
     * @return array
     */
    public function changeActionStatus(Request $request) {
        $params['id']     = $request->param('id/a');
        $params['status'] = $request->param('status');

        $actionLogic = Loader::model('action', 'logic');
        $result      = $actionLogic->changeStatus($params);

        return [
            'errorcode' => $actionLogic->errorcode,
            'message'   => Config::get('errorcode')[$actionLogic->errorcode],
            'data'      => $result,
        ];
    }

}
