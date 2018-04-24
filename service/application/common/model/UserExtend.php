<?php
/**
 * 用户扩展表
 * @createTime 2017/4/6 17:39
 */

namespace app\common\model;

use think\Model;

class UserExtend extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'user_id';

    public function getUserBalance($userId){
        return $this->where(['user_id' => $userId])->value('ue_account_balance');
    }

}