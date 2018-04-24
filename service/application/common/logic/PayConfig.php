<?php

namespace app\common\logic;

use think\Loader;
use think\Model;

class PayConfig extends Model{

    public function getInfoByUserLevelId($userLevelId){
        $condition = [
            'ul_id' => $userLevelId
        ];

        return Loader::model('PayConfig')->where($condition)->find();
    }

}