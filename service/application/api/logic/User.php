<?php
/**
 * 用户相关业务逻辑
 * @createTime 2017/3/31 17:08
 */
namespace app\api\logic;

use Filipac\Ip;
use jwt\Jwt;
use passport\Passport;
use think\Cache;
use think\Config;
use think\Loader;

class User extends \app\common\logic\User {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    public $message = '';

    /**
     * 判断用户名是否存在
     * @param $userName
     * @return bool
     */
    public function checkUserByUserName($userName) {
        $passport = new Passport();
        $result = $passport->verifyUsername($userName);
        if (!$result) {
            $this->errorcode = EC_USER_REG_USER_EXISTING;
            $this->message = $passport->getErrorMessage();
            return false;
        }
        return true;
    }

    /**
     * 登录
     * @param $params
     * @return array|bool
     * @throws \think\exception\PDOException
     */
    public function signIn($params) {
        //到用户中心登录
        $passport = new Passport();
        if ($params['special']) {
            $result = $passport->setPlatformName($params['terminal'])->tryLogin($params['user_name'], $params['password'], Ip::get());
        } else {
            $result = $passport->setPlatformName($params['terminal'])->login($params['user_name'], $params['password'], Ip::get());
        }
        if ($result) {
            $accessToken = $passport->getAccessToken(); //获取Token
            $userInfo = $passport->getUserInfo(); //获取用户信息
        } else {
            $this->errorcode = $passport->getErrorCode();
            $this->message = $passport->getErrorMessage();
            return false;
        }

        //判断用户身份
//        if ($userInfo['identityCategory'] != $GLOBALS['auth_identity']) {
//            $this->errorcode = EC_USER_IDENTITY_ERROR;
//            return false;
//        }

        //获取用户信息，如果没有则创建用户
        $info = Loader::model('User')->where(['user_id' => $userInfo['uid']])->find();
        if (!$info) {
            $params['uid'] = $userInfo['uid'];
            $params['terminal'] = 'unknown';
            if (!$this->createUser($params)) {
                return false;
            }
        } else {
            if ($GLOBALS['auth_identity'] == 'normal' && $userInfo['uid'] == 1) {
                //正式库的uid=1(总代理)那个用户不让登录。。
                $this->errorcode = EC_USER_LOGIN_USER_NONE;
                return false;
            }

            if($info['user_status'] != Config::get('status.user_status')['enable']) {
                $this->errorcode = EC_USER_DISABLE;
                return false;
            }
        }

        $extendInfo = $this->getExtendInfoByUid($userInfo['uid']);

        //是否绑定银行卡
        $bankInfo = Loader::model('UserBankRelation', 'logic')->getInfoByUid($userInfo['uid']);
        if ($bankInfo && $bankInfo['ub_status'] == Config::get('status.user_bank_status')['enable']) {
            $isBindBank = true;
        } else {
            $isBindBank = false;
        }

        //记录登录日志
        $this->recordLoginLog($userInfo['uid'], $params['terminal']);
        
        //更新登录次数
        Loader::model('UserExtend')->where('user_id', $userInfo['uid'])->setInc('ue_login_count');

        //从用户中心同步个人信息
        $syncUserInfo = [
            'user_nickname' => $userInfo['nickname'],
            'user_mobile'   => $userInfo['mobile'],
            'user_email'    => $userInfo['email'],
            'user_qq'       => $userInfo['qq'],
        ];
        Loader::model('User')->where(['user_id' => $userInfo['uid']])->update($syncUserInfo);

        //记录在线状态
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'user_online_status:'  . $userInfo['uid'];
        Cache::set($cacheKey, true, Config::get('common.cache_time')['user_online']);

        return [
            'token'                => $accessToken,
            'identity'             => $userInfo['identityCategory'],
            'user_name'            => $params['user_name'],
            'real_name'            => $info['user_realname'] ?: '',
            'is_agent'             => $info['user_is_agent'] ?: 0,
            'account_balance'      => $extendInfo['account_balance'],
            'is_bind_bank'         => $isBindBank,
            'is_set_fund_password' => empty($info['user_funds_password']) ? false : true,
            'last_login_time'      => $info['user_last_login_time'] ?: date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 注册
     * @param $params
     * @return array|bool
     * @throws \think\exception\PDOException
     */
    public function signUp($params) {
        //验证captcha
        if(!captcha_check($params['captcha'])) {
            $this->errorcode = EC_USER_REG_CAPTCHA_ERROR;
            return false;
        };

        //到用户中心注册
        $userCenterData = [
            'username' => $params['user_name'],
            'password' => $params['password'],
            'ip'       => Ip::get(),
        ];
        $passport = new Passport();
        $result = $passport->setPlatformName($params['terminal'])->signup($userCenterData);
        if ($result) {
            $accessToken = $passport->getAccessToken();
            $userInfo = $passport->getUserInfo();
            $params['uid'] = $userInfo['uid'];
        } else {
            $this->errorcode = $passport->getErrorCode();
            $this->message = $passport->getErrorMessage();
            return false;
        }

        //判断用户身份
//        if ($userInfo['identityCategory'] != $GLOBALS['auth_identity']) {
//            $this->errorcode = EC_USER_IDENTITY_ERROR;
//            return false;
//        }

        if (!$this->createUser($params)) {
            return false;
        }

        //记录登录日志
        $this->recordLoginLog($userInfo['uid'], $params['terminal']);

        return [
            'token'           => $accessToken,
            'uid'             => $userInfo['uid'],
            'user_name'       => $params['user_name'],
            'is_agent'        => Config::get('status.user_is_agent')['no'],
            'account_balance' => 0,
        ];
    }

    /**
     * 创建用户信息
     * @param $params
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function createUser($params) {
        $defaultLevelId = Loader::model('UserLevel')->getDefaultLevelId();
        if (empty($defaultLevelId)) {
            $this->errorcode = EC_CM_LACK_USER_DEFAULT_LEVEL;
            return false;
        }

        $channelId = 0;
        if ($params['channel']) {
            $channelId = Loader::model('Channel')->getChannelId($params['channel']);
        }

        if ($params['invitation_code']) {
            $agentLinkInfo = Loader::model('AgentLink')->getInfoByCode($params['invitation_code'], Config::get('status.agent_link_type')['user']);
            if ($agentLinkInfo === false) {
                $this->errorcode = EC_AGENT_INVITATION_CODE_ERROR;
                return false;
            }

            $userPid = $agentLinkInfo['user_id'];
            $rebateConfig = Loader::model('AgentLinkRebate')->getLinkRebate($agentLinkInfo['agl_id']);
            $registerWay = Config::get('status.user_register_way')['code'];
        } else {
            $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
            $domainInfo = Loader::model('AgentDomain')->getInfoByDomain($domain);
            if($domainInfo){
                $userPid = $domainInfo['user_id'];
                $userRegisterUrlId = $domainInfo['agd_id'];
                $rebateConfig = Loader::model('AgentDomainRebate')->getRebateByUserId($userPid, $domainInfo['agd_id']);
                $registerWay = Config::get('status.user_register_way')['domain'];
            }else{
                $userPid = Config::get('common.default_agent_uid');
                $rebateConfig = Loader::model('LotteryCategory')->getDefaultRebateMap();
                $registerWay = Config::get('status.user_register_way')['default'];
            }
        }

        if($userPid){
            $upUserInfo = Loader::model('User')->getInfo($userPid);
            $userGrade = $upUserInfo['user_grade'] + 1;
            $userAllPid = $upUserInfo['user_all_pid'] ? $upUserInfo['user_all_pid'] . "," . $userPid : $userPid;
        }

        //开启事务；如果本站几张表失败回滚，那么用户中户中心不会回滚，在登录的时候再写一遍用户信息
        $this->db()->startTrans();

        //插入用户表
        $userIp = Ip::get();
        $data['user_id']              = $params['uid'];
        $data['ul_id']                = $defaultLevelId;
        $data['user_name']            = $params['user_name'];
        $data['user_last_login_time'] = date('Y-m-d H:i:s');
        $data['user_last_login_ip']   = $userIp;
        $data['channel_id']           = $channelId;
        $data['user_pid']             = $userPid;
        $data['reg_terminal']         = Config::get('status.reg_terminal')[$params['terminal']];
        $data['reg_way']              = $registerWay;
        $data['user_reg_ip']          = $userIp;
        $data['user_all_pid']         = isset($userAllPid) ? $userAllPid : $userPid;
        $data['user_createtime']      = date('Y-m-d H:i:s');
        isset($userGrade) && $data['user_grade'] = $userGrade;
        isset($userRegisterUrlId) && $data['user_reg_url'] = $userRegisterUrlId;

        if (!Loader::model('User')->insert($data)) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        //插入用户扩展表
        if (!Loader::model('UserExtend')->insert(['user_id' => $params['uid']])) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        $addConfigResult = Loader::model('UserAutoRebateConfig', 'logic')->addUserRebateConfig($rebateConfig, $params['uid'], $userPid);
        if ($addConfigResult === false) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        $this->db()->commit();

        //统计信息
        if ($registerWay == Config::get('status.user_register_way')['code']) {
            Loader::model('AgentLink')->decreaseUseCount($agentLinkInfo['agl_id']);
        } else if ($registerWay == Config::get('status.user_register_way')['domain']) {
            Loader::model('AgentDomain')->decreaseUseCount($domainInfo['agd_id']);
        }

        Loader::model('User')->addLowerCount($userPid);
        Loader::model('UserLevel')->addUserCount($defaultLevelId);

        if ($params['device_unique_id']) {
            Loader::model('Device')->updateUserInfo($params['device_unique_id'], $params['uid']);
        }

        return true;
    }

    /**
     * 免费试玩注册
     * @param $params
     * @return array|bool
     * @throws \think\exception\PDOException
     */
    public function guestSignUp($params) {
        //注册数量限制
        if(!$this->checkIpLimit()) {
            return false;
        }

        //到用户中心注册
        $passport = new Passport();
        $result = $passport->setPlatformName($params['terminal'])->trySignup('guest', '', '', Ip::get());
        if ($result) {
            $accessToken = $passport->getAccessToken();
            $userInfo = $passport->getUserInfo();
        } else {
            $this->errorcode = $passport->getErrorCode();
            $this->message = $passport->getErrorMessage();
            return false;
        }

        //判断用户身份
//        if ($userInfo['identityCategory'] != $GLOBALS['auth_identity']) {
//            $this->errorcode = EC_USER_IDENTITY_ERROR;
//            return false;
//        }

        //开启事务
        $this->db()->startTrans();

        //插入用户表
        $userIp = Ip::get();
        $data['user_id']              = $userInfo['uid'];
        $data['user_name']            = $userInfo['username'];
        $data['user_last_login_time'] = date('Y-m-d H:i:s');
        $data['user_last_login_ip']   = $userIp;
        $data['user_reg_ip']          = $userIp;
        $data['user_createtime']      = date('Y-m-d H:i:s');
        if (!Loader::model('User')->save($data)) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        //读取配置
        $siteConfig = Loader::model('SiteConfig')->getConfig('sports', 'common', 'guest_init_balance');

        //插入用户扩展表
        $extendData = [
            'user_id' => $userInfo['uid'],
            'ue_account_balance' => $siteConfig['guest_init_balance']
        ];
        $ret = Loader::model('UserExtend')->save($extendData);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        $this->db()->commit();

        //记录登录日志
        $this->recordLoginLog($userInfo['uid'], $params['terminal']);

        $userInfo = [
            'token'           => $accessToken,
            'uid'             => $userInfo['uid'],
            'user_name'       => $userInfo['username'],
            'account_balance' => $siteConfig['guest_init_balance'],
        ];
        return $userInfo;
    }

    /**
     * 特殊代理注册下级账号
     * @param $params
     * @return bool
     * @throws \think\exception\PDOException
     */
    public function specialAgentSignUp($params) {
        //TODO 只有uid=2的特殊总代理才能创建下级
        $passport = new Passport();
        $result = $passport->trySignup('special', $params['user_name'], $params['password'], Ip::get());
        if ($result) {
            $userInfo = $passport->getUserInfo(); //获取用户信息
        } else {
            $this->errorcode = $passport->getErrorCode();
            $this->message = $passport->getErrorMessage();
            return false;
        }

        //开启事务
        $this->db()->startTrans();

        //插入用户表
        $userIp = Ip::get();
        $data['user_id']              = $userInfo['uid'];
        $data['user_pid']             = USER_ID;
        $data['user_name']            = $params['user_name'];
        $data['user_last_login_time'] = date('Y-m-d H:i:s');
        $data['user_last_login_ip']   = $userIp;
        $data['user_reg_ip']          = $userIp;
        $data['user_createtime']      = date('Y-m-d H:i:s');
        if (!Loader::model('User')->save($data)) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        //读取配置
        $siteConfig = Loader::model('SiteConfig')->getConfig('sports', 'common', 'guest_init_balance');

        //插入用户扩展表
        $extendData = [
            'user_id' => $userInfo['uid'],
            'ue_account_balance' => $siteConfig['guest_init_balance']
        ];
        $ret = Loader::model('UserExtend')->save($extendData);

        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        $this->db()->commit();
        return true;
    }

    /**
     * 试玩用户ip限制
     * @return bool
     */
    public function checkIpLimit() {
        $sTime = date('Y-m-d') . ' 00:00:00';
        $eTime = date('Y-m-d') . ' 23:59:59';
        $where = [
            'user_reg_ip' => Ip::get(),
            'user_createtime' => ['between', [$sTime, $eTime]]
        ];
        $count = Loader::model('User')->where($where)->count();

        //读取配置
        $siteConfig = Loader::model('SiteConfig')->getConfig('sports', 'common', 'guest_ip_limit_num');

        if ($count >= $siteConfig['guest_ip_limit_num']) {
            $this->errorcode = EC_USER_GUEST_REG_IP_LIMIT;
            return false;
        }
        return true;
    }

    /**
     * 记录登录日志
     * @param $uid
     * @param $terminal
     * @return bool
     */
    public function recordLoginLog($uid, $terminal) {
        $userIp = Ip::get();
        $log['user_id']        = $uid;
        $log['ull_type']       = Config::get('status.reg_terminal')[$terminal];
        $log['ull_login_time'] = date('Y-m-d H:i:s');
        $log['ull_login_ip']   = $userIp;
        $log['ull_createtime'] = date('Y-m-d H:i:s');
        $log['ull_modifytime'] = date('Y-m-d H:i:s');
        Loader::model('UserLoginLog')->save($log);

        //修改用户表登录信息
        $userLog['user_last_login_time'] = $log['ull_login_time'];
        $userLog['user_last_login_ip']   = $userIp;
        Loader::model('User')->save($userLog, ['user_id' => $uid]);

        return true;
    }

    public function getInfo($userId){
        $info = Loader::model('User')->where(['user_id' => $userId])->find();
        return $info;
    }

    public function getBanks(){
        $userId = USER_ID;

        if(!$userId){
            $this->errorcode = EC_USER_JWT_EXP_ERROR;
            return;
        }

        $bankList = Loader::model('Bank','logic')->getList();
        $condition = [
            'user_id' => $userId,
            'ub_status' => Config::get('status.user_bank_status')['enable'],
        ];
        $userBankList = Loader::model('userBankRelation')->where($condition)->select();

        $response = array();
        foreach ($userBankList as $userBank){
            $bank_id = $userBank['bank_id'];
            $bank_info = $bankList[$bank_id];
            $temp = array();
            $temp['id'] = $userBank['ub_id'];
            $temp['name'] = $bank_info['bank_name'];
            $temp['code'] = $bank_info['bank_code'];
            $temp['image'] = $bank_info['bank_image_mobile'];
            $temp['account'] = $userBank['ub_bank_account'];
            $temp['isDefault'] = $userBank['ub_is_default'];
            $temp['address'] = $userBank['ub_address'];
            $response[] = $temp;
        }

        return $response;
    }

    /**
     * 第三方登录
     * @param $params
     * @return array
     * @throws \think\exception\PDOException
     */
    public function thirdSignIn($params) {
        //获取用户信息
        $info = Loader::model('User')->where(['user_third_token' => $params['user_third_token'], 'user_third_type' => $params['user_third_type']])->find();
        if (!$info) {
            //用户不存在则进行注册
            $info = $this->thirdSignUp($params);
        }

        $extendInfo = $this->getExtendInfoByUid($info['user_id']);

        //生成Token
        $userInfo = [
            'uid'             => $info['user_id'],
            'user_name'       => $info['user_name'],
            'is_agent'        => $info['user_is_agent'],
            'account_balance' => $extendInfo['account_balance'],
        ];
        $userInfo['token'] = $this->generateToken($userInfo);

        //根据密码是否为空来判断是否需要完善资料
        if(empty($info['password'])) {
            $userInfo['needImprove'] = 1;
        }else {
            $userInfo['needImprove'] = 0;
        }

        //记录登录日志
        $this->recordLoginLog($info['user_id'], $params['terminal']);

        $userInfo['last_login_time'] = $info['user_last_login_time'];
        return $userInfo;
    }

    /**
     * 第三方注册
     * @param $params
     * @return array
     * @throws \think\exception\PDOException
     */
    public function thirdSignUp($params) {

        $defaultLevelId = Loader::model('UserLevel')->getDefaultLevelId();
        if (empty($defaultLevelId)) {
            $this->errorcode = EC_CM_LACK_USER_DEFAULT_LEVEL;
            return false;
        }

        $channelId = 0;
        if ($params['channel']) {
            $channelId = Loader::model('Channel')->getChannelId($params['channel']);
        }

        if ($params['invitation_code']) {
            $agentLinkInfo = Loader::model('AgentLink')->getInfoByCode($params['invitation_code'], Config::get('status.agent_link_type')['user']);
            if ($agentLinkInfo === false) {
                $this->errorcode = EC_AGENT_INVITATION_CODE_ERROR;
                return false;
            }

            $userPid = $agentLinkInfo['user_id'];
            $rebateConfig = Loader::model('AgentLinkRebate')->getLinkRebate($agentLinkInfo['agl_id']);
            $registerWay = Config::get('status.user_register_way')['code'];
        } else {
            $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
            $domainInfo = Loader::model('AgentDomain')->getInfoByDomain($domain);
            if($domainInfo){
                $userPid = $domainInfo['user_id'];
                $userRegisterUrlId = $domainInfo['agd_id'];
                $rebateConfig = Loader::model('AgentDomainRebate')->getRebateByUserId($userPid, $domainInfo['agd_id']);
                $registerWay = Config::get('status.user_register_way')['domain'];
            }else{
                $userPid = 0;
                $rebateConfig = Loader::model('LotteryCategory')->getDefaultRebateMap();
                $registerWay = Config::get('status.user_register_way')['default'];
            }
        }

        if($userPid){
            $upUserInfo = Loader::model('User')->getInfo($userPid);
            $userAllPid = $upUserInfo['user_all_pid'] ? $upUserInfo['user_all_pid'] . "," . $userPid : $userPid;
        }

        //开启事务
        $this->db()->startTrans();

        //插入用户表
        $randomUserName = 'third'.random_string();
        $userIp = Ip::get();
        $data['user_third_token']     = $params['user_third_token'];
        $data['user_third_type']      = $params['user_third_type'];
        $data['user_nickname']        = $params['user_nickname'];
        $data['user_name']            = $randomUserName;
        $data['user_password']        = '';
        $data['user_salt']            = '';
        $data['user_last_login_time'] = date('Y-m-d H:i:s');
        $data['user_last_login_ip']   = $userIp;
        $data['channel_id']           = $channelId;
        $data['user_pid']             = $userPid;
        $data['reg_way']              = $registerWay;
        $data['user_reg_url']         = $userRegisterUrlId ? $userRegisterUrlId : 0;
        $data['user_reg_ip']          = $userIp;
        $data['user_all_pid']         = isset($userAllPid) ? $userAllPid : $userPid;
        $data['user_createtime']      = date('Y-m-d H:i:s');
        $userModel = Loader::model('User');
        $userModel->save($data);
        if (!$userModel->user_id) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        //插入用户扩展表
        $ret = Loader::model('UserExtend')->save(['user_id' => $userModel->user_id]);
        if (!$ret) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        $addConfigResult = Loader::model('UserAutoRebateConfig', 'logic')->addUserRebateConfig($rebateConfig, $userModel->user_id, $userPid);
        if ($addConfigResult === false) {
            $this->db()->rollback();
            $this->errorcode = EC_USER_REG_FAILURE;
            return false;
        }

        $this->db()->commit();

        //统计信息
        if ($registerWay == Config::get('status.user_register_way')['code']) {
            Loader::model('AgentLink')->decreaseUseCount($agentLinkInfo['agl_id']);
        } else if ($registerWay == Config::get('status.user_register_way')['domain']) {
            Loader::model('AgentDomain')->decreaseUseCount($domainInfo['agd_id']);
        }

        Loader::model('User')->addLowerCount($userPid);
        Loader::model('UserLevel')->addUserCount($defaultLevelId);

        if ($params['device_unique_id']) {
            Loader::model('Device')->updateUserInfo($params['device_unique_id'], $userModel->user_id);
        }

        //生成Token
        $userInfo = [
            'user_id'         => $userModel->user_id,
            'user_name'       => $randomUserName,
            'is_agent'        => Config::get('status.user_is_agent')['no'],
            'account_balance' => 0,
        ];
        $userInfo['token'] = $this->generateToken($userInfo);

        return $userInfo;
    }

    /**
     * 第三方帐号完善注册
     * @param $params
     * @return bool
     */
    public function thirdSignUpImprove($params) {
        //获取用户信息
        $info = Loader::model('User')->where(['user_third_token' => $params['user_third_token'], 'user_third_type' => $params['user_third_type']])->find();

        if (!$info) {
            $this->errorcode = EC_USER_LOGIN_USER_NONE;
            return false;
        }

        //判断帐号是否已经存在
        $ret = Loader::model('User')->where(['user_name' => $params['user_name']])->count();
        if ($ret > 0) {
            $this->errorcode = EC_USER_REG_USER_EXISTING;
            return false;
        }

        //修改用户表登录信息
        $salt = random_string();
        $data['user_name']            = $params['user_name'];
        $data['user_password']        = md5($params['password'] . $salt);
        $data['user_salt']            = $salt;
        $result = Loader::model('User')->where(['user_third_token' => $params['user_third_token'], 'user_third_type' => $params['user_third_type']])->update($data);

        if($result === false) {
            return false;
        }else {
            //记录登录日志
            $this->recordLoginLog($info['user_id'], $params['terminal']);
            return true;
        }
    }
}