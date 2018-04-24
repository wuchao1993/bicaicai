<?php

/**
 * 资讯控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class Information {
    
    /**
     * 获取资讯列表
     * 
     * @param Request $request            
     * @return array
     */
    public function getInformationList(Request $request) 
    {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );
        
        if ($request->param ( 'typeId' ) != '') {
            $params ['information_type'] = $request->param ( 'typeId' );
        }
        
        $informationLogic = Loader::model ( 'Information', 'logic' );
        $informationList = $informationLogic->getList ( $params );
        
        foreach ( $informationList ['list'] as &$info ) {
            $info = $this->_packInformationList( $info );
        }
        
        return [ 
                'errorcode' => $informationLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$informationLogic->errorcode],
                'data' => output_format ( $informationList ) 
        ];
    }
    
    /**
     * 获取资讯信息
     * 
     * @param Request $request            
     * @return array
     */
    public function getInformationInfo(Request $request) 
    {
        $id = $request->param ( 'id' );
        
        $informationLogic = Loader::model ( 'information', 'logic' );
        $informationInfo = $informationLogic->getInfo ( $id );
        $informationInfo = $this->_packInformationInfo ( $informationInfo );
        
        return [ 
                'errorcode' => $informationLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$informationLogic->errorcode],
                'data' => output_format ( $informationInfo ) 
        ];
    }
    
    /**
     * 新增资讯
     * 
     * @param Request $request            
     * @return array
     */
    public function addInformation(Request $request) 
    {
        $params ['information_title'] = $request->param ( 'title' );
        $params ['information_type'] = $request->param ( 'type' );
        $params ['information_source'] = $request->param ( 'source', '');
        $params ['information_content'] = $request->param ( 'content', '' );
        $params ['information_createtime'] = $request->param ( 'createtime' );
        $params ['information_status'] = $request->param ( 'status' );
        $params ['information_sort'] = $request->param ( 'sort' );

        $informationLogic = Loader::model ( 'information', 'logic' );
        $informationInfo = $informationLogic->add ( $params );
        
        return [ 
                'errorcode' => $informationLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$informationLogic->errorcode],
                'data' => output_format ( $informationInfo ) 
        ];
    }
    
    /**
     * 编辑资讯
     * 
     * @param Request $request            
     * @return array
     */
    public function editInformation(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        $params ['information_title'] = $request->param ( 'title' );
        $params ['information_type'] = $request->param ( 'type' );
        $params ['information_source'] = $request->param ( 'source' );
        $params ['information_content'] = $request->param ( 'content' );
        $params ['information_createtime'] = $request->param ( 'createtime' );
        $params ['information_status'] = $request->param ( 'status' );
        $params ['information_sort'] = $request->param ( 'sort' );

        $informationLogic = Loader::model ( 'information', 'logic' );
        $result = $informationLogic->edit ( $params );
        
        return [ 
                'errorcode' => $informationLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$informationLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 删除资讯
     * 
     * @param Request $request            
     * @return array
     */
    public function delInformation(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        
        $informationLogic = Loader::model ( 'information', 'logic' );
        $result = $informationLogic->del ( $params );
        
        return [ 
                'errorcode' => $informationLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$informationLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 修改资讯状态
     * 
     * @param Request $request            
     * @return array
     */
    public function changeInformationStatus(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        $params ['status'] = $request->param ( 'status' );
        
        $informationLogic = Loader::model ( 'information', 'logic' );
        $result = $informationLogic->changeStatus ( $params );
        
        return [ 
                'errorcode' => $informationLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$informationLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 获取资讯类型
     * @param Request $request
     * @return array
     */
    public function getTypeList(Request $request)
    {
        $noteLogic = Loader::model('information', 'logic');
        
        $data = array();
        $i=0;
        foreach (Config::get('status.information_type_name') as $key=>$val) {
            $data[$i] = array('id' => $key, 'name' => $val);
            $i++;
        }
        
        return [
                'errorcode' => $noteLogic->errorcode,
                'message'   => Config::get('errorcode')[$noteLogic->errorcode],
                'data'      => output_format($data),
        ];
    }
    
    private function _packInformationList($info)
    {
        return [
                'id' => $info ['information_id'],
                'type' => $info ['information_type'],
                'title' => $info ['information_title'],
                'content' => mb_substr(strip_tags(htmlspecialchars_decode($info ['information_content'])),0,30).'...',
                'createtime' => $info ['information_createtime'],
                'status' => $info ['information_status'],
                'source' => $info ['information_source'],
                'sort' => $info ['information_sort'],
        ];
    }
    
    private function _packInformationInfo($info) 
    {
        return [ 
                'id' => $info ['information_id'],
                'type' => $info ['information_type'],
                'title' => $info ['information_title'],
                'content' => $info ['information_content'],
                'createtime' => $info ['information_createtime'],
                'status' => $info ['information_status'],
                'sort' => $info ['information_sort'],
                'source' => $info ['information_source'],
        ];
    }
}
