<?php
namespace app\common\model;

use think\Config;
use think\Model;

class UserLevel extends Model{

    public $pk = 'ul_id';

    public function getDefaultLevelId(){
        $condition = [
            'ul_default' => 1,
            'ul_status'  => Config::get('status.user_level_status')['normal'],
        ];

        return $this->where($condition)->value('ul_id');
    }


    public function addUserCount($userLevelId){
        $condition = [
            'ul_id' => $userLevelId,
        ];

        return $this->where($condition)->setInc('ul_user_count');
    }

}