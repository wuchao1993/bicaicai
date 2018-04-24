<?php
/**
 * Passport封装类（ThinkPHP）
 * @author Ryan
 * @since  2018-01-17
 */

namespace passport;

use think\Cache;
use crypt\CryptAes;
use crypt\CryptRsa;

class Passport
{
    const OFFLINE_STATUS_NORMAL = 0;
    const OFFLINE_STATUS_OTHER = -1;
    const OFFLINE_STATUS_FORCE = -2;

    public $appName;
    public $domain;
    public $siteIdentity;
    public $redisConfig;
    private static $_actions = [
        'auth' => 'c1/user/auth',
        'login' => 'c1/user/login',
        'tryLogin' => 'c1/user/try-login',
        'thirdPartyLogin' => 'c1/third-party/auth',
        'logout' => 'c1/user/logout',
        'signup' => 'c1/user/signup',
        'trySignup' => 'c1/user/try-signup',
        'refreshToken' => 'c1/user/refresh-token',
        'changePassword' => 'c1/user/change-password',
        'systemUpdate' => 'c1/user/system-update',
        'verifyUsername' => 'c1/user/verify-username',
        'forceOffline' => 'c1/user/force-offline',
    ];
    private $_errorCode = 0;
    private $_errorMessage = '';
    private $_accessToken;
    private $_platformName = 'pc';
    private $_timestamp;
    private $_redis;
    private $_cryptAesConfig;
    private $_cryptAes;
    private $_userInfo;
    private $_identityCategory;

    public function __construct()
    {
        $config = (array)\think\Config::get('passport');
        $this->appName = $config['appName'];
        $this->domain = $config['domain'];
        $this->siteIdentity = $config['siteIdentity'];
        $this->redisConfig = $config['redisConfig'];
        $this->init();
    }

    public function init()
    {
        if (!extension_loaded('curl')) {
            throw new Exception('The PHP exention curl must be installed to use this library.', Exception::CURL_NOT_FOUND);
        }
        $this->_timestamp = time();
    }

    public function setAccessToken($token)
    {
        $this->_accessToken = $token;
        return $this;
    }

    public function getAccessToken()
    {
        return $this->_accessToken;
    }

    public function setPlatformName($platformName)
    {
        $this->_platformName = $platformName;
        return $this;
    }

    public function getPlatformName()
    {
        return $this->_platformName;
    }

    /**
     * 获取地址
     * @param string $action 动作
     * @return string|bool
     */
    public function getUrl($action)
    {
        if (isset(static::$_actions[$action])) {
            return $this->domain . static::$_actions[$action];
        }
        return false;
    }

    /**
     * 获取错误码
     * @return int
     */
    public function getErrorCode()
    {
        return $this->_errorCode;
    }

    /**
     * 获取错误详情
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    /**
     * 校验登录
     * @return bool|int
     */
    public function authToken($remote = false)
    {
        if (!$this->_assertAccessTokenNotEmpty()) {
            return false;
        }
        if ($remote) { //远程认证
            return $this->_remoteAuth();
        } else {
            return $this->_redisAuth();
        }
    }

    /**
     * 获取用户数据
     * @param mixed $fields
     * @return mixed
     */
    public function getUserInfo($fields = null)
    {
        empty($this->_userInfo) && $this->_remoteAuth();
        if (empty($this->_userInfo)) {
            return false;
        }
        return empty($fields)
            ? $this->_userInfo
            : (count($fields) == 1
                ? $this->_userInfo[current((array)$fields)]
                : array_intersect_key($this->_userInfo, array_flip($fields))
            );
    }

    /**
     * 获取用户身份类别
     * @return string
     */
    public function getIdentityCategory()
    {
        if (!empty($this->_identityCategory)) {
            return $this->_identityCategory;
        }
        return $this->getUserInfo('identityCategory');
    }

    /**
     * 登录
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $ip IP地址(IPV4)
     * @return bool
     */
    public function login($username, $password, $ip)
    {
        $this->_userInfo = $this->_executeRequest($this->getUrl('login'), $this->_packet([
            'username' => $username,
            'password' => $password,
            'ip' => $ip,
        ]));
        if (!empty($this->_userInfo['accessToken'])) {
            $this->setAccessToken($this->_userInfo['accessToken']);
            return true;
        }
        return false;
    }

