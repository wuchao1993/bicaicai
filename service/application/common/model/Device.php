<?php

namespace app\common\model;

use think\Model;

class Device extends Model
{

    public $pk = 'id';
    
    public function getInfoByUniqueId($uniqueId)
    {
        $condition = [
            'unique_id' => $uniqueId
        ];

        return $this->where($condition)->find();
    }


    public function updateUserInfo($uniqueId, $userId){
        $condition = [
            'unique_id' => $uniqueId
        ];

        $this->where($condition)->update(['user_id' => $userId]);
    }

}