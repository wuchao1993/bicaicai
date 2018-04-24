<?php

/**
 * 权限组相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Cache;
use think\Config;
use think\Loader;
use think\Model;

class AuthGroup extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取权限组列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $authGroupModel = Loader::model('AuthGroup');
        //过滤组kaifa
        $condition ['title'] = ['NEQ','kaifa'];
        // 获取总条数
        $count = $authGroupModel->where($condition)->count();

        $list = $authGroupModel->where($condition)->field('id,title,description,status')->order('id asc')->limit($params ['num'])->page($params ['page'])->select();

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 获取权限组信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($id) {
        $condition = [
            'id' => $id,
        ];
        $info      = Loader::model('AuthGroup')->where($condition)->find();

        return $info;
    }

    /**
     * 新增
     *
     * @param
     *            $params
     * @return bool
     */
    public function add($params) {

        // 判断权限组是否已经存在
        $ret = Loader::model('AuthGroup')->where([
            'title' => $params ['title'],
        ])->count();
        if($ret > 0) {
            $this->errorcode = EC_AD_REG_USER_EXISTING;

            return false;
        }

        // 入库
        $data ['module']      = 'admin';
        $data ['type']        = 1;
        $data ['title']       = $params ['title'];
        $data ['description'] = $params ['description'];
        $authGroupModel       = Loader::model('AuthGroup');
        $ret                  = $authGroupModel->save($data);
        if($ret) {
            $authGroupInfo = [
                'id' => $authGroupModel->id,
            ];

            return $authGroupInfo;
        }
        $this->errorcode = EC_AD_REG_FAILURE;

        return false;
    }

    /**
     * 编辑
     *
     * @param
     *            $params
     * @return array
     */
    public function edit($params) {

        // 获取权限组信息
        $info = Loader::model('AuthGroup')->where([
            'id' => $params ['id'],
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        // 修改权限组表信息
        $updateData ['title']       = $params ['title'];
        $updateData ['description'] = $params ['description'];
        Loader::model('AuthGroup')->save($updateData, [
            'id' => $info ['id'],
        ]);

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
            $ret = Loader::model('AuthGroup')->where([
                'id' => $val,
            ])->delete();
        }

        return $ret;
    }

    /**
     * 修改状态
     *
     * @param
     *            $params
     * @return array
     */
    public function changeStatus($params) {
        foreach($params ['id'] as $val) {
            $updateData ['status'] = $params ['status'];
            Loader::model('AuthGroup')->save($updateData, [
                'id' => $val,
            ]);
        }

        return true;
    }

    /**
     * 获取权限组用户列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserList($group_id) {
        $condition = [
            'group_id' => $group_id,
            'status'   => ['NEQ',-1]
        ];
        $list      = Loader::model('Member')->alias('m')->join('AuthGroupAccessNew aga', 'm.uid=aga.uid', 'LEFT')->field('m.uid,m.nickname,m.last_login_ip,m.last_login_time,m.status')->where($condition)->order('m.uid asc')->select();

        if(!empty ($list)) {
            foreach($list as &$val) {
                $val ['last_login_ip']   = long2ip($val ['last_login_ip']);
                $val ['last_login_time'] = date('Y-m-d H:i:s', $val ['last_login_time']);
            }
        }

        return $list;
    }

    /**
     * 新增权限组用户
     *
     * @param
     *            $params
     * @return bool
     */
    public function addUser($params) {
        $authGroupAccessModel = Loader::model('AuthGroupAccess');

        // 判断是否已在此权限组中
        $condition = [
            'uid'      => $params ['uid'],
            'group_id' => $params ['group_id'],
        ];
        $info      = $authGroupAccessModel->where($condition)->find();
        if(!empty ($info)) {
            $this->errorcode = EC_AD_ADD_ACCESS_EXISTING;

            return false;
        }

        // 入库
        $data ['uid']      = $params ['uid'];
        $data ['group_id'] = $params ['group_id'];

        $ret = $authGroupAccessModel->save($data);
        if($ret) {
            $condition = [
                'aga.uid' => $params ['uid'],
            ];
            $info      = Loader::model('Member')->alias('m')->join('AuthGroupAccess aga', 'm.uid=aga.uid', 'LEFT')->field('m.uid,m.nickname,m.last_login_ip,m.last_login_time,m.status')->where($condition)->find();

            if(!empty ($info)) {
                $info ['last_login_ip']   = long2ip($info ['last_login_ip']);
                $info ['last_login_time'] = date('Y-m-d H:i:s', $info ['last_login_time']);
            }

            return $info;
        }
        $this->errorcode = EC_AD_ADD_ACCESS_ERROR;

        return false;
    }

    /**
     * 删除权限组用户
     *
     * @param
     *            $params
     * @return bool
     */
    public function delUser($params) {

        if($params ['uid'] == MEMBER_ID) {
            $this->errorcode = EC_AD_ACTION_GROUP_SELF_ERROR;

            return false;
        }

        $ret = Loader::model('AuthGroupAccess')->where([
            'uid'      => $params ['uid'],
            'group_id' => $params ['group_id'],
        ])->delete();

        return $ret;
    }

    /**
     * 获取访问授权列表
     * @params $params
     * @return array
     */
    public function getAccessList($params) {

        if (!defined('MEMBER_ID')) {
            define ( 'MEMBER_ID', 0 );
        }

        $uid = isset($params['uid']) ? $params['uid'] : '';
        $group_id = isset($params['group_id']) ? $params['group_id'] : '';

        $isAdmin = $uid == 1 ? true : false;

        if($isAdmin) {
            $ruleList = Loader::model('AuthRule')->column('name');
        } else {
            if(empty($uid) && !empty($group_id)) {
                $groupInfo = $this->getInfo($group_id);
            } else {
                //获取用户组权限
                $authGroupAccessInfo = Loader::model('AuthGroupAccess', 'logic')->getInfoByUid($uid);
                if(empty ($authGroupAccessInfo)) {
                    return false;
                }

                $groupArray = [];
                foreach($authGroupAccessInfo as $val) {
                    $ruleInfo = $this->getInfo($val['group_id']);
                    if(!empty($ruleInfo['rules'])) {
                        $ruleArray = explode(',', $ruleInfo['rules']);
                        $groupArray = array_merge($groupArray, $ruleArray);
                    }
                }

                $groupInfo['rules'] = implode(',', $groupArray);
            }

            if(!empty ($groupInfo['rules'])) {
                $ruleList = Loader::model('AuthRule')->where('id in (' . $groupInfo['rules'] . ')')->column('name');
            }else{
                $ruleList = [];
            }

        }

        $condition = [
            'm.url' => [
                'neq',
                '',
            ],
        ];

        $menuModel = Loader::model('Menu');
        $list      = $menuModel->alias('m')->join('AuthRuleNew ar', 'm.url=ar.name', 'LEFT')->field('ar.id as rulesId ,m.id,m.title,m.route_name,m.pid,m.group,m.url,m.hide,m.tip,m.sort,m.is_dev,m.status')->where($condition)->order('m.sort asc')->select();

        //不是admin帐号的话，需要对菜单进行处理，只能看到当前自身权限下的菜单
        if(MEMBER_ID != 1 && empty($uid) && !empty($group_id)) {

            $mangerAuthGroupAccessInfo = Loader::model('AuthGroupAccess', 'logic')->getInfoByUid(MEMBER_ID);
            if(empty ($mangerAuthGroupAccessInfo)) {
                return false;
            }

            $mangerGroupArray = [];
            foreach($mangerAuthGroupAccessInfo as $val) {
                $mangerRuleInfo = $this->getInfo($val['group_id']);
                if(!empty($mangerRuleInfo['rules'])) {
                    $ruleArray = explode(',', $mangerRuleInfo['rules']);
                    $mangerGroupArray = array_merge($mangerGroupArray, $ruleArray);
                }
            }

            $mangerGroupInfo = implode(',', $mangerGroupArray);

            if(!empty ($mangerGroupInfo)) {
                $mangerRuleList = Loader::model('AuthRule')->where('id in (' . $mangerGroupInfo . ')')->column('name');
            }else {
                $mangerRuleList = [];
            }

            if(is_object($mangerRuleList)) {
                $mangerRuleList = collection($mangerRuleList)->toArray();
            }

            foreach($list as $key => $val) {
                if(!in_array($val ['url'], $mangerRuleList)) {
                    unset($list[$key]);
                }
            }
        }

        return $this->_buildAccessTreeArray($list, $ruleList);
    }


    private function _buildAccessTreeArray($data, $rule = [], $pId = 0) {

        //根据rule来生成缓存标识
//        if($pId == 0) {
//            $cacheKey        = md5(implode('-', $rule));
//            $accessCacheList = Cache::tag('accesslist')->get($cacheKey);
//        }else {
//            $accessCacheList = '';
//        }

        $tree = [];

        if(true) {
        //if(empty($accessCacheList)) {

            if(is_object($rule)) {
                $rule = collection($rule)->toArray();
            }

            foreach($data as $key => $value) {

                if(empty($value ['rulesId'])) {
                    continue;
                }

                $tmp              = [];
                $tmp['rulesId']   = $value ['rulesId'];
                $tmp['name']      = $value ['title'];
                $tmp['routeName'] = $value ['route_name'];
                $tmp['url']       = $value ['url'];
                $tmp['checked']   = in_array($value ['url'], $rule) ? 1 : 0;
                if($value['pid'] == $pId) {
                    $childRule = $this->_buildAccessTreeArray($data, $rule, $value['id']);
                    if($childRule) {
                        $tmp['childRule'] = $childRule;
                    }
                    $tree[] = $tmp;
                }
            }

//            if($pId == 0) {
//                Cache::tag('accesslist')->set($cacheKey, $tree);
//            }

//        }else {
//            $tree = $accessCacheList;
        }

        return $tree;
    }


    /**
     * 更新访问权限
     *
     * @param
     *            $params
     * @return array
     */
    public function editAccess($params) {

        // 修改权限组表信息
        $params ['rules']     = array_filter($params ['rules']);
        $updateData ['rules'] = implode(',', $params ['rules']);
        Loader::model('AuthGroup')->save($updateData, [
            'id' => $params ['groupId'],
        ]);

        // 清除对应权限组的管理员token
        $authGroupAccessList = Loader::model('AuthGroupAccess')->field('uid')->where([
            'group_id' => $params ['groupId'],
        ])->select();

        if(!empty($authGroupAccessList)) {
            foreach($authGroupAccessList as $val) {
                Cache::tag('member')->rm(Config::get('token_cache_key') . $val['uid']);
            }
        }

        //清除菜单和权限列表缓存
        Cache::clear('menulist');
        Cache::clear('accesslist');

        return true;
    }
}