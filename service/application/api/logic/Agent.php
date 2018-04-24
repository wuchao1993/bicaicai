<?php

namespace app\api\logic;

use passport\Passport;
use think\Loader;
use think\Config;
use Filipac\Ip;
use think\Db;
use think\Log;

class Agent {

    public $errorcode = EC_SUCCESS;

    public $message = '';

    public function applyAgent($params) {
        $userName = $params['userName'];
        $password = $params['password'];
        $captcha  = $params['captcha'];
        $mobile   = $params['mobile'];
        $email    = $params['email'];
        $contact  = $params['contact'];
        $qq       = $params['qq'];
        $terminal = $params['terminal'] ? $params['terminal'] : 'unknown';
        $channel  = $params['channel'];

        if(!captcha_check($captcha)) {
            $this->errorcode = EC_USER_REG_CAPTCHA_ERROR;
            return false;
        };

        $defaultLevelId = Loader::model('UserLevel')->getDefaultLevelId();
        if(empty($defaultLevelId)) {
            $this->errorcode = EC_CM_LACK_USER_DEFAULT_LEVEL;
            return false;
        }

        $domain      = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
        $domainInfo  = Loader::model('AgentDomain')->getInfoByDomain($domain);
        $registerWay = $domainInfo ? Config::get('status.user_register_way')['domain'] : Config::get('status.user_register_way')['common'];

        $channelId = 0;
        if($channel) {
            $channelId = Loader::model('Channel')->getChannelId($channel);
        }

        $salt         = random_string(8);
        $password     = encrypt_password($password, $salt);
        $userPid      = 0;
        $rebateConfig = Loader::model('LotteryCategory')->getDefaultRebateMap();
        $agentInfo    = [
            'user_pid'                => $userPid,
            'user_grade'              => 0,
            'ul_id'                   => $defaultLevelId,
            'user_name'               => $userName,
            'user_mobile'             => $mobile,
            'user_email'              => $email,
            'user_password'           => $password,
            'user_salt'               => $salt,
            'user_is_agent'           => Config::get('status.user_is_agent')['yes'],
            'user_reg_ip'             => Ip::get(),
            'user_createtime'         => current_datetime(),
            'user_status'             => Config::get('status.user_status')['enable'],
            'user_agent_check_status' => Config::get('status.agent_check_status')['wait'],
            'reg_terminal'            => Config::get('status.reg_terminal')[$terminal],
            'reg_way'                 => $registerWay,
            'user_reg_url'            => $domain,
            'user_contact_info'       => $contact,
            'channel_id'              => $channelId,
            'user_all_pid'            => $userPid
        ];
        if($qq) {
            $agentInfo['user_qq'] = $qq;
        }

        //到用户中心注册
        $passport = new Passport();
        $userCenterData = [
            'username' => $userName,
            'password' => $params['password'],
            'email'    => $email,
            'mobile'   => $mobile,
            'qq'       => $qq,
            'ip'       => Ip::get(),
        ];
        $result = $passport->setPlatformName($terminal)->signup($userCenterData);
        if($result) {
            $accessToken          = $passport->getAccessToken();
            $userInfo             = $passport->getUserInfo();
            $agentInfo['user_id'] = $userInfo['uid'];
        } else {
            $this->errorcode = $passport->getErrorCode();
            $this->message   = $passport->getErrorMessage();
            return false;
        }

        $userId = $this->_addUser($agentInfo, $rebateConfig);
        if($userId === false) {
            $this->errorcode = EC_DATABASE_ERROR;
            return false;
        }

        //生成Token
        $userInfo = [
            'token'           => $accessToken,
            'uid'             => $userId,
            'user_name'       => $userName,
            'is_agent'        => Config::get('status.user_is_agent')['no'],
            'account_balance' => Loader::model('UserExtend')->getUserBalance($userId),
        ];

        //记录登录日志
        Loader::model('User', 'logic')->recordLoginLog($userId, $params['terminal']);

        return $userInfo;
    }

