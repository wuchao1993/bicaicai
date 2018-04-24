<?php
namespace app\admin\logic;

use think\Loader;
use think\Model;

class PayCategory extends Model
{

    public $errorcode = EC_SUCCESS;

    /**
     * 获取列表
     * @param string $name
     * @param int $page
     * @param int $num
     * @param string $sort 值：desc - 降序 | asc - 升序
     * @return array
     */
    public function getList($name= '', $page=1, $num = 10, $sort = 'asc')
    {
        $limit = (($page<=0) ? 0 : ($page-1))*$num . "," .$num;
        $condition = [];
        if (isset($name)) {
            $condition['recharge_type_name'] = ['like', '%'.$name.'%'];
        }

        if($sort == 'desc'){
            $order_by = "recharge_type_sort desc";
        }else{
            $order_by = "recharge_type_sort asc";
        }

        $list  = Loader::model('RechargeType')->where($condition)->limit($limit)->order($order_by)->select();
        $count = Loader::model('RechargeType')->where($condition)->count();

        return [
            'list' => $list,
            'count' => $count
        ];
    }


    public function getInfo($id)
    {
        $condition['recharge_type_id'] = $id;
        return Loader::model('RechargeType')->where($condition)->find()->toArray();
    }


    public function editInfo($id, $info)
    {
        Loader::model('General', 'logic')->actionLog('update_recharge_type', 'editPayCategoryInfo', $id, MEMBER_ID, json_encode($info));
        return Loader::model('RechargeType')->save($info, ['recharge_type_id' => $id]);
    }

    /**
     * 修改状态
     * @param $params
     * @return bool
     */
    public function changeStatus($params)
    {
        $updateData['recharge_type_status'] = $params['recharge_type_status'];
        Loader::model('RechargeType')->save($updateData, ['recharge_type_id' => $params['recharge_type_id']]);
        
        return true;
    }
    
}