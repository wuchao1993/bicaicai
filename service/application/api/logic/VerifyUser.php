<?php
/**
 * 用户验证业务逻辑
 * @createTime 2017/5/24 15:30
 */

namespace app\api\logic;

use jwt\Jwt;
use think\Config;
use think\Model;
use think\Loader;

class VerifyUser extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    public $message;

    /**
     * 验证银行卡号和姓名
     * @param $token
     * @param $bankAccount
     * @param $bankUserName
     * @return bool
     */
    public function verifyBank($token, $bankAccount, $bankUserName) {
        if (!$token) {
            $this->errorcode = EC_USER_NEED_TOKEN;
            return false;
        }

        //验证token合法性
        $tokenInfo = Jwt::decode($token, Config::get('token_sign_key'));
        if (false === $tokenInfo) {
            $this->errorcode = Jwt::$errorcode;
            return false;
        }

        if (!$tokenInfo->unverified_uid) {
            $this->errorcode = EC_USER_TOKEN_INVALID;
            return false;
        }

        $bankInfo = Loader::model('UserBankRelation', 'logic')->getInfoByUid($tokenInfo->unverified_uid, 'ub_bank_account,ub_bank_user_name');
        if (!$bankInfo) {
            $this->errorcode = EC_USER_BANK_NO_BIND;
            return false;
        }
        if ($bankUserName != $bankInfo['ub_bank_user_name']) {
            $this->errorcode = EC_USER_BANK_USERNAME_INCORRECT;
            return false;
        }
        if ($bankAccount != substr($bankInfo['ub_bank_account'], -4)) {
            $this->errorcode = EC_USER_BANK_ACCOUNT_INCORRECT;
            return false;
        }

        //生成新的token
        $tokenInfo = [
            'unverified_uid'       => $tokenInfo->unverified_uid,
            'unverified_user_name' => $tokenInfo->unverified_user_name,
            'bank_info_verified'   => true,
        ];

        return Loader::model('User', 'logic')->generateUnverifiedToken($tokenInfo);
    }

    /**
     * 修改密码
     * @param $token
     * @param $loginPassword 登录密码
     * @param $fundPassword  资金密码
     * @return bool
     */
    public function updatePassword($token, $loginPassword, $fundPassword) {
        if (!$token) {
            $this->errorcode = EC_USER_NEED_TOKEN;
            return false;
        }

        //验证token合法性
        $tokenInfo = Jwt::decode($token, Config::get('token_sign_key'));
        if (false === $tokenInfo) {
            $this->errorcode = Jwt::$errorcode;
            return false;
        }

        if (!$tokenInfo->unverified_uid || !$tokenInfo->bank_info_verified) {
            $this->errorcode = EC_USER_TOKEN_INVALID;
            return false;
        }

        //修改密码
        $loginPasswordSalt = random_string();
        $fundPasswordSalt  = random_string();
        $update = [
            'user_password'       => md5($loginPassword . $loginPasswordSalt),
            'user_funds_password' => md5($fundPassword . $fundPasswordSalt),
            'user_salt'           => $loginPasswordSalt,
            'user_funds_salt'     => $fundPasswordSalt
        ];
        $ret = Loader::model('User')->where(['user_id' => $tokenInfo->unverified_uid])->update($update);
        if (false === $ret) {
            $this->errorcode = EC_FAILURE;
            return false;
        }

        //更新用户状态
        $update = [
            'user_status' => Config::get('status.user_status')['enable'],
        ];
        Loader::model('User')->where(['user_id' => $tokenInfo->unverified_uid])->update($update);

        //登录
        $signInParams = [
            'user_name' => $tokenInfo->unverified_user_name,
            'password'  => $loginPassword,
            'terminal'  => 'pc'
        ];
        $userLogic = Loader::model('User', 'logic');
        $userInfo = $userLogic->signIn($signInParams);
        $this->errorcode = $userLogic->errorcode;
        return $userInfo;
    }
}