    /**
     * 试玩用户登录
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $ip IP地址(IPV4)
     * @return bool
     */
    public function tryLogin($username, $password, $ip)
    {
        $this->_userInfo = $this->_executeRequest($this->getUrl('tryLogin'), $this->_packet([
            'username' => $username,
            'password' => $password,
            'ip' => $ip,
        ]));
        if (!empty($this->_userInfo['accessToken'])) {
            $this->setAccessToken($this->_userInfo['accessToken']);
            return true;
        }
        return false;
    }

    /**
     * 第三方登录
     * @param array $params 参数
     * @return bool
     */
    public function thirdPartyLogin($params)
    {
        $this->_userInfo = $this->_executeRequest($this->getUrl('thirdPartyLogin'), $this->_packet($params));
        if (!empty($this->_userInfo['accessToken'])) {
            $this->setAccessToken($this->_userInfo['accessToken']);
            return true;
        }
        return false;
    }

    /**
     * 退出登录
     * @return bool
     */
    public function logout()
    {
        if (!$this->_assertAccessTokenNotEmpty()) {
            return false;
        }
        $result = $this->_executeRequest($this->getUrl('logout'), $this->_packet());
        return false !== $result;
    }

    /**
     * 注册
     * @param array $data 信息
     * @return bool
     */
    public function signup($data)
    {
        $this->_userInfo = $this->_executeRequest($this->getUrl('signup'), $this->_packet($data));
        if (!empty($this->_userInfo['accessToken'])) {
            $this->setAccessToken($this->_userInfo['accessToken']);
            return true;
        }
        return false;
    }

    /**
     * 试玩用户注册
     * @param string $identityCategory 身份类别
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $ip IP地址(IPV4)
     * @return bool
     */
    public function trySignup($identityCategory, $username, $password, $ip)
    {
        $this->_userInfo = $this->_executeRequest($this->getUrl('trySignup'), $this->_packet([
            'identityCategory' => $identityCategory,
            'username' => $username,
            'password' => $password,
            'ip' => $ip,
        ]));
        if (!empty($this->_userInfo['accessToken'])) {
            $this->setAccessToken($this->_userInfo['accessToken']);
            return true;
        }
        return false;
    }

    /**
     * 刷新Token
     * @return bool
     */
    public function refreshToken()
    {
        if (!$this->_assertAccessTokenNotEmpty()) {
            return false;
        }
        $this->_userInfo = $this->_executeRequest($this->getUrl('refreshToken'), $this->_packet());
        if (!empty($this->_userInfo['accessToken'])) {
            $this->setAccessToken($this->_userInfo['accessToken']);
            return true;
        }
        return false;
    }

    /**
     * 修改密码
     * @param string $oldPassword 原密码
     * @param string $password 新密码
     * @return bool
     */
    public function changePassword($oldPassword, $password)
    {
        if (!$this->_assertAccessTokenNotEmpty()) {
            return false;
        }
        $result = $this->_executeRequest($this->getUrl('changePassword'), $this->_packet([
            'oldPassword' => $oldPassword,
            'password' => $password,
        ]));
        return false !== $result;
    }

    /**
     * 系统修改信息
     * @param int $uid 用户ID
     * @param array $data 信息
     * @return bool
     */
    public function systemUpdate($uid, $data)
    {
        $result = $this->_executeRequest($this->getUrl('systemUpdate'), $this->_packet(
            array_merge(['uid' => $uid], $data)
        ));
        return false !== $result;
    }

    /**
     * 校验用户名
     * @param string $username 用户名
     * @return bool
     */
    public function verifyUsername($username)
    {
        $result = $this->_executeRequest($this->getUrl('verifyUsername'), $this->_packet([
            'username' => $username,
        ]));
        return false !== $result;
    }

    /**
     * 系统强制下线
     * @param int $uid 用户UID
     * @return bool
     */
    public function forceOffline($uid)
    {
        $result = $this->_executeRequest($this->getUrl('forceOffline'), $this->_packet([
            'uid' => $uid,
        ]));
        return false !== $result;
    }

    /**
     * 远程认证接口
     * @return bool
     */
    private function _remoteAuth()
    {
        if (!$this->_assertAccessTokenNotEmpty()) {
            return false;
        }
        $this->_userInfo = $this->_executeRequest($this->getUrl('auth'), $this->_packet());
        return false !== $this->_userInfo;
    }

    /**
     * redis认证接口
     * @return bool
     */
    private function _redisAuth()
    {
        if (!$this->_assertAccessTokenNotEmpty()) {
            return false;
        }
        $this->_redis = Cache::connect($this->redisConfig, 'passport')->handler();
        $cacheKey = "passport:{$this->siteIdentity}:oauth_access_tokens:{$this->getAccessToken()}";
        $tokenInfo = $this->_redis->GET($cacheKey);
        if (empty($tokenInfo)) {
            return $this->_setErrorInfo(10010, '登录状态已过期，请重新登录');
        }
        $result = json_decode($tokenInfo, true);
        if (empty($result['scope']) || empty($result['user_id'])
            || $result['expires'] < $this->_timestamp) {
            return $this->_setErrorInfo(10010, '登录状态已过期，请重新登录');
        } elseif ($result['scope'] == self::OFFLINE_STATUS_OTHER) {
            return $this->_setErrorInfo(10011, '您的账号已在其他地方登录，您已被踢下线');
        } elseif ($result['scope'] == self::OFFLINE_STATUS_FORCE) {
            return $this->_setErrorInfo(10012, '系统已将您退出登录状态，请联系客服');
        }
        $this->_identityCategory = $result['scope'];
        return $result['user_id'];
    }

    /**
     * 获取AES加密Key信息
     */
    private function _getCryptAesConfig()
    {
        if (empty($this->_cryptAesConfig)) {
            $this->_cryptAesConfig = [
                'key' => bin2hex(openssl_random_pseudo_bytes(8)),
                'iv' => bin2hex(openssl_random_pseudo_bytes(8))
            ];
        }
        return $this->_cryptAesConfig;
    }

    /**
     * 获取AES加密类
     */
    private function _getCryptAes()
    {
        if (empty($this->_cryptAes)) {
            $this->_cryptAes = new CryptAes($this->_getCryptAesConfig());
        }
        return $this->_cryptAes;
    }

    /**
     * 获取公共header信息
     */
    private function _getHeader()
    {
        return [
            'appName' => $this->appName,
            'platformName' => $this->getPlatformName(),
            'authToken' => $this->getAccessToken(),
            'timestamp' => $this->_timestamp,
            'nonce' => bin2hex(openssl_random_pseudo_bytes(16))
        ];
    }

    /**
     * 封装包信息
     */
    private function _packet($body = [])
    {
        $result['crypt'] = (new CryptRsa())->encrypt(json_encode($this->_getCryptAesConfig(), 320));
        $result['header'] = $this->_getCryptAes()->encrypt(json_encode($this->_getHeader(), 320));
        $result['body'] = $this->_getCryptAes()->encrypt(json_encode($body, 320));
        return $result;
    }

    /**
     * 执行请求（用CURL）
     * @param string $url 地址
     * @param mixed $params Array of parameters
     * @return array
     */
    private function _executeRequest($url, $params = [])
    {
        $params = json_encode($params, 320);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type:application/json',
                'Content-Length:' . strlen($params),
            ],
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $params,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!empty($curlError = curl_error($ch))) {
            throw new Exception($curlError, Exception::CURL_ERROR);
        } else {
            $result = json_decode($result, true);
        }
        curl_close($ch);
        if (!isset($result['code'])
            || !isset($result['message'])
            || !isset($result['data'])) {
            return $this->_setErrorInfo(9999, '返回数据格式错误');
        }
        if (intval($result['code']) !== 0) {
            return $this->_setErrorInfo($result['code'], $result['message']);
        }
        if (is_array($result['data']) && !empty($result['data']['message'])) {
            return $this->_setErrorInfo(9999, $result['data']['message']);
        }
        return json_decode($this->_getCryptAes()->decrypt($result['data']), true);
    }

    /**
     * 断言accessToken非空
     * @return bool
     */
    private function _assertAccessTokenNotEmpty(){
        if (empty($this->_accessToken)) {
            return $this->_setErrorInfo(10007, '用户未登录');
        }
        return true;
    }

    /**
     * 设置错误信息
     * @param int $code
     * @param string $message
     * @return bool
     */
    private function _setErrorInfo($code, $message)
    {
        $this->_errorCode = $code;
        $this->_errorMessage = $message;
        return false;
    }
}

class Exception extends \Exception
{
    const CURL_NOT_FOUND = 0x01;
    const CURL_ERROR = 0x02;
}
