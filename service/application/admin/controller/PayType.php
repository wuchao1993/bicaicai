<?php

namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class PayType
{

    /**
     * @param Request $request
     * @return array
     * 获取支付平台列表
     */
    public function getPayTypeList(Request $request)
    {
        $page = $request->param('page');
        $num = $request->param('num');
        $type = $request->param('type');
        $typeName = $request->param('typeName');
        $payTypeLogic = Loader::model('PayType', 'logic');
        $data = $payTypeLogic->getList($page, $num, $type, $typeName);
        $responseList = [];
        foreach ($data['list'] as $key => $info) {
            $responseList[$key] = $this->_packPlayTypeList($info);
        }

        return [
            'errorcode' => $payTypeLogic->errorcode,
            'message' => Config::get('errorcode')[$payTypeLogic->errorcode],
            'data' => [
                'list' => $responseList,
                'totalCount' => $data['count']
            ],
        ];
    }

    /**
     * @param Request $request
     * @return array
     * 获取支付平台详情
     */
    public function getPayTypeInfo(Request $request)
    {
        $id = $request->param('id');
        $payTypeLogic = Loader::model('PayType', 'logic');
        $data = $payTypeLogic->getInfo($id);
        $data = $this->_packPlayTypeInfo($data);

        return [
            'errorcode' => $payTypeLogic->errorcode,
            'message' => Config::get('errorcode')[$payTypeLogic->errorcode],
            'data' => output_format($data),
        ];
    }
    /**
     * @param Request $request
     * @return array
     * 获取支付平台对应的支付类型
     */
    public function getPayTypeConfig(Request $request)
    {
        $id   = $request->param('id');
        $type = $request->param('type');
        $payTypeLogic = Loader::model('PayType', 'logic');
        $data = $payTypeLogic->getConfig($id,$type);
        return [
            'errorcode' => $payTypeLogic->errorcode,
            'message' => Config::get('errorcode')[$payTypeLogic->errorcode],
            'data' => output_format($data),
        ];
    }

    /**
     * 获取注单状态
     * @param Request $request
     * @return array
     */
    public function getCategoryList(Request $request)
    {
        $payTypeLogic = Loader::model('PayType', 'logic');
        
        $data = array();
        $i=0;
        foreach (Config::get('status.pay_category_type_name') as $key=>$val) {
            $data[$i] = array('category_id' => $key, 'category_name' => $val);
            $i++;
        }
        
        return [
                'errorcode' => $payTypeLogic->errorcode,
                'message'   => Config::get('errorcode')[$payTypeLogic->errorcode],
                'data'      => output_format($data),
        ];
    }
    
    private function _packPlayTypeList($info)
    {
        return [
            'id' => $info['pay_type_id'],
            'typeName' => $info['pay_type_name'],
            'className' => $info['pay_class_name'],
            'configDisplay' => json_decode($info['pay_config_display'], true),
            'status' => $info['pay_type_status'],
        ];
    }

    private function _packPlayTypeInfo($info)
    {
        return [
                'id' => $info['pay_type_id'],
                'typeName' => $info['pay_type_name'],
                'className' => $info['pay_class_name'],
                'configDisplay' => json_decode($info['pay_config_display'], true),
                'status' => $info['pay_type_status'],
                'gotopay' => $info['pay_gotopay_status'],
                'configList' => $info['configList'],
        ];
    }
    
    /**
     * @param Request $request
     * @return array
     * 新增渠道信息
     */
    public function addPayTypeInfo(Request $request)
    {
        $payTypeInfo = [
                'pay_type_name' => $request->param('typeName'),
                'pay_class_name' => $request->param('className'),
                'pay_config_display' => $request->param('configDisplay/a'),
                'pay_type_status' => $request->param('status'),
                'pay_gotopay_status' => $request->param('gotopay'),
                'configList' => $request->param('configList/a'),
        ];
        
        $payTypeLogic = Loader::model('PayType', 'logic');
        
        $result = $payTypeLogic->addInfo($payTypeInfo);
        return [
                'errorcode' => $payTypeLogic->errorcode,
                'message' => Config::get('errorcode')[$payTypeLogic->errorcode]
        ];
    }
    
    /**
     * @param Request $request
     * @return array
     * 编辑渠道信息
     */
    public function editPayTypeInfo(Request $request)
    {
        $payTypeInfo = [
            'pay_type_id'   => $request->param('id'),
            'pay_type_name' => $request->param('typeName'),
            'pay_class_name' => $request->param('className'),
            'pay_config_display' => $request->param('configDisplay/a'),
            'pay_type_status' => $request->param('status'),
            'pay_gotopay_status' => $request->param('gotopay'),
            'configList' => $request->param('configList/a'),
        ];

        $payTypeLogic = Loader::model('PayType', 'logic');

        $result = $payTypeLogic->editInfo($payTypeInfo);
        return [
            'errorcode' => $payTypeLogic->errorcode,
            'message' => Config::get('errorcode')[$payTypeLogic->errorcode]
        ];
    }

    /**
     * 获取弹窗广告类型
     * @param Request $request
     * @return array
     */
    public function getConfigDisplayList(Request $request)
    {
        $noteLogic = Loader::model('advert', 'logic');

        $data = array();
        $i=0;
        foreach (Config::get('status.pay_config_display_name') as $key=>$val) {
            $data[$i] = array('id' => $key, 'name' => $val);
            $i++;
        }

        return [
            'errorcode' => $noteLogic->errorcode,
            'message'   => Config::get('errorcode')[$noteLogic->errorcode],
            'data'      => output_format($data),
        ];
    }
}