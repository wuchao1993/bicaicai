<?php
/**
 * 代理域名返点控制器
 * User: jesse
 * Date: 2017/8/7
 * Time: 16:41
 */

namespace app\admin\controller;


class AgentDomainRebate extends Common
{
    public function getRebate()
    {
        $info = $this->logic->getRebate ($this->param['id'],$this->param['uid']);

        return $this->_response($this->logic->errorCode, $info);
    }


}