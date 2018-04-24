<?php
namespace app\admin\model;

use think\Model;

class PayTypeConfig extends Model
{
    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pay_type_id'    => '主键',
        'category_id'    => '分类',
        'api_url'        => 'api地址',
    ];

    public function getCategoryIdsByChannelid($channelId)
    {
        $condition = ['pay_type_id' => $channelId];
        return array_values($this->where($condition)->column('category_id'));
    }

    public function saveConfig($configInfo){
        $info = $this->getInfo($configInfo['pay_type_id'], $configInfo['category_id']);
        if($info){
            $this->where(['pay_type_id'=>$configInfo['pay_type_id'], 'category_id' => $configInfo['category_id']])->update($configInfo);
        }else{
            $this->isUpdate(false)->save($configInfo);
        }
    }

    public function getInfo($typeId, $categoryId){
        $condition = [
            'pay_type_id' => $typeId,
            'category_id' => $categoryId
        ];

        return $this->where($condition)->find();
    }

}