<?php
 
namespace app\common\logic;

use think\Config;
use think\Loader;
use think\Db;

class Recharge extends Model{

	public function getInfoByNo($orderId){
		$condition['urr_no'] = $orderId;
		return Loader::model('UserRechargeRecord')->where($condition)->find();
	}

}