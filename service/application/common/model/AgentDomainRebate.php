<?php
namespace app\common\model;

use think\Model;

class AgentDomainRebate extends Model{

    public function getRebateByUserId($userId, $id){
        $condition = [
            'user_id' => $userId,
            'agd_id' => $id,
        ];

        return $this->where($condition)->column('rebate', 'category_id');
    }
}