<?php

/**
 * 广告控制器
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;

class Advertising {


    public function getList(Request $request) {
        $params['status']    = $request->post('status');
        $params['siteType']  = $request->post('siteType');
        $params['terminal']  = $request->post('terminal');
        $params['name']      = $request->post('name');
        $params['page']      = $request->post('page',0);
        $params['num']       = $request->post('num',20);
        $advertisingLogic    = Loader::model('advertising', 'logic');
        $advertisingData     = $advertisingLogic->getList($params);
        return return_result($advertisingLogic->errorcode, output_format($advertisingData));
    }

    public function addAdvertising(Request $request) {
        $params['name']        = $request->post('name');
        $params['sketchImage'] = $request->post('sketchImage');
        $params['size']        = $request->post('size');
        $params['remark']      = $request->post('remark');
        $params['webImage']    = $request->post('webImage','');
        $params['link']        = $request->post('link','');
        $params['status']      = $request->post('status');
        $params['siteType']    = $request->post('siteType');
        $params['terminal']    = $request->post('terminal');
        $params['identifier']  = $request->post('identifier');
        $advertisingLogic = Loader::model('Advertising' , 'logic');
        $advertisingData  = $advertisingLogic->addAdvertising($params);
        return return_result($advertisingLogic->errorcode, output_format($advertisingData));
    }

    /**
     * 编辑广告示意图信息
     * @param Request $request
     * @return array
     */
    public function editAdvertising(Request $request) {
        $params['name']        = $request->post('name');
        $params['sketchImage'] = $request->post('sketchImage');
        $params['size']        = $request->post('size');
        $params['remark']      = $request->post('remark');
        $params['webImage']    = $request->post('webImage');
        $params['link']        = $request->post('link');
        $params['status']      = $request->post('status');
        $params['siteType']    = $request->post('siteType');
        $params['terminal']    = $request->post('terminal');
        $params['id']          = $request->post('id');
        $params['identifier']  = $request->post('identifier');

        $advertisingLogic = Loader::model('Advertising','logic');
        $advertisingData  = $advertisingLogic->editAdvertising($params);
        return return_result($advertisingLogic->errorcode, output_format($advertisingData));
    }

    /**
     * 获取广告详情
     * @param Request $request
     * @return array
     */
    public function getAdvertisingInfo(Request $request) {
        $id               = $request->post('id');
        $advertisingLogic = Loader::model('Advertising', 'logic');
        $advertisingData  = $advertisingLogic->getAdvertisingInfo($id);
        return return_result($advertisingLogic->errorcode, output_format($advertisingData));
    }

    /**
     * 删除功能
     * @param Request $request
     * @return array
     */
    public function deleteAdvertising(Request $request) {
        $id               = $request->post('id');
        $advertisingLogic = Loader::model('advertising', 'logic');
        $advertisingData  = $advertisingLogic->deleteAdvertising($id);
        return return_result($advertisingLogic->errorcode, $advertisingData);
    }
}
