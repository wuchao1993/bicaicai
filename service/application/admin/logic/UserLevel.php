<?php
/**
 * 用户层级逻辑
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;

class UserLevel extends Model {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_AD_SUCCESS;


    public function setIncUserCountByUlId($ulId,$count=1){

        $condition = [];

        $condition['ul_id'] = $ulId;

        return $this->where($condition)->setInc('ul_user_count',$count);

    }

    public function setDecUserCountByUlId($ulId,$count=1){

        $condition = [];

        $condition['ul_id'] = $ulId;

        return $this->where($condition)->setDec("ul_user_count",$count);

    }

    public function getDefaultLevelId(){

        $condition = [];

        $condition['ul_default'] = Config::get('status.user_level_default')['yes'];
        $condition['ul_status'] = Config::get('status.user_level_status')['normal'];

        return $this->where($condition)->value('ul_id');
    }

    public function getUserCountByUlid($ulId){

        $condition = [];
        $condition['ul_id'] = $ulId;

        return $this->where($condition)->value('ul_user_count');
    }

    public function updateUserCount($ulId,$userCount){

        $condition = [];
        $condition['ul_id'] = $ulId;

        $data = [];
        $data['ul_user_count'] = $userCount;

        return $this->save($data,$condition);

    }

}