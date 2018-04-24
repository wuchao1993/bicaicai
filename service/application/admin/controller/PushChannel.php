<?php

/**
 * 推送渠道控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class PushChannel {
    
    /**
     * 获取推送渠道列表
     *
     * @param Request $request            
     * @return array
     */
    public function getPushChannelList(Request $request) {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );
        
        $pushChannelLogic = Loader::model ( 'PushChannel', 'logic' );
        $pushChannelList = $pushChannelLogic->getList ( $params );
        
        foreach ( $pushChannelList ['list'] as &$info ) {
            $info = $this->_packPushChannelInfo ( $info );
        }
        
        return [ 
                'errorcode' => $pushChannelLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$pushChannelLogic->errorcode],
                'data' => output_format ( $pushChannelList ) 
        ];
    }
    
    /**
     * 获取推送渠道信息
     *
     * @param Request $request            
     * @return array
     */
    public function getPushChannelInfo(Request $request) {
        $id = $request->param ( 'id' );
        
        $pushChannelLogic = Loader::model ( 'pushChannel', 'logic' );
        $pushChannelInfo = $pushChannelLogic->getInfo ( $id );
        $pushChannelInfo = $this->_packPushChannelInfo ( $pushChannelInfo );
        
        return [ 
                'errorcode' => $pushChannelLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$pushChannelLogic->errorcode],
                'data' => output_format ( $pushChannelInfo ) 
        ];
    }
    
    /**
     * 新增推送渠道
     *
     * @param Request $request            
     * @return array
     */
    public function addPushChannel(Request $request) {
        $params ['pc_app_name'] = $request->param ( 'appName' );
        $params ['pc_app_key'] = $request->param ( 'appKey' );
        $params ['pc_app_master_secret'] = $request->param ( 'appMasterSecret' );
        $params ['pc_platform'] = $request->param ( 'platform' );
        
        $pushChannelLogic = Loader::model ( 'pushChannel', 'logic' );
        $pushChannelInfo = $pushChannelLogic->add ( $params );
        
        return [ 
                'errorcode' => $pushChannelLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$pushChannelLogic->errorcode],
                'data' => output_format ( $pushChannelInfo ) 
        ];
    }
    
    /**
     * 编辑推送渠道
     *
     * @param Request $request            
     * @return array
     */
    public function editPushChannel(Request $request) {
        $params ['pc_id'] = $request->param ( 'id' );
        $params ['pc_app_name'] = $request->param ( 'appName' );
        $params ['pc_app_key'] = $request->param ( 'appKey' );
        $params ['pc_app_master_secret'] = $request->param ( 'appMasterSecret' );
        $params ['pc_platform'] = $request->param ( 'platform' );
        
        $pushChannelLogic = Loader::model ( 'pushChannel', 'logic' );
        $result = $pushChannelLogic->edit ( $params );
        
        return [ 
                'errorcode' => $pushChannelLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$pushChannelLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 删除推送渠道
     *
     * @param Request $request            
     * @return array
     */
    public function delPushChannel(Request $request) {
        $params ['pc_id'] = $request->param ( 'id' );
        
        $pushChannelLogic = Loader::model ( 'pushChannel', 'logic' );
        $result = $pushChannelLogic->del ( $params );
        
        return [ 
                'errorcode' => $pushChannelLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$pushChannelLogic->errorcode],
                'data' => $result 
        ];
    }
    private function _packPushChannelInfo($info) {
        return [ 
                'id' => $info ['pc_id'],
                'appName' => $info ['pc_app_name'],
                'appKey' => $info ['pc_app_key'],
                'appMasterSecret' => $info ['pc_app_master_secret'],
                'platform' => $info ['pc_platform'],
                'createtime' => $info ['pc_createtime'],
                'modifytime' => $info ['pc_modifytime'] 
        ];
    }
}
