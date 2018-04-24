<?php

/**
 * 管理员控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use think\Env;

class Member {

    /**
     * 管理员登录
     *
     * @param Request $request
     * @return array
     */
    public function memberLogin(Request $request) {
        $params ['nickname'] = $request->param('nickname');
        $params ['password'] = $request->param('password');
        $params ['captcha']  = $request->param('captcha');

        //获取cookie
        $params['cookie'] = $request->header ( 'cookie' );

        $memberLogic = Loader::model('Member', 'logic');
        $memberInfo  = $memberLogic->login($params);

        $token = $memberInfo ['token'];
        unset ($memberInfo ['token']);

        return json([
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => output_format($memberInfo),
        ], 200, [
            'Auth-Token' => $token,
        ]);
    }

    public function twoAuth(Request $request){
        $params['uid'] = $request->param('uid');
        $params['captcha'] = $request->param('captcha');
        $memberLogic = Loader::model('Member', 'logic');
        $result  = $memberLogic->twoAuth($params);
        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }
    
    /**
     * 管理员登出
     *
     * @param Request $request
     * @return array
     */
    public function memberLogOut(Request $request) {
        $uid = $request->param('uid');

        $memberLogic = Loader::model('Member', 'logic');
        $result      = $memberLogic->logout($uid);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }
    
    /**
     * 切换二步验证开关
     * @param Request $request
     * @return unknown[]|NULL[]
     */
    public function switchTwoFactor(Request $request){
        $memberLogic = Loader::model('Member', 'logic');
        $params['authUser'] = $request->param('authUser');
        $result = $memberLogic->switchTwoFactor($params);
        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }
    
    /**
     * 保存两步验证秘钥
     * @param Request $request
     * @return unknown[]|NULL[]
     */
    public function saveTwoFactor(Request $request){
        $params = array();
        $params['two_factor_image']  = $request->param('image');
        $params['two_factor_secret'] = $request->param('secret');
        $params['code']              = $request->param('captcha');
        
        $memberLogic = Loader::model('admin/Member', 'logic');
        $result = $memberLogic->saveTwoFactor($params);
        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }
  
    /**
     * 创建两步验证秘钥
     * @return NULL[]|unknown[][]|string[][]|mixed[][]
     */
    public function createTwoFactorInfo(Request $request){
        $uid = MEMBER_ID;
        $memberLogic = Loader::model('Member', 'logic');
        $userInfo = $memberLogic->getInfo($uid);
        
        $tfa = new TwoFactorAuth(Env::get('redis.prefix'), 6, 30, 'sha1', new QRServerProvider());
        $secret = $tfa->createSecret();
        $imguri = $tfa->getQRCodeImageAsDataUri($userInfo['nickname'], $secret);
        $result = [
            'secret'  => $secret,
            'image'   => $imguri
        ];
        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }
    
    /**
     * 获取管理员列表
     *
     * @param Request $request
     * @return array
     */
    public function getMemberList(Request $request) {
        $params ['page'] = $request->param('page') ? $request->param('page') : 1;
        $params ['num']  = $request->param('num') ? $request->param('num') : 10;

        if($request->param('nickname') != '') {
            $params ['nickname'] = $request->param('nickname');
        }
        $memberLogic = Loader::model('Member', 'logic');
        $memberList  = $memberLogic->getList($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => output_format($memberList),
        ];
    }

    /**
     * 获取管理员信息
     *
     * @param Request $request
     * @return array
     */
    public function getMemberInfo(Request $request) {
        $uid = $request->param('uid');

        $memberLogic = Loader::model('Member', 'logic');
        $memberInfo  = $memberLogic->getInfo($uid);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => output_format($memberInfo),
        ];
    }

    /**
     * 新增管理员
     *
     * @param Request $request
     * @return array
     */
    public function addMember(Request $request) {
        $params ['nickname']         = $request->param('nickname');
        $params ['password']         = $request->param('password');
        $params ['confirm_password'] = $request->param('confirmPassword');
        $params ['mobile']           = $request->param('mobile','');
        $params ['email']            = $request->param('email','');
        $params ['remark']            = $request->param('remark','');

        $memberLogic = Loader::model('Member', 'logic');
        $memberInfo  = $memberLogic->add($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => output_format($memberInfo),
        ];
    }

    /**
     * 编辑管理员
     *
     * @param Request $request
     * @return array
     */
    public function editMember(Request $request) {
        $params ['uid']              = $request->param('uid');
        $params ['nickname']         = $request->param('nickname');
        $params ['password']         = $request->param('password');
        $params ['confirm_password'] = $request->param('confirmPassword');
        $params ['mobile']           = $request->param('mobile','');
        $params ['email']            = $request->param('email','');
        $params ['remark']           = $request->param('remark','');

        $memberLogic = Loader::model('Member', 'logic');
        $result      = $memberLogic->edit($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 删除管理员
     *
     * @param Request $request
     * @return array
     */
    public function delMember(Request $request) {
        $params ['uid'] = $request->param('uid/a');

        $memberLogic = Loader::model('Member', 'logic');
        $result      = $memberLogic->del($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 修改管理员状态
     *
     * @param Request $request
     * @return array
     */
    public function changeMemberStatus(Request $request) {
        $params ['uid']    = $request->param('uid/a');
        $params ['status'] = $request->param('status');

        $memberLogic = Loader::model('Member', 'logic');
        $result      = $memberLogic->changeStatus($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取管理员菜单列表
     *
     * @param Request $request
     * @return array
     */
    public function getMenuList(Request $request) {
        $params ['uid'] = $request->param('uid');

        $memberLogic = Loader::model('Member', 'logic');
        $menuList    = $memberLogic->getMenuList($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => output_format($menuList),
        ];
    }

    /**
     * 获取管理员授权用户组列表
     *
     * @param Request $request
     * @return array
     */
    public function getAuthGroupList(Request $request) {
        $params ['uid'] = $request->param('uid');

        $memberLogic   = Loader::model('Member', 'logic');
        $authGroupList = $memberLogic->getAuthGroupList($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => output_format($authGroupList),
        ];
    }

    /**
     * 编辑管理员授权用户组
     *
     * @param Request $request
     * @return array
     */
    public function editMemberAuthGroup(Request $request) {
        $params ['uid'] = $request->param('uid');
        $params ['ids'] = $request->param('ids/a');

        $memberLogic = Loader::model('Member', 'logic');
        $result      = $memberLogic->editMemberAuthGroup($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 修改密码
     *
     * @param Request $request
     * @return array
     */
    public function changePassword(Request $request) {
        $params ['uid']              = MEMBER_ID;
        $params ['originalPassword'] = $request->param('originalPassword');
        $params ['password']         = $request->param('password');
        $params ['confirm_password'] = $request->param('confirmPassword');

        $memberLogic = Loader::model('Member', 'logic');
        $result      = $memberLogic->changePassword($params);

        return [
            'errorcode' => $memberLogic->errorcode,
            'message'   => Config::get('errorcode') [$memberLogic->errorcode],
            'data'      => $result,
        ];
    }
}
