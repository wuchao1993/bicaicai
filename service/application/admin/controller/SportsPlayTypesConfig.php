<?php
/**
 * 玩法管理配置
 * @createTime 2017/12/20 10:17
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;

class SportsPlayTypesConfig {

    /**
     * 玩法列表
     * @param Request $request
     * @return mixed
     */
    public function editSportsPlayTypesConfig(Request $request) {
        $sportId = $request->param('sportId');
        $ulId = $request->param('ulId');
        $playTypeConfigLogic = Loader::model('SportsPlayTypesConfig', 'logic');
        $playTypeConfigData = $playTypeConfigLogic->getSportsPlayTypesConfig($sportId, $ulId);

        return return_result($playTypeConfigLogic->errorcode, output_format($playTypeConfigData));
    }

    /***
     * @desc 批量修改玩法配置
     * @param Request $request
     * @return array
     */
    public function saveSportsPlayTypesConfig(Request $request){
        $configInfo = $request->param('configInfo/a');
        $ulId = $request->param('ulId');
        $playTypeConfigLogic = Loader::model('SportsPlayTypesConfig', 'logic');
        $playTypeConfigData = $playTypeConfigLogic->saveSportsPlayTypesConfig($ulId, $configInfo);

        return return_result($playTypeConfigLogic->errorcode, output_format($playTypeConfigData));
    }

}