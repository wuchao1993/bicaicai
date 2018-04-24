<?php
/**
 * 菜单控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class Menu {

    /**
     * 获取菜单列表
     * @param Request $request
     * @return array
     */
    public function getMenuList(Request $request) {
        $params['pid']  = $request->param('pid',0);
        $params['page'] = $request->param('page',1);
        $params['num']  = $request->param('num',10);

        if($request->param('title') != '') {
            $params['title'] = $request->param('title');
        }

        if($request->param('url') != '') {
            $params['url'] = $request->param('url');
        }

        $menuLogic = Loader::model('Menu', 'logic');
        $menuList  = $menuLogic->getList($params);

        return [
            'errorcode' => $menuLogic->errorcode,
            'message'   => Config::get('errorcode')[$menuLogic->errorcode],
            'data'      => output_format($menuList),
        ];
    }

    /**
     * 获取所有菜单列表（不判断权限）
     * @param Request $request
     * @return array
     */
    public function getAllMenuList(Request $request) {
        $menuLogic = Loader::model('Menu', 'logic');
        $menuList  = $menuLogic->getAllMenuList();

        return [
            'errorcode' => $menuLogic->errorcode,
            'message'   => Config::get('errorcode')[$menuLogic->errorcode],
            'data'      => output_format($menuList),
        ];
    }

    /**
     * 获取菜单信息
     * @param Request $request
     * @return array
     */
    public function getMenuInfo(Request $request) {
        $id = $request->param('id');

        $menuLogic = Loader::model('menu', 'logic');
        $menuInfo  = $menuLogic->getInfo($id);

        return [
            'errorcode' => $menuLogic->errorcode,
            'message'   => Config::get('errorcode')[$menuLogic->errorcode],
            'data'      => output_format($menuInfo),
        ];
    }

    /**
     * 新增菜单
     * @param Request $request
     * @return array
     */
    public function addMenu(Request $request) {
        $params['pid']        = $request->param('pid',0);
        $params['title']      = $request->param('title');
        $params['url']        = $request->param('url');
        $params['route_name'] = $request->param('routeName');
        $params['hide']       = $request->param('hide',0);
        $params['group']      = $request->param('group','');
        $params['tip']        = $request->param('tip','');
        $params['is_dev']     = $request->param('isDev',0);
        $params['sort']       = $request->param('sort',0);

        $menuLogic = Loader::model('menu', 'logic');
        $menuInfo  = $menuLogic->add($params);

        return [
            'errorcode' => $menuLogic->errorcode,
            'message'   => Config::get('errorcode')[$menuLogic->errorcode],
            'data'      => output_format($menuInfo),
        ];
    }

    /**
     * 编辑菜单
     * @param Request $request
     * @return array
     */
    public function editMenu(Request $request) {
        $params['id']         = $request->param('id');
        $params['pid']        = $request->param('pid',0);
        $params['title']      = $request->param('title');
        $params['url']        = $request->param('url');
        $params['route_name'] = $request->param('routeName');
        $params['hide']       = $request->param('hide',0);
        $params['group']      = $request->param('group','');
        $params['tip']        = $request->param('tip','');
        $params['is_dev']     = $request->param('isDev',0);
        $params['sort']       = $request->param('sort',0);

        $menuLogic = Loader::model('menu', 'logic');
        $result    = $menuLogic->edit($params);

        return [
            'errorcode' => $menuLogic->errorcode,
            'message'   => Config::get('errorcode')[$menuLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 删除菜单
     * @param Request $request
     * @return array
     */
    public function delMenu(Request $request) {
        $params['id'] = $request->param('id/a');

        $menuLogic = Loader::model('menu', 'logic');
        $result    = $menuLogic->del($params);

        return [
            'errorcode' => $menuLogic->errorcode,
            'message'   => Config::get('errorcode')[$menuLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 菜单排序
     * @param Request $request
     * @return array
     */
    public function sortMenu(Request $request) {
        $ids = $request->param('ids/a');

        $menuLogic = Loader::model('menu', 'logic');
        $result    = $menuLogic->sort($ids);

        return [
            'errorcode' => $menuLogic->errorcode,
            'message'   => Config::get('errorcode')[$menuLogic->errorcode],
            'data'      => $result,
        ];
    }

}