    public function registerSubordinate($params) {
        $invitationCode = $params['invitationCode'];
        $userName       = $params['userName'];
        $password       = $params['password'];
        $terminal       = $params['terminal'] ? $params['terminal'] : 'mobile';
        $mobile         = $params['mobile'];
        $email          = $params['email'];
        $channel        = $params['channel'];
        $contact        = $params['contact'];
        $qq             = $params['qq'];
        $deviceUniqueId = $params['deviceUniqueId'];
        $captcha        = $params['captcha'];

        if(!captcha_check($captcha)) {
            $this->errorcode = EC_USER_REG_CAPTCHA_ERROR;

            return false;
        };

        $isRegisterAllow = Loader::model('Config', 'logic')->checkRegisterAllow;
        if($isRegisterAllow === false) {
            $this->errorcode = EC_CM_USER_REGISTER_ALLOW_FALSE;

            return false;
        }

        $userExist = Loader::model('User')->getInfoByUserName($userName);
        if($userExist) {
            $this->errorcode = EC_AD_REG_USER_EXISTING;

            return false;
        }

        $defaultLevelId = Loader::model('UserLevel')->getDefaultLevelId();
        if(empty($defaultLevelId)) {
            $this->errorcode = EC_CM_LACK_USER_DEFAULT_LEVEL;

            return false;
        }

        $currentIpUserCount = Loader::model('User')->getUserCountByIp(Ip::get());
        $userRegisterLimit  = Loader::model('Config')->getConfig('USER_REG_IP_LIMIT');
        if($userRegisterLimit && $currentIpUserCount >= $userRegisterLimit) {
            $this->errorcode = EC_CM_LACK_USER_DEFAULT_LEVEL;

            return false;
        }

        $channelId = 0;
        if($channel) {
            $channelId = Loader::model('Channel')->getChannelId($channel);
        }

        $salt     = random_string(Config::get('common.user_register_salt_length'));
        $password = encrypt_password($password, $salt);

        if($invitationCode) {
            $agentLinkInfo = Loader::model('AgentLink')->getInfoByCode($invitationCode, Config::get('status.agent_link_type')['agent']);
            if($agentLinkInfo === false) {
                $this->errorcode = EC_AGENT_INVITATION_CODE_ERROR;

                return false;
            }

            $userPid      = $agentLinkInfo['user_id'];
            $rebateConfig = Loader::model('AgentLinkRebate')->getLinkRebate($agentLinkInfo['agl_id']);
            $registerWay  = Config::get('status.user_register_way')['code'];
        } else {
            $domain            = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
            $domainInfo        = Loader::model('AgentDomain')->getInfoByDomain($domain);
            $userPid           = $domainInfo['user_id'];
            $userRegisterUrlId = $domainInfo['agd_id'];
            $rebateConfig      = Loader::model('AgentDomainRebate')->getRebateByUserId($userPid, $domainInfo['agd_id']);
            $registerWay       = Config::get('status.user_register_way')['domain'];
        }

        $upUserInfo = Loader::model('User')->getInfo($userPid);
        $userGrade  = $upUserInfo['user_grade'] + 1;
        $userAllPid = $upUserInfo['user_all_pid'] ? $upUserInfo['user_all_pid'] . "," . $userPid : $userPid;

        $agentInfo = [
            'user_pid'                => $userPid,
            'user_grade'              => $userGrade,
            'ul_id'                   => $defaultLevelId,
            'user_name'               => $userName,
            'user_mobile'             => $mobile,
            'user_email'              => $email,
            'user_password'           => $password,
            'user_salt'               => $salt,
            'user_is_agent'           => Config::get('status.user_is_agent')['yes'],
            'user_reg_ip'             => Ip::get(),
            'user_createtime'         => current_datetime(),
            'user_status'             => Config::get('status.user_status')['enable'],
            'user_agent_check_status' => Config::get('status.agent_check_status')['past'],
            'reg_terminal'            => Config::get('status.reg_terminal')[$terminal],
            'reg_way'                 => $registerWay,
            'user_reg_url'            => $userRegisterUrlId,
            'user_qq'                 => $qq,
            'user_contact_info'       => $contact,
            'channel_id'              => $channelId,
            'user_all_pid'            => $userAllPid
        ];

        //到用户中心注册
        $userCenterData = [
            'username' => $userName,
            'password' => $params['password'],
            'email'    => $email,
            'mobile'   => $mobile,
            'qq'       => $qq,
            'ip'       => Ip::get(),
        ];
        $passport       = new Passport();
        $result         = $passport->setPlatformName($terminal)->signup($userCenterData);
        if($result) {
            $userInfo             = $passport->getUserInfo();
            $agentInfo['user_id'] = $userInfo['uid'];
        } else {
            $this->errorcode = $passport->getErrorCode();
            $this->message   = $passport->getErrorMessage();

            return false;
        }

        $userId = $this->_addUser($agentInfo, $rebateConfig);

        if($userId === false) {
            $this->errorcode = EC_DATABASE_ERROR;

            return false;
        }

        //统计信息
        if($registerWay == Config::get('status.user_register_way')['code']) {
            Loader::model('AgentLink')->decreaseUseCount($agentLinkInfo['agl_id']);
        } else if($registerWay == Config::get('status.user_register_way')['domain']) {
            Loader::model('AgentDomain')->decreaseUseCount($domainInfo['agd_id']);
        }

        Loader::model('User')->addLowerCount($userPid);
        Loader::model('UserLevel')->addUserCount($defaultLevelId);

        if($deviceUniqueId) {
            Loader::model('Device')->updateUserInfo($deviceUniqueId, $userId);
        }
    }


