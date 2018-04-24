<?php
namespace app\admin\logic;

use think\Loader;
use think\Model;

class PayBankRelation extends Model
{

    public $errorcode = EC_SUCCESS;

    public function getConfigList($channelId,$type)
    {
        $condition['pay_type_id'] = $channelId;
        $condition['pay_type'] = $type;
        $list = Loader::model('PayBankRelation')->where($condition)->select();

        return $list;
    }


    public function editConfig($configInfo)
    {
        Loader::model('PayBankRelation')->insert($configInfo, true);
        
        return true;
    }
}