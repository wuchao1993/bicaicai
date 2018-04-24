<?php

/**
 * 广告控制器
 */
namespace app\api\controller;

use think\Request;
use think\Loader;
use think\Config;


class Advertising {

    /**
     * 前台显示广告列表
     * @param Request $request
     * @return array
     */
    public function getList(Request $request) {
        $params['siteType'] = $request->post('siteType');
        $params['terminal'] = $request->post('terminal');
        $advertisingLogic   = Loader::model('Advertising', 'logic');
        $advertisingData    = $advertisingLogic->getList($params);
        return return_result($advertisingLogic->errorcode, output_format($advertisingData));
    }

}
