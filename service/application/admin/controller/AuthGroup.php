<?php
/**
 * 权限组控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class AuthGroup {

    /**
     * 获取权限用户组列表
     * @param Request $request
     * @return array
     */
    public function getAuthGroupList(Request $request) {
        $params['page'] = $request->param('page', 1);
        $params['num']  = $request->param('num', 10);
        $authGroupLogic = Loader::model('AuthGroup', 'logic');
        $authGroupList  = $authGroupLogic->getList($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => output_format($authGroupList),
        ];
    }

    /**
     * 获取权限用户组信息
     * @param Request $request
     * @return array
     */
    public function getAuthGroupInfo(Request $request) {
        $id = $request->param('id');

        $authGroupLogic = Loader::model('AuthGroup', 'logic');
        $authGroupInfo  = $authGroupLogic->getInfo($id);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => output_format($authGroupInfo),
        ];
    }

    /**
     * 新增权限用户组
     * @param Request $request
     * @return array
     */
    public function addAuthGroup(Request $request) {
        $params['title']       = $request->param('title');
        $params['description'] = $request->param('description','');

        $authGroupLogic = Loader::model('AuthGroup', 'logic');
        $authGroupInfo  = $authGroupLogic->add($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => output_format($authGroupInfo),
        ];
    }

    /**
     * 编辑权限用户组
     * @param Request $request
     * @return array
     */
    public function editAuthGroup(Request $request) {
        $params['id']          = $request->param('id');
        $params['title']       = $request->param('title');
        $params['description'] = $request->param('description');

        $authGroupLogic = Loader::model('AuthGroup', 'logic');
        $result         = $authGroupLogic->edit($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 删除权限组
     * @param Request $request
     * @return array
     */
    public function delAuthGroup(Request $request) {
        $params['id'] = $request->param('id/a');

        $authGroupLogic = Loader::model('AuthGroup', 'logic');
        $result         = $authGroupLogic->del($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 修改权限组状态
     * @param Request $request
     * @return array
     */
    public function changeAuthGroupStatus(Request $request) {
        $params['id']     = $request->param('id/a');
        $params['status'] = $request->param('status');

        $authGroupLogic = Loader::model('AuthGroup', 'logic');
        $result         = $authGroupLogic->changeStatus($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取权限用户成员授权列表
     * @param Request $request
     * @return array
     */
    public function getUserList(Request $request) {

        $group_id       = $request->param('groupId');
        $authGroupLogic = Loader::model('AuthGroup', 'logic');
        $userList       = $authGroupLogic->getUserList($group_id);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => output_format($userList),
        ];
    }

    /**
     * 新增权限组成员授权
     * @param Request $request
     * @return array
     */
    public function addUser(Request $request) {
        $params['uid']      = $request->param('uid');
        $params['group_id'] = $request->param('groupId');
        $authGroupLogic     = Loader::model('AuthGroup', 'logic');
        $result             = $authGroupLogic->addUser($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => output_format($result),
        ];
    }

    /**
     * 删除权限组成员授权
     * @param Request $request
     * @return array
     */
    public function delUser(Request $request) {
        $params['uid']      = $request->param('uid');
        $params['group_id'] = $request->param('groupId');
        $authGroupLogic     = Loader::model('AuthGroup', 'logic');
        $result             = $authGroupLogic->delUser($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取访问权限列表
     * @param Request $request
     * @return array
     */
    public function getAccessList(Request $request) {
        $params['uid']      = $request->param('uid');
        $params['group_id'] = $request->param('groupId');
        $authGroupLogic     = Loader::model('AuthGroup', 'logic');
        $accessList         = $authGroupLogic->getAccessList($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => output_format($accessList),
        ];
    }

    /**
     * 更新访问权限
     * @param Request $request
     * @return array
     */
    public function editAccess(Request $request) {
        $params['groupId'] = $request->param('groupId');
        $params['rules']   = $request->param('rules/a');

        $authGroupLogic = Loader::model('AuthGroup', 'logic');
        $result         = $authGroupLogic->editAccess($params);

        return [
            'errorcode' => $authGroupLogic->errorcode,
            'message'   => Config::get('errorcode')[$authGroupLogic->errorcode],
            'data'      => $result,
        ];
    }

}
