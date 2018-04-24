<?php

/**
 * 帮助控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class Help {
    
    /**
     * 获取帮助列表
     * 
     * @param Request $request            
     * @return array
     */
    public function getHelpList(Request $request) 
    {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );
        
        if ($request->param ( 'typeId' ) != '') {
            $params ['help_type'] = $request->param ( 'typeId' );
        }
        
        $helpLogic = Loader::model ( 'Help', 'logic' );
        $helpList = $helpLogic->getList ( $params );
        
        foreach ( $helpList ['list'] as &$info ) {
            $info = $this->_packHelpList( $info );
        }
        
        return [ 
                'errorcode' => $helpLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$helpLogic->errorcode],
                'data' => output_format ( $helpList ) 
        ];
    }
    
    /**
     * 获取帮助信息
     * 
     * @param Request $request            
     * @return array
     */
    public function getHelpInfo(Request $request) 
    {
        $id = $request->param ( 'id' );
        
        $helpLogic = Loader::model ( 'help', 'logic' );
        $helpInfo = $helpLogic->getInfo ( $id );
        $helpInfo = $this->_packHelpInfo ( $helpInfo );
        
        return [ 
                'errorcode' => $helpLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$helpLogic->errorcode],
                'data' => output_format ( $helpInfo ) 
        ];
    }
    
    /**
     * 新增帮助
     * 
     * @param Request $request            
     * @return array
     */
    public function addHelp(Request $request) 
    {
        $params ['help_title'] = $request->param ( 'title' );
        $params ['help_type'] = $request->param ( 'type' );
        $params ['help_content'] = $request->param ( 'content' );
        $params ['help_createtime'] = $request->param ( 'createtime' );
        $params ['help_status'] = $request->param ( 'status' );
        
        $helpLogic = Loader::model ( 'help', 'logic' );
        $helpInfo = $helpLogic->add ( $params );
        
        return [ 
                'errorcode' => $helpLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$helpLogic->errorcode],
                'data' => output_format ( $helpInfo ) 
        ];
    }
    
    /**
     * 编辑帮助
     * 
     * @param Request $request            
     * @return array
     */
    public function editHelp(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        $params ['help_title'] = $request->param ( 'title' );
        $params ['help_type'] = $request->param ( 'type' );
        $params ['help_content'] = $request->param ( 'content' );
        $params ['help_createtime'] = $request->param ( 'createtime' );
        $params ['help_status'] = $request->param ( 'status' );
        
        $helpLogic = Loader::model ( 'help', 'logic' );
        $result = $helpLogic->edit ( $params );
        
        return [ 
                'errorcode' => $helpLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$helpLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 删除帮助
     * 
     * @param Request $request            
     * @return array
     */
    public function delHelp(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        
        $helpLogic = Loader::model ( 'help', 'logic' );
        $result = $helpLogic->del ( $params );
        
        return [ 
                'errorcode' => $helpLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$helpLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 修改帮助状态
     * 
     * @param Request $request            
     * @return array
     */
    public function changeHelpStatus(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        $params ['status'] = $request->param ( 'status' );
        
        $helpLogic = Loader::model ( 'help', 'logic' );
        $result = $helpLogic->changeStatus ( $params );
        
        return [ 
                'errorcode' => $helpLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$helpLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 获取帮助类型
     * @param Request $request
     * @return array
     */
    public function getTypeList(Request $request)
    {
        $noteLogic = Loader::model('help', 'logic');
        
        $data = array();
        $i=0;
        foreach (Config::get('status.help_type_name') as $key=>$val) {
            $data[$i] = array('id' => $key, 'name' => $val);
            $i++;
        }
        
        return [
                'errorcode' => $noteLogic->errorcode,
                'message'   => Config::get('errorcode')[$noteLogic->errorcode],
                'data'      => output_format($data),
        ];
    }
    
    private function _packHelpList($info)
    {
        return [
                'id' => $info ['help_id'],
                'type' => $info ['help_type'],
                'title' => $info ['help_title'],
                'content' => mb_substr(strip_tags(htmlspecialchars_decode($info ['help_content'])),0,30).'...',
                'createtime' => $info ['help_createtime'],
                'status' => $info ['help_status'],
        ];
    }
    
    private function _packHelpInfo($info) 
    {
        return [ 
                'id' => $info ['help_id'],
                'type' => $info ['help_type'],
                'title' => $info ['help_title'],
                'content' => $info ['help_content'],
                'createtime' => $info ['help_createtime'],
                'status' => $info ['help_status'],
        ];
    }
}
