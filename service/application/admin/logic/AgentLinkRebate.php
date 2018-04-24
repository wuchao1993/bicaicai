<?php
/**
 * 代理推广码返点业务模型
 */

namespace app\admin\logic;
use think\Controller;
use think\Loader;

class AgentLinkRebate extends Common
{

    public function getListByUserId($userId){

        $condition = [];
        $condition['user_id'] = is_array($userId)?array("IN",$userId):$userId;

        return $this->where($condition)->select();
    }

}