<?php

namespace app\admin\logic;

use think\Loader;

class AuthGroupAccess {

    public $errorcode = EC_SUCCESS;

    public function getInfoByUid($uid) {
        $condition = [
            'uid' => $uid
        ];

        return Loader::model('AuthGroupAccess')->field('group_id')->where($condition)->order('group_id asc')->select();
    }

}