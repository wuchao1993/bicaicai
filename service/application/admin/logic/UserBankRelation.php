<?php
/**
 * 用户银行信息相关
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;

class UserBankRelation extends Model {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取用户银行信息
     * @param $userId
     * @return array
     */
    public function getList($userId) {
        $condition            = array();
        $condition['user_id']   = $userId;
        $condition['ub_status'] = USER_BANK_STATUS_ENABLE;

        $info = Loader::model('UserBankRelation')->where($condition)->select();

        if(!empty($info)){
        return $info;
        }
        else{
            return [];
        }
    }

    public function getAllBankRelations(){

        // $condition            = array();
        // $condition['ub_status']   = Config::get('status.user_bank_status')['enable'];
        // $info = Loader::model('UserBankRelation')->where($condition)->select();

        $condition = [
            // 'ubr.user_id' => $uid,
            'ubr.ub_status' => Config::get('status.user_bank_status')['enable']
        ];
        $info      = Loader::model('UserBankRelation')->alias('ubr')->join('Bank b', 'ubr.bank_id=b.bank_id', 'LEFT')->field('ubr.ub_id,ubr.user_id,b.bank_name,ubr.ub_bank_account,ubr.ub_bank_user_name,ubr.ub_address')->where($condition)->select();

        if(!empty($info)){
            return $info;
        }
        else{
            return [];
        }
    }

    public function modifyBankUserName($userId,$userRealName){

            $condition = [];
            $condition['user_id'] = $userId;

            $data = [];
            $data['ub_bank_user_name'] = $userRealName;

            return $this->save($data,$condition);

    }

}