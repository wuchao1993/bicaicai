<?php
namespace app\common\model;

use think\Config;
use think\Model;

class AgentDomain extends Model{

    public function getInfoByDomain($domain){
        $condition = [
            'agd_domain' => $domain,
            'agd_status' => Config::get('status.agent_domain_status')['enable'],
            'agd_use_count' => ['gt', 0]
        ];

        return $this->where($condition)->find();
    }


    public function decreaseUseCount($id){
        $condition = [
            'agd_id' => $id
        ];

        return $this->where($condition)->setDec('agd_use_count');
    }

}