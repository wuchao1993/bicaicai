<?php

namespace app\admin\controller;

use think\Loader;
use think\Request;
use think\Config;

class PayChannel
{

    /**
     * @param Request $request
     * @return array
     * 获取支付渠道列表
     */
    public function getPayChannelList(Request $request)
    {
        $page = $request->param('page');
        $num  = $request->param('num');
        $type = $request->param('type');
        $payChannelLogic = Loader::model('PayChannel', 'logic');

        $data =  $payChannelLogic->getList($page, $num,$type);
        $responseList = [];
        foreach ($data['list'] as $key => $info){
            $responseList[$key] = $this->_packPayChannelInfo($info->toArray());
        }
        return [
            'errorcode' => $payChannelLogic->errorcode,
            'message' => Config::get('errorcode')[$payChannelLogic->errorcode],
            'data' => [
                'list' => $responseList,
                'totalCount' => $data['count']
            ],
        ];
    }



    public function getPayChannelInfo(Request $request){
        $channelId = $request->post('id');
        $payChannelLogic = Loader::model('PayChannel', 'logic');
        $data =  $payChannelLogic->getInfo($channelId);

        return [
            'errorcode' => $payChannelLogic->errorcode,
            'message' => Config::get('errorcode')[$payChannelLogic->errorcode],
            'data' => $this->_packPayChannelInfo($data),
        ];
    }



    private function _packPayChannelInfo($info){
        return [
            'id' => $info['pay_type_id'],
            'name' => $info['pay_type_name'],
            'className' => $info['pay_class_name'],
            'status' => $info['pay_type_status'],
        ];
    }


    /**
     * @param Request $request
     * 编辑支付渠道
     */
    public function editPayChannel(Request $request){
        $id = $request->param('id');
        $payChannelInfo = [
            'pay_type_name' => $request->param('name'),
            'pay_class_name' => $request->param('className'),
            'pay_config_display' => json_encode($request->param('configDisplay')),
            'pay_type_status' => $request->param('status'),
            'pay_gotopay_status' => $request->param('gotopay'),
        ];
        $configs = $request->param('configs');
        $configs = json_decode($configs, true);

        $payChannelLogic = Loader::model('PayChannel', 'logic');
        if($id){
            $payChannelLogic->editInfo($id, $payChannelInfo, $configs);
        }else{
            $payChannelLogic->add($payChannelInfo, $configs);
        }

    }




}