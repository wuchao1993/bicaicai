<?php

namespace app\common\model;

use think\Model;

class PayConfig extends Model{


    public function getPayConfigByLevel($userLevel){
        $condition = [
            'ul_id' => $userLevel,
            'pc_status' => 1
        ];

        return $this->where($condition)->find();
    }


}