    public function getIntro() {
        $condition = [
            'document_title'  => [
                'in',
                [
                    '代理协议',
                    '代理方案'
                ]
            ],
            'document_type'   => Config::get('status.document_type')['agent'],
            'document_status' => Config::get('status.document_status')['enable']
        ];
        $documents = Loader::model('Document')->where($condition)->order('document_sort asc')->select();
        if($documents == false) {
            $this->errorcode = EC_AGENT_INTRO_CONFIG_ERROR;

            return false;
        }
        $response = [];
        foreach($documents as $document) {
            $response[] = [
                'image'   => $document['document_image'],
                'content' => $document['document_content'],
                'title'   => $document['document_title']
            ];
        }

        return $response;
    }


    public function getAgentInfo($params) {
        $userId   = USER_ID;
        $userInfo = Loader::model('User')->getInfo($userId);
        if($userInfo['user_is_agent'] == Config::get('status.user_is_agent')['no']) {
            $this->errorcode = EC_AGENT_STATS_FALSE;

            return false;
        }
        $subordinateInfo = Loader::model('User')->getSubordinateInfo($userId);
        $yesterdayInfo   = Loader::model('AgentDayStatistics')->getInfoByDate($userId, date('Ymd', strtotime('-1 day')));
        $response        = [
            "subordinateCount"       => $subordinateInfo['count'],
            "subordinateBalance"     => (double)$subordinateInfo['balance'],
            "yesterdayTotalRecharge" => (double)$yesterdayInfo['ads_recharge'],
            "yesterdayTotalBet"      => (double)$yesterdayInfo['ads_bet'],
        ];

        return $response;
    }


    public function getAgentStatistics($params) {
        $userId   = USER_ID;
        $userInfo = Loader::model('User')->getInfo($userId);
        if($userInfo['user_is_agent'] == Config::get('status.user_is_agent')['no']) {
            $this->errorcode = EC_AGENT_STATS_FALSE;

            return false;
        }
        $statisticsInfo                   = Loader::model('AgentDayStatistics')->statisticsBetweenDate($userId, $params['startDate'], $params['endDate']);
        $userCount                        = Loader::model('User')->getRegisterUserCountBetweenDateFromAgentInvite($userId, $params['startDate'], $params['endDate']);
        $statisticsInfo['userCount']      = $userCount;
        $statisticsInfo['validUserCount'] = $this->_getValidUserCount($userId, $params['startDate'], $params['endDate']);

        return $statisticsInfo;
    }


    private function _getValidUserCount($userId, $startDate, $endDate) {
        $startDate          = date('Ymd', strtotime($startDate));
        $endDate            = date('Ymd', strtotime($endDate));
        $result             = Loader::model('UserDayStatistics')->getSubordinateStatistics($userId, $startDate, $endDate);
        $validUserCount     = 0;
        $validUserCondition = Config::get('sports.valid_user_condition');
        if($result) {
            foreach($result as $info) {
                if($info['recharge'] >= $validUserCondition['recharge_amount'] && $info['bet'] >= $validUserCondition['bet_amount']) {
                    $validUserCount += 1;
                }
            }
        }

        return $validUserCount;
    }


    public function getAgentDayStatistics($params) {
        $userId   = USER_ID;
        $userInfo = Loader::model('User')->getInfo($userId);
        if($userInfo['user_is_agent'] == Config::get('status.user_is_agent')['no']) {
            $this->errorcode = EC_AGENT_STATS_FALSE;

            return false;
        }

        $agentDayStatisticsList = Loader::model('AgentDayStatistics')->getAgentDayStatisticsList($userId, $params['startDate'], $params['endDate']);
        if($agentDayStatisticsList) {
            foreach($agentDayStatisticsList as &$info) {
                $info['date'] = date('Y-m-d', strtotime($info['date']));
            }
        }

        return $agentDayStatisticsList;
    }


