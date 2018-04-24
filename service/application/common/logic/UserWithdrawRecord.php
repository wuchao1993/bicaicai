<?php

namespace app\common\logic;

use think\Config;
use think\Loader;

class UserWithdrawRecord{

    public function getLatelyRecords($userId, $startDate){
        $condition = [
            'user_id' => $userId,
            'uwr_createtime' => ['egt', $startDate],
            'uwr_status' => ['elt', Config::get('status.withdraw_status')['confirm']]
        ];

        return Loader::model('UserWithdrawRecord')->where($condition)->select();
    }

}