<?php

namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class SportsAgentRebateConfig
{

    /***
     * @desc 添加体彩返水配置信息
     * @param Request $request
     * @return array
     */
    public function addRebateConfig(Request $request)
    {

        $sportsAgentRebateLogic = Loader::model("SportsAgentRebate", "logic");
        $sportsAgentRebateLogic->addSportsAgentRebate($request->post());

        return send_response('', $sportsAgentRebateLogic->errorcode);

    }

    /***
     * @desc 编辑体彩返水配置信息
     * @param Request $request
     * @return array
     */
    public function editRebateConfig(Request $request)
    {

        $sportsAgentRebateLogic = Loader::model("SportsAgentRebate", "logic");
        $sportsAgentRebateLogic->editSportsAgentRebate($request->post());

        return send_response('', $sportsAgentRebateLogic->errorcode);

    }

    /***
     * @desc 删除体彩返水配置信息
     * @param Request $request
     * @return array
     */
    public function deleteRebateConfig(Request $request)
    {

        $sportsAgentRebateLogic = Loader::model("SportsAgentRebate", "logic");
        $sportsAgentRebateLogic->deleteSportsAgentRebate($request->post());
        return send_response('', $sportsAgentRebateLogic->errorcode);

    }

    /***
     * @desc 获取体彩返水配置信息
     * @param Request $request
     * @return array
     */
    public function getRebateConfigList(Request $request)
    {
        $count = Config::get ("qrcode.limit_num")['count'];
        $limitStartNumber = Config::get ("qrcode.limit_num")['page'];
        $sportsAgentRebateLogic = Loader::model("SportsAgentRebate", "logic");
        $param['limitStartNumber'] = $request->param("page", $limitStartNumber);
        $param['num'] = $request->param("num",$count);
        $rebateResult = $sportsAgentRebateLogic->getSportsAgentRebateList($param);
        return send_response($rebateResult, $sportsAgentRebateLogic->errorcode);

    }
}