<?php

/**
 * 管理员相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use Filipac\Ip;
use jwt\Jwt;
use think\Cache;
use think\Config;
use think\Loader;
use think\Model;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use think\Env;

class Member extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 登录
     *
     * @param
     *            $params
     * @return array
     */
    public function login($params) {

        // 验证captcha
        if(!captcha_check($params ['captcha'],'',Config::get('captcha'))) {
            $this->errorcode = EC_AD_REG_CAPTCHA_ERROR;

            return false;
        };

        // 获取管理员信息
        $info = Loader::model('Member')->where([
            'nickname' => $params ['nickname']
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        if(md5($params ['password'] . $info ['salt']) != $info ['password']) {
            $this->errorcode = EC_AD_LOGIN_PASSWORD_ERROR;

            return false;
        }

        // 生成Token
        $memberInfo           = [
            'uid'      => $info ['uid'],
            'nickname' => $params ['nickname'],
            'cookie'   => $params ['cookie'],
        ];
       /*  if($info['is_two_factor'] == TWO_FACTOR_ENABLE){
            $memberInfo['needTwoAuth'] = TWO_FACTOR_ENABLE;
        }else{
            $memberInfo['needTwoAuth'] = TWO_FACTOR_DISABLE;
        } */
        $memberInfo ['token'] = $this->generateToken($memberInfo);

        // 获取访问授权列表返回
        $authGroupLogic                = Loader::model('AuthGroup', 'logic');
        $authAccessList                = $authGroupLogic->getAccessList($memberInfo);
        $memberInfo ['authAccessList'] = $authAccessList;

        // 修改管理员表登录信息
        $updateData                     = array();
        $updateData ['login']           = $info ['login'] + 1;
        $updateData ['last_login_time'] = time();
        $updateData ['last_login_ip']   = ip2long(Ip::get());
        Loader::model('Member')->save($updateData, [
            'uid' => $info ['uid']
        ]);

        //记录行为
        Loader::model('General', 'logic')->actionLog('user_login', 'Member', $info ['uid'], $info ['uid']);

        return $memberInfo;
    }

    /**
     * 登出
     *
     * @param
     *            $params
     * @return array
     */
    public function logout($uid) {
        Cache::tag('member')->rm(Config::get('token_cache_key') . $uid);

        return true;
    }

    /**
     * 获取管理员列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $memberModel = Loader::model('Member');

        //过滤账号admin及kaifa
        $memberId = Loader::model('Member')->getUserIdByUsername('kaifa');
        !empty($memberId)?$memberIds = [1,$memberId]:$memberIds = [1];
        $condition ['uid'] = ['NOT IN', $memberIds];
        $condition ['status'] = ['EGT',0];

        if(isset ($params ['nickname'])) {
            $condition ['nickname'] = [
                'LIKE',
                '%' . $params ['nickname'] . '%'
            ];
        }

        // 获取总条数
        $count = $memberModel->where($condition)->count();

        if($count >0) {
            $fields = 'uid,nickname,sex,birthday,qq,score,mobile,email,login,last_login_ip,last_login_time,status,remark';
            $list = $memberModel->field($fields)->where($condition)->order('uid desc')->limit($params ['num'])->page($params ['page'])->select();

            if(!empty ($list)) {
                foreach($list as &$val) {
                    $val ['last_login_ip']   = long2ip($val ['last_login_ip']);
                    $val ['last_login_time'] = date('Y-m-d H:i:s', $val ['last_login_time']);
                }
            }
        }else{
            $list = [];
        }

        $returnArr = array(
            'totalCount' => $count,
            'list'       => $list
        );

        return $returnArr;
    }

    /**
     * 获取管理员信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($uid) {
        $condition = [
            'uid' => $uid
        ];
        $info      = Loader::model('Member')->where($condition)->find()->toArray();

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

        // 判断两次密码是否一致
        if($params ['password'] != $params ['confirm_password']) {
            $this->errorcode = EC_AD_LOGIN_PASSWORD_ERROR;

            return false;
        }

        // 判断帐号是否已经存在
        $ret = Loader::model('Member')->where([
            'nickname' => $params ['nickname']
        ])->count();
        if($ret > 0) {
            $this->errorcode = EC_AD_REG_USER_EXISTING;

            return false;
        }

        // 入库
        $salt              = random_string();
        $data ['nickname'] = $params ['nickname'];
        $data ['password'] = md5($params ['password'] . $salt);
        $data ['salt']     = $salt;
        $data ['mobile']   = $params ['mobile'];
        $data ['email']    = $params ['email'];
        $data ['remark']   = $params ['remark'];
        $data ['reg_ip']   = ip2long(Ip::get());
        $data ['reg_time'] = time();
        $memberModel       = Loader::model('Member');
        $ret               = $memberModel->save($data);
        if($ret) {
            $memberInfo = [
                'uid' => $memberModel->uid
            ];

            return $memberInfo;
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

        // 判断两次密码是否一致
        if($params ['password'] != $params ['confirm_password']) {
            $this->errorcode = EC_AD_EDIT_PASSWORD_ERROR;

            return false;
        }

        // 获取管理员信息
        $userInfo = Loader::model('Member')->where([
            'uid' => $params ['uid']
        ])->find();
        if(!$userInfo) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        // 判断帐号是否已经存在
        $isExistNickname = Loader::model('Member')->where([
            'nickname' => $params ['nickname'],
            'uid' => ['NEQ', $params ['uid']]
        ])->find();
        if(!empty ($isExistNickname)) {
            $this->errorcode = EC_AD_REG_USER_EXISTING;

            return false;
        }

        // 修改管理员表信息
        $updateData              = [];
        $updateData ['nickname'] = $params ['nickname'];
        $updateData ['mobile']   = $params ['mobile'];
        $updateData ['email']    = $params ['email'];
        $updateData ['remark']    = $params ['remark'];
        if(!empty ($params ['password'])) {
            $updateData ['password'] = md5($params ['password'] . $userInfo ['salt']);
        }

        return Loader::model('Member')->save($updateData, [
            'uid' => $params ['uid']
        ]);

    }

    /**
     * 删除
     *
     * @param
     *            $params
     * @return array
     */
    public function del($params) {
        foreach($params ['uid'] as $val) {
            if($val != 1) {
                $ret = Loader::model('Member')->where([
                    'uid' => $val
                ])->delete();

                Cache::tag('member')->rm(Config::get('token_cache_key') . $val);
            }
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
        foreach($params ['uid'] as $val) {
            if($val != 1) {
                $updateData ['status'] = $params ['status'];
                Loader::model('Member')->save($updateData, [
                    'uid' => $val
                ]);
            }
        }

        return true;
    }

    /**
     * 获取菜单列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getMenuList($params) {

        $uid = $params['uid'];

        //$menuCacheList = Cache::tag ( 'menulist' )->get ($uid);

        if(true) {
        //if(empty($menuCacheList)) {

            $isAdmin = $uid == 1 ? true : false;

            if($isAdmin) {
                $ruleList = $ret = Loader::model('AuthRule')->field('name')->select();
            } else {
                // 获取用户组权限
                $authGroupAccessInfo = Loader::model('AuthGroupAccess', 'logic')->getInfoByUid($uid);
                if(empty ($authGroupAccessInfo)) {
                    return false;
                }

                $groupInfo = [];
                foreach($authGroupAccessInfo as $val) {
                    $ruleInfo = Loader::model('AuthGroup', 'logic')->getInfo($val['group_id']);
                    if(!empty($ruleInfo['rules'])) {
                        $ruleArray = explode(',', $ruleInfo['rules']);
                        $groupInfo = array_merge($groupInfo, $ruleArray);
                    }
                }

                $groupInfo['rules'] = implode(',', $groupInfo);

                if(empty ($groupInfo)) {
                    return false;
                }

                $ruleList = $ret = Loader::model('AuthRule')->field('name')->where('id in (' . $groupInfo ['rules'] . ')')->select();
            }

            $menuModel = Loader::model('Menu');

            // 获取第一级节点
            $condition = [
                'pid'  => 0,
                'hide' => 0
            ];

            $topList = $menuModel->field('id,title,route_name,pid,group,url,hide,tip,sort,is_dev,status')->where($condition)->order('sort asc')->select();

            $resultArr = array();
            if(!empty ($topList)) {
                foreach($topList as $key => $val) {
                    $resultArr [$key] ['name']      = $val ['title'];
                    $resultArr [$key] ['routeName'] = $val ['route_name'];
                    $resultArr [$key] ['icon']      = '';

                    foreach($ruleList as $ruleInfo) {
                        if($ruleInfo ['name'] == $val ['url']) {
                            $resultArr [$key] ['checked'] = 1;
                            break;
                        } else {
                            $resultArr [$key] ['checked'] = 0;
                        }
                    }

                    if($resultArr [$key] ['checked'] == 0) {
                        unset ($resultArr [$key]);
                        continue;
                    }

                    // 获取分组节点
                    $condition = "`pid` =" . $val ['id'] . " and `group` !='' and `hide`=0";
                    $groupList = $menuModel->field('group')->where($condition)->distinct(true)->select();

                    $groupArr = array();
                    $i        = 0;
                    if(!empty ($groupList)) {
                        foreach($groupList as $group) {
                            $groupArr [$i] ['name']      = $group ['group'];
                            $groupArr [$i] ['routeName'] = Pinyin($group ['group'], 1);

                            // 获取第二级节点
                            $condition = [
                                'group' => $group ['group'],
                                'pid'   => $val ['id'],
                                'hide'  => 0
                            ];

                            //非admin只能看到非开发模式的
                            if(defined('MEMBER_ID') && MEMBER_ID != 1) {
                                $condition['is_dev'] = 0;
                            }

                            $secondList = $menuModel->field('id,title,route_name,pid,group,url,hide,tip,sort,is_dev,status')->where($condition)->order('sort asc')->select();

                            if(!empty ($secondList)) {
                                $secondArr = array();
                                foreach($secondList as $key2 => $val2) {
                                    $secondArr [$key2] ['name']      = $val2 ['title'];
                                    $secondArr [$key2] ['routeName'] = $val2 ['route_name'];

                                    foreach($ruleList as $ruleInfo) {
                                        if($ruleInfo ['name'] == $val2 ['url']) {
                                            $secondArr [$key2] ['checked'] = 1;
                                            break;
                                        } else {
                                            $secondArr [$key2] ['checked'] = 0;
                                        }
                                    }

                                    if($secondArr [$key2] ['checked'] == 0) {
                                        unset ($secondArr [$key2]);
                                        continue;
                                    }
                                }
                                if(empty ($secondArr)) {
                                    unset ($groupArr [$i]);
                                    continue;
                                } else {
                                    $groupArr [$i] ['childRule'] = array_values($secondArr);
                                }
                            }

                            $i++;
                        }
                        $resultArr [$key] ['childRule'] = array_values($groupArr);
                    }
                }
            }

//            Cache::tag ( 'menulist' )->set ( $uid, $resultArr );
//        }else {
//            $resultArr = $menuCacheList;
        }

        return array_values($resultArr);
    }

    /**
     * 获取权限组列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getAuthGroupList($params) {
        //过滤组kaifa
        $condition ['title'] = ['NEQ','kaifa'];
        $authGroupList = Loader::model('AuthGroup')->where($condition)->field('id,title')->order('id asc')->select();

        $authGroupAccesslist = Loader::model('AuthGroupAccess')->field('group_id')->where([
            'uid' => $params ['uid']
        ])->select();

        foreach($authGroupList as $key => $val) {
            foreach($authGroupAccesslist as $access) {
                if($val ['id'] == $access ['group_id']) {
                    $authGroupList [$key] ['checked'] = 1;
                    break;
                } else {
                    $authGroupList [$key] ['checked'] = 0;
                }
            }
        }

        return $authGroupList;
    }

    /**
     * 更新权限组
     *
     * @param
     *            $params
     * @return array
     */
    public function editMemberAuthGroup($params) {
        $authGroupAccessModel = Loader::model('AuthGroupAccess');

        // 清除所有的权限组后再添加
        $authGroupAccessModel->where([
            'uid' => $params ['uid']
        ])->delete();

        if(!empty ($params ['ids'])) {

            foreach($params ['ids'] as $val) {

                $data ['uid']      = $params ['uid'];
                $data ['group_id'] = $val;
                $authGroupAccessModel->insert($data);
            }
        }

        return true;
    }

    /**
     * 修改密码
     *
     * @param
     *            $params
     * @return array
     */
    public function changePassword($params) {

        // 判断两次密码是否一致
        if($params ['password'] != $params ['confirm_password']) {
            $this->errorcode = EC_AD_EDIT_PASSWORD_ERROR;

            return false;
        }

        // 获取管理员信息
        $userInfo = Loader::model('Member')->where([
            'uid' => $params ['uid']
        ])->find();
        if(!$userInfo) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        // 判断原始密码是否正确
        $originalPassword = md5($params ['originalPassword'] . $userInfo ['salt']);
        if($originalPassword != $userInfo ['password']) {
            $this->errorcode = EC_AD_ORIGINAL_PASSWORD_WRONG;

            return false;
        }

        // 修改管理员表信息
        $updateData              = [];
        $updateData ['password'] = md5($params ['password'] . $userInfo ['salt']);

        Loader::model('Member')->save($updateData, [
            'uid' => $params ['uid']
        ]);

        return true;
    }

    /**
     * 生成token
     *
     * @param
     *            $memberInfo
     * @return bool
     */
    public function generateToken($memberInfo) {
        // 生成Token
        $tokenExpireTime = time() + Config::get('token_expires');
        $tokenCookie     = $memberInfo['cookie'];
        $tokenInfo       = array_merge($memberInfo, [
            'exp' => $tokenExpireTime,
            'cookie' => $tokenCookie
        ]);
        $token           = Jwt::encode($tokenInfo, Config::get('token_sign_key'));

        // 记录到redis，用于同时在线判断
        Cache::tag('member')->set(Config::get('token_cache_key') . $memberInfo ['uid'], $tokenCookie);
        // 记录到redis，用于同时超时判断
        Cache::tag('member')->set(Config::get('token_cache_key') . $memberInfo ['uid']. '_expire', $tokenExpireTime);
        
        #Cache::tag('member')->set(Config::get('token_cache_key') . $memberInfo ['uid']. '_twoAuth', $memberInfo['needTwoAuth']);

        return $token;
    }
    
    public function saveTwoFactor($params){
        $tfa = new TwoFactorAuth(Env::get('redis.prefix'), 6, 30, 'sha1', new QRServerProvider());
        if($tfa->verifyCode($params['two_factor_secret'], $params['code']) === true){
            unset($params['code']);
            $model = Loader::model('admin/Member');
            $condition = array();
            $condition['uid'] = MEMBER_ID;
            $result = $model->where($condition)->update($params);
        }else{
            $this->errorcode = EC_AD_REG_CAPTCHA_ERROR;
        }
        return $result;
    }
    
    public function switchTwoFactor($params){
        if(isset($params['authUser'])){
            if($params['authUser'] == TWO_FACTOR_ENABLE){
                $userInfo = $this->getInfo(MEMBER_ID);
                if(empty($userInfo['two_factor_secret'])){
                    $this->errorcode = EC_USER_NEED_SET_TWO_FACTOR;
                    return false;
                }
            }
            
            $data = array();
            $data['uid'] = MEMBER_ID;
            $data['is_two_factor'] = $params['authUser'];
            $memberModel = Loader::model('Member');
            return $memberModel->update($data);
        }else{
            $this->errorcode = EC_PARAMS_ILLEGAL;
        }
    }
    
    public function twoAuth($params){
        $uid = $params['uid'];
        $code = $params['captcha'];
        $twoAuth = Cache::tag('member')->get(Config::get('token_cache_key') . $uid . '_twoAuth');
        if($twoAuth){
            $userInfo = $this->getInfo($uid);
            $tfa = new TwoFactorAuth(Env::get('redis.prefix'), 6, 30, 'sha1', new QRServerProvider());
            if($tfa->verifyCode($userInfo['two_factor_secret'], $code) === true){
                Cache::tag('member')->set(Config::get('token_cache_key') . $uid . '_twoAuth', TWO_FACTOR_DISABLE);
            }else{
                $this->errorcode = EC_AD_REG_CAPTCHA_ERROR;
            }
        }else{
            $this->errorcode = EC_USER_NEED_TOKEN;
        }
        return true;
    }
}