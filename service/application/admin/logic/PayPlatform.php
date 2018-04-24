<?php
namespace app\admin\logic;

use think\Loader;
use think\Model;

class PayPlatform extends Model
{

    public $errorcode = EC_SUCCESS;

    public function getList($status = '', $userLevelId = '', $page = 1, $num = 10, $channelId = '',$type = 1)
    {
        $condition = [];
        if($type == 1){
            $condition['pp.pp_category_id'] = array('neq',PAY_PLATFORM_GOTOPAY);
        }else{
            $condition['pp.pp_category_id'] = array('eq',PAY_PLATFORM_GOTOPAY);
        }
        if($status != ''){
            $condition['pp.pp_status'] = $status;
        }else{
            $condition['pp.pp_status'] = array('neq',2);;
        }

        if($channelId != ''){
            $condition['pt.pay_type_id'] = $channelId;
        }

        if ($userLevelId != '') {
            $payPlatformIds = Loader::model('PayPlatformUserLevelRelation')->getPlatformIds($userLevelId);
            $condition['pp.pp_id'] = ['in', $payPlatformIds];
        }

        $list = Loader::model('PayPlatform')
                ->alias('pp')
                ->join('PayType pt','pt.pay_type_id=pp.pay_type_id', 'LEFT')
                ->field('pp.*,pt.pay_type_name')
                ->where($condition)
                ->limit($num)
                ->page($page)
                ->order('pp.pp_status desc,pp.pay_type_id desc,pp.pp_sort asc')
                ->select();

        $count = Loader::model('PayPlatform')
                ->alias('pp')
                ->join('PayType pt','pt.pay_type_id=pp.pay_type_id', 'LEFT')
                ->where($condition)
                ->count();
        
        if(!empty($list)) {
            foreach ($list as &$val) {
                $condition = [];
                $condition['ppu.pay_platform_id'] = $val['pp_id'];
                $levelList = Loader::model('PayPlatformUserLevelRelation')
                            ->alias('ppu')
                            ->join('UserLevel ul','ul.ul_id=ppu.user_level_id', 'LEFT')
                            ->field('ul.ul_id as id,ul.ul_name as name')
                            ->where($condition)
                            ->select();
                $val['levelList'] = $levelList;
            }
        }
        
        return [
            'list' => $list,
            'count' => $count
        ];
    }
    public function getInfo($id){
        $condition['pp_id'] = $id;
        return Loader::model('PayPlatform')->where($condition)->find()->toArray();
    }

    public function getListByUserLevelId($user_level_id, $pp_category_id,$amount){
        $pp_category_id = $pp_category_id ? $pp_category_id : 1;
        $condition = array();
        $condition['ppu.user_level_id']     = $user_level_id;
        $condition['pp.pp_category_id']    = intval($pp_category_id);
        $condition['pp.pp_status']         = PAY_PLATFORM_STATUS_ENABLE;
        $condition['pp.pp_min_pay_money']  = array('ELT',$amount);
        $payPlatFormInfo = Loader::model('PayPlatform')
                            ->alias('pp')
                            ->join('PayPlatformUserLevelRelation ppu','pp.pp_id = ppu.pay_platform_id','LEFT')
                            ->where($condition)
                            ->order('pp.pp_sort desc')
                            ->select();
        return $payPlatFormInfo;
    }

    public function editInfo($info){
        
        $ulId = $info['ulId'];
        unset($info['ulId']);
        
        if(isset($info['pp_id'])){
            $result = Loader::model('PayPlatform')->save($info, ['pp_id'=>$info['pp_id']]);
            $result = $info['pp_id'];
        }else{
            $result = Loader::model('PayPlatform')->save($info);
            $result = Loader::model('PayPlatform')->pp_id;
        }
        
        Loader::model('PayPlatformUserLevelRelation')->where(['pay_platform_id'=>$result])->delete();
        
        if(!empty($ulId)) {
            foreach($ulId as $val) {
                $data = [];
                $data['pay_platform_id'] = $result;
                $data['user_level_id']   = $val;
                Loader::model('PayPlatformUserLevelRelation')->insert($data,true);
            }
        }
        
        return true;
    }
    
    public function getPayPlatformSearchList()
    {
        $list = Loader::model('PayPlatform')
        ->alias('pp')
        ->join('PayType pt','pp.pay_type_id=pt.pay_type_id', 'LEFT')
        ->field('pp.pp_id,pp.pay_type_id,pp.pp_category_id,pt.pay_type_name,pp.pp_account_no')
        ->select();
        
        return $list;
    }
    
    /**
     * 删除
     * @param $params
     * @return bool
     */
    public function del($params) 
    {
        $ret = Loader::model('PayPlatform')->where(['pp_id' => $params['pp_id']])->delete();
        
        return $ret;
    }
    
    /**
     * 修改状态
     * @param $params
     * @return bool
     */
    public function changeStatus($params)
    {
        $updateData['pp_status'] = $params['pp_status'];
        Loader::model('PayPlatform')->save($updateData, ['pp_id' => $params['pp_id']]);
        
        return true;
    }
}