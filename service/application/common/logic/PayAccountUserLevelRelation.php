<?php

namespace app\common\logic;

use think\Model;
use think\Loader;

class PayAccountUserLevelRelation{

    public function getPayAccountIds($userLevelId){
        $condition['user_level_id'] = $userLevelId;
        $pay_account_ids = Loader::model('PayAccountUserLevelRelation')->where($condition)->column('pay_account_id');
        return $pay_account_ids;
    }
}