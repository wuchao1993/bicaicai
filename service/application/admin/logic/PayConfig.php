<?php

namespace app\admin\logic;

class PayConfig extends \app\common\logic\PayConfig
{

    public function getUlConfig($ulIds){

        if(empty($ulIds)) return false;

        $condition = [];
        $condition['ul_id'] = ['IN',$ulIds];

        return $this->where($condition)->select();
    }

    public function getUlCompanyLargeAmount($ulIds){

        if(empty($ulIds)) return false;

        $condition = [];
        $condition['ul_id'] = ['IN',$ulIds];

        return $this->where($condition)->column('pc_company_everyday_large_amount','ul_id');
    }

}