    public function getTeamList($params) {
        $startDate = date('Ymd', strtotime($params['startDate']));
        $endDate   = date('Ymd', strtotime($params['endDate']));
        $page      = $params['page'] ? $params['page'] : 1;
        $count     = $params['count'] ? $params['count'] : 10;
        $userId    = USER_ID;
        $condition = [
            'user_pid' => $userId,
            'uds_date' => [
                'between',
                [
                    $startDate,
                    $endDate
                ]
            ],
        ];

        $fields = [
            "user_id",
            "sum(uds_recharge)"    => "total_recharge",
            "sum(uds_withdraw)"    => "total_withdraw",
            "sum(uds_bet)"         => "total_bet",
            "sum(uds_bonus)"       => "total_bonus",
            "sum(uds_discount)"    => "total_discount",
            "sum(uds_rebate)"      => "bet_rebate",
            "sum(uds_team_profit)" => "profit",
        ];

        $statisticsSql = Loader::model("UserDayStatistics")->where($condition)->field($fields)->group('user_id')->page($page)->limit($count)->buildSql();
        $result        = Db::table('ds_user')->alias('u')->join([$statisticsSql => 's'], 'u.user_id = s.user_id')->field('user_name,user_nickname,s.*')->select();

        return collection($result)->toArray();
    }


    /**
     * 获取下级用户/代理信息
     * @param $params
     * @return array
     */
    public function getSubordinateUserInfo($params) {
        $userId         = $params['userId'];
        $accountBalance = Loader::model('UserExtend')->getUserBalance($userId);

        return [
            'balance' => $accountBalance
        ];
    }

    public function createSubordinate($params) {
        $userName = $params['userName'];
        $type     = $params['type'];

        $defaultLevelId = Loader::model('UserLevel')->getDefaultLevelId();
        if(empty($defaultLevelId)) {
            $this->errorcode = EC_CM_LACK_USER_DEFAULT_LEVEL;
            return false;
        }

        $userPid    = USER_ID;
        $upUserInfo = Loader::model('User')->getInfo($userPid);

        if($upUserInfo['user_is_agent'] != Config::get('status.user_is_agent')['yes']) {
            $this->errorcode = EC_SUBORDINATE_REGISTER_CLOSED;
            return false;
        }

        if($type == 1) {
            $isCloseSubAgentRegister = Loader::model('Config')->getConfig('IS_CLOSE_SUBAGENT');
            if($isCloseSubAgentRegister) {
                $this->errorcode = EC_SUBORDINATE_REGISTER_CLOSED;
                return false;
            }
            $isAgent = Config::get('status.user_is_agent')['yes'];
        } else {
            $isAgent = Config::get('status.user_is_agent')['no'];
        }
        $userGrade    = $upUserInfo['user_grade'] + 1;
        $rebateConfig = Loader::model('LotteryCategory')->getDefaultRebateMap();
        $userAllPid   = $upUserInfo['user_all_pid'] ? $upUserInfo['user_all_pid'] . "," . $userPid : $userPid;

        //到用户中心注册
        $passport = new Passport();
        $userCenterData = [
            'username' => $userName,
            'password' => Config::get('status.create_subordinate_default_password'),
            'ip'       => Ip::get(),
        ];
        $result = $passport->setPlatformName('unknown')->signup($userCenterData);
        if($result) {
            $userCenterData = $passport->getUserInfo();
        } else {
            $this->errorcode = $passport->getErrorCode();
            $this->message   = $passport->getErrorMessage();
            return false;
        }

        $userInfo = [
            'user_id'                 => $userCenterData['uid'],
            'user_pid'                => $userPid,
            'user_grade'              => $userGrade,
            'ul_id'                   => $defaultLevelId,
            'user_name'               => $userName,
            'user_is_agent'           => $isAgent,
            'user_reg_ip'             => Ip::get(),
            'user_createtime'         => current_datetime(),
            'user_status'             => Config::get('status.user_status')['enable'],
            'user_agent_check_status' => $isAgent ? Config::get('status.agent_check_status')['past'] : 0,
            'user_all_pid'            => $userAllPid
        ];

        $this->_addUser($userInfo, $rebateConfig);
    }


    private function _addUser($info, $rebateConfig) {
        Db::startTrans();
        if(Loader::model('User')->insert($info)) {
            $extendResult = Loader::model('UserExtend')->save(['user_id' => $info['user_id']]);
            if($extendResult == false) {
                Db::rollback();

                return false;
            } else {
                $addConfigResult = Loader::model('UserAutoRebateConfig', 'logic')->addUserRebateConfig($rebateConfig, $info['user_id'], $info['user_pid']);
                if($addConfigResult !== false) {
                    Db::commit();

                    Loader::model('User')->addLowerCount($info['user_pid']);
                    Loader::model('UserLevel')->addUserCount($info['ul_id']);

                    return $info['user_id'];
                } else {
                    Db::rollback();

                    return false;
                }
            }
        } else {
            Db::rollback();

            return false;
        }
    }
}