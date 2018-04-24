<?php

namespace app\api\logic;

use think\Cache;
use think\Loader;
use think\Model;
use think\Config;

class UserConfig extends Model {

    public $errorcode = EC_SUCCESS;

    public function bindRealInfo($realName, $fundsPassword) {
        $userId = USER_ID;
        if(!$userId) {
            $this->errorcode = EC_USER_JWT_EXP_ERROR;

            return;
        }

        $userInfo = Loader::model('User', 'logic')->getInfo($userId);

        if($userInfo['user_realname'] && $userInfo['user_funds_password']) {
            $this->errorcode = EC_USER_REAL_INFO_IS_BIND;

            return;
        } else {
            $salt                        = random_string(8);
            $enPassword                  = encrypt_password($fundsPassword, $salt);
            $data                        = [];
            $data['user_realname']       = $realName;
            $data['user_funds_password'] = $enPassword;
            $data['user_funds_salt']     = $salt;
            $result                      = Loader::model('User')->save($data, ['user_id' => $userId]);
            if($result) {
                $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'user:user_info_' . $userId;
                Cache::rm($cacheKey);
                $this->errorcode = EC_SUCCESS;
            } else {
                $this->errorcode = EC_USER_REAL_INFO_BIND_FAIL;
            }
        }
    }


    public function bindCard($params) {
        $bankId        = $params['id'];
        $address       = $params['address'];
        $cardNumber    = $params['cardNumber'];
        $userId        = USER_ID;

        if(!$userId) {
            $this->errorcode = EC_USER_JWT_EXP_ERROR;

            return;
        }

        $bankMap = Loader::model('Bank', 'logic')->getMap();
        $bankIds = array_keys($bankMap);
        if(!in_array($bankId, $bankIds)) {
            $this->errorcode = EC_NOT_EXIST_BANK;

            return;
        }

        $bankAccountCount = Loader::model('UserBankRelation', 'logic')->getBankAccountCount($cardNumber);
        if($bankAccountCount >= 1) {
            $this->errorcode = EC_EXIST_BANK_ACCOUNT;

            return;
        }

        $bankCardCount = Loader::model('UserBankRelation', 'logic')->getUserAccountCount($userId);
        if($bankCardCount >= 1) {
            $this->errorcode = EC_BANK_CARD_OVER_LIMIT;

            return;
        }

        $userInfo          = Loader::model('User', 'logic')->getInfo($userId);
        $userFundsPassword = $userInfo['user_funds_password'];
        $userRealName      = $userInfo['user_realname'];
        if(empty($userFundsPassword)) {
            $this->errorcode = EC_NO_SET_FUNDS_PASSWORD;

            return;
        } else {
            $data                      = [];
            $data['user_id']           = $userId;
            $data['bank_id']           = $bankId;
            $data['ub_bank_account']   = $cardNumber;
            $data['ub_bank_user_name'] = $userRealName;
            $data['ub_address']        = $address;
            $data['ub_createtime']     = current_datetime();
            if($bankCardCount < 1) {
                $data['ub_is_default'] = 1;
            }
            $result = Loader::Model('UserBankRelation')->save($data);
            if($result == false) {
                $this->errorcode = EC_BIND_FUNDS_PASSWORD_ERROR;

                return;
            }
        }

    }

}