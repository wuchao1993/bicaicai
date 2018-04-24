<?php
/**
 * 入款类型分组业务逻辑
 * @author jesse.lin.989@gmail.com
 */
namespace app\admin\logic;
class RechargeTypeGroup extends Common
{
    public $fieldMapConf = [
        'rtg_id'            => ['id'],
        'rtg_name'          => ['name'],
        'rtg_sort'          => ['sort'],
        'rtg_createtime'    => ['createtime'],
    ];

    public function setCondition(&$params){
        $condition = [ ];

        if (isset ( $params ['name'] )) {
            $condition ['rtg_name'] = ['LIKE','%'.$params['name'].'%'];
        }

        $this->condition = $condition;
    }

    public function setOrder()
    {
       $this->orderBy = 'rtg_id desc';
    }

    public function getAll(){
        $list = $this->select();
        return $list?collection($list)->toArray():[];
    }

}