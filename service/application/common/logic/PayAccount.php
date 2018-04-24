<?php

namespace app\common\logic;

use think\Config;
use think\Loader;

class PayAccount {

    public $errorcode = EC_SUCCESS;

    public function getQrCodeInfo($payAccountIds, $rechargeTypeId) {
        if($payAccountIds && $rechargeTypeId) {
            $condition = [
                'pa_status' => Config::get('status.pay_account_status')['enable'],
                'pa_id'     => [
                    'in',
                    $payAccountIds
                ]
            ];

            if($rechargeTypeId == PAY_PLATFORM_CATEGORY_ALIPAY_FRIEND) {
                $condition['bank_id'] = BANK_ID_ALIPAY;
            } else if($rechargeTypeId == PAY_PLATFORM_CATEGORY_WEIXIN_FRIEND) {
                $condition['bank_id'] = BANK_ID_WEIXIN;
            } else {
                return false;
            }

            return Loader::model('PayAccount')->where($condition)->find();
        }
    }


    public function getCompanyBankList() {
        $userId      = USER_ID;
        $userInfo    = Loader::model('User', 'logic')->getInfo($userId);
        $userLevelId = $userInfo['ul_id'];

        $payAccountIds  = Loader::model('PayAccountUserLevelRelation', 'logic')->getPayAccountIds($userLevelId);
        $bankList       = Loader::model('Bank', 'logic')->getList();
        $payAccountList = Loader::model('PayAccount', 'logic')->getListByIds($payAccountIds);

        $response = [];
        foreach($payAccountList as $payAccount) {
            $temp                   = [];
            $temp['id']             = $payAccount['pa_id'];
            $temp['account']   = $payAccount['pa_collection_account'];
            $temp['userName'] = $payAccount['pa_collection_user_name'];
            $bankInfo               = $bankList[$payAccount['bank_id']];
            $bankImage              = build_website($bankInfo['bank_image_mobile']);
            $bankName               = $bankInfo['bank_name'];

            $temp['image'] = $bankImage;
            $temp['name']  = $bankName;
            $temp['code']  = $bankInfo['bank_code'];
            $response[]         = $temp;
        }

        return $response;
    }


    public function getListByIds($ids) {
        $condition = [
            'pa_id'     => [
                'in',
                $ids
            ],
            'pa_status' => 1
        ];

        return Loader::model('PayAccount')->where($condition)->select();
    }

}