<?php
namespace app\admin\logic;

use think\Loader;
use think\Model;

class PayAccountUserLevelRelation extends Model
{

    public $errorcode = EC_SUCCESS;

    public function getPayAccountIds($user_level_id)
    {
        if($user_level_id){
            $condition = array();
            $condition['user_level_id'] = $user_level_id;
            $list = Loader::model('PayAccountUserLevelRelation')->where($condition)->field('pay_account_id')->select();
            
            $result = array();
            foreach ($list as $val){
                $result[] = $val['pay_account_id'];
            }
            
            return $result;
        }
    }
}