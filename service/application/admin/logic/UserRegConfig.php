<?php
/**
 * 用户注册优惠逻辑
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class UserRegConfig extends Model {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取用户信息
     * @param $params
     * @return array
     */
    public function getInfo()
    {
    	$info = Loader::model('UserRegConfig')->find();
    	
    	return $info;
    }
    
    /**
     * 编辑用户注册优惠
     * @param $params
     * @return bool
     */
    public function editRegConfig($params) {
                
        //修改表信息
        $updateData['urc_id']		            = $params['urc_id'];
        $updateData['urc_is_discount']          = $params['urc_is_discount'];
        $updateData['urc_discount_amount']      = $params['urc_discount_amount'];
        $updateData['urc_type']                 = implode(',', $params['urc_type']);
        $updateData['urc_check_amount']         = $params['urc_check_amount'];
        $updateData['urc_remark']               = $params['urc_remark'];
        $updateData['urc_ip_day_limit']         = $params['urc_ip_day_limit'];
        $updateData['urc_isonly_general_agent'] = $params['urc_isonly_general_agent'];
        $updateData['urc_reg_ip_isrepeat']      = $params['urc_reg_ip_isrepeat'];
        $updateData['urc_regip_bindip_issame']  = $params['urc_regip_bindip_issame'];

        Loader::model('UserRegConfig')->insert($updateData,true);
        
        return true;
    }
     
}