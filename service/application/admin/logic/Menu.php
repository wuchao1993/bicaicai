<?php

/**
 * 菜单相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Cache;

class Menu extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取菜单列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $menuModel = Loader::model('Menu');
        if(empty ($params ['url'])) {
            $condition = [
                'pid' => 0
            ];
        }
        if(!empty ($params ['pid'])) {
            $condition = [
                'pid' => $params ['pid']
            ];
        }

        if(!empty ($params ['title'])) {
            $condition ['title'] = [
                'LIKE',
                '%' . $params ['title'] . '%'
            ];
        }
        if(!empty ($params ['url'])) {
                    $condition ['url'] = [
                        'LIKE',
                        '%' . $params ['url'] . '%'
                    ];
        }

        // 获取总条数
        $count = $menuModel->where($condition)->count();

        $list = $menuModel->field('id,title,route_name,pid,group,url,hide,tip,sort,is_dev,status')->where($condition)->order('id desc')->limit($params ['num'])->page($params ['page'])->select();

        $returnArr = array(
            'totalCount' => $count,
            'list'       => $list
        );

        return $returnArr;
    }

    /**
     * 获取菜单信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($id) {
        $condition = [
            'id' => $id
        ];
        $info      = Loader::model('Menu')->where($condition)->find();

        return $info;
    }

    /**
     * 新增
     * @param  $params
     * @return bool
     */
    public function add($params) {
        $menuModel = Loader::model('Menu');

        $info = $menuModel->where(['route_name' => $params ['route_name']])->find();
        if($info) {
            $this->errorcode = EC_AD_MENU_EXISTING;
            return false;
        }

        // 入库
        $data ['pid']        = $params ['pid'];
        $data ['title']      = $params ['title'];
        $data ['route_name'] = $params ['route_name'];
        $data ['url']        = $params ['url'];
        $data ['hide']       = $params ['hide'];
        $data ['group']      = $params ['group'];
        $data ['tip']        = $params ['tip'];
        $data ['is_dev']     = $params ['is_dev'];
        $data ['sort']       = $params ['sort'];

        $ret = $menuModel->save($data);

        if($ret) {

            // 权限表入库
            $info = Loader::model('AuthRule')->where(['name' => $params ['url']])->find();
            if(!$info) {
                $data                = array();
                $data ['module']     = 'admin';
                $data ['type']       = 1;
                $data ['name']       = $params ['url'];
                $data ['title']      = $params ['title'];
                $data ['route_name'] = $params ['route_name'];

                Loader::model('AuthRule')->save($data);
            }

            //清除菜单和权限列表缓存
            Cache::clear('menulist');
            Cache::clear('accesslist');

            //记录行为
            Loader::model('General', 'logic')->actionLog('update_menu', 'Menu', $menuModel->id, MEMBER_ID);

            $menuInfo = [
                'id' => $menuModel->id
            ];

            return $menuInfo;
        }
        $this->errorcode = EC_AD_REG_FAILURE;

        return false;
    }

    /**
     * 编辑
     *
     * @param
     *            $params
     * @return bool
     */
    public function edit($params) {

        // 获取菜单信息
        $info = Loader::model('Menu')->where(['id' => $params ['id']])->find();
        if(!$info) {
            $this->errorcode = EC_AD_MENU_NONE;
            return false;
        }

        $info = Loader::model('Menu')->where(['route_name' => $params ['route_name']])->find();
        if(!empty ($info) && $info ['id'] != $params ['id']) {
            $this->errorcode = EC_AD_MENU_EXISTING;
            return false;
        }

        // 修改菜单信息
        $updateData ['pid']        = $params ['pid'];
        $updateData ['title']      = $params ['title'];
        $updateData ['route_name'] = $params ['route_name'];
        $updateData ['url']        = $params ['url'];
        $updateData ['hide']       = $params ['hide'];
        $updateData ['group']      = $params ['group'];
        $updateData ['tip']        = $params ['tip'];
        $updateData ['is_dev']     = $params ['is_dev'];
        $updateData ['sort']       = $params ['sort'];

        Loader::model('Menu')->save($updateData, ['id' => $params ['id']]);

        // 权限表入库
        $info = Loader::model('AuthRule')->where(['name' => $params ['url']])->find();

        if(!$info) {
            $data                = array();
            $data ['module']     = 'admin';
            $data ['type']       = 1;
            $data ['name']       = $params ['url'];
            $data ['title']      = $params ['title'];
            $data ['route_name'] = $params ['route_name'];

            Loader::model('AuthRule')->save($data);

        } else {
            $data                = array();
            $data ['name']       = $params ['url'];
            $data ['title']      = $params ['title'];
            $data ['route_name'] = $params ['route_name'];

            Loader::model('AuthRule')->save($data, [
                'id' => $info ['id']
            ]);
        }

        //清除菜单和权限列表缓存
        Cache::clear('menulist');
        Cache::clear('accesslist');

        //记录行为
        Loader::model('General', 'logic')->actionLog('update_menu', 'Menu', $params ['id'], MEMBER_ID);

        return true;
    }

    /**
     * 删除
     *
     * @param
     *            $params
     * @return array
     */
    public function del($params) {

        foreach($params ['id'] as $val) {

            //菜单如有下级，不允许删除
            $info = Loader::model('Menu')->where(['pid' => $val])->find();
            if($info) {
                $this->errorcode = EC_AD_DEL_PARENT_MENU_ERROR;
                return false;
            }

            $ret = Loader::model('Menu')->where([
                'id' => $val
            ])->delete();
        }

        //清除菜单和权限列表缓存
        Cache::clear('menulist');
        Cache::clear('accesslist');

        //记录行为
        Loader::model('General', 'logic')->actionLog('update_menu', 'Menu', $params ['id'], MEMBER_ID);

        return $ret;
    }

    /**
     * 排序
     *
     * @param
     *            $params
     * @return bool
     */
    public function sort($ids) {
        $menuModel = Loader::model('Menu');
        foreach($ids as $sort => $id) {

            $updateData ['sort'] = $sort;
            $menuModel->save($updateData, [
                'id' => $id
            ]);
        }

        return true;
    }

    /**
     * 获取访问授权列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getAllMenuList() {
        $menuModel = Loader::model('Menu');

        // 获取第一级节点
        $condition = [
            'hide' => 0,
            'url'  => [
                'neq',
                ''
            ]
        ];
        $list   = $menuModel->field('id,title,route_name,pid,group,url,hide,tip,sort,is_dev,status')->where($condition)->order('sort asc')->select();

        return $this->_buildMenuTreeArray($list);
    }


    private function _buildMenuTreeArray($data, $pId = 0) {
        $tree = [];

        foreach($data as $key => $value) {
            $tmp['id']   = $value ['id'];
            $tmp['name']      = $value ['title'];
            $tmp['routeName'] = $value ['route_name'];
            $tmp['url']       = $value ['url'];
            $tmp['group']       = $value ['group'];
            if($value['pid'] == $pId) {
                $childRule = $this->_buildMenuTreeArray($data, $value['id']);
                if($childRule) {
                    $tmp['childRule'] = $childRule;
                }
                $tree[] = $tmp;
            }
        }

        return $tree;
    }


}



