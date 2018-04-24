<?php

/**
 * 弹窗广告控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class Advert {
    
    /**
     * 获取弹窗广告列表
     * 
     * @param Request $request            
     * @return array
     */
    public function getAdvertList(Request $request) 
    {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );

        $advertLogic = Loader::model ( 'Advert', 'logic' );
        $advertList = $advertLogic->getList ( $params );
        
        foreach ( $advertList ['list'] as &$info ) {
            $info = $this->_packAdvertList( $info );
        }
        
        return [ 
                'errorcode' => $advertLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$advertLogic->errorcode],
                'data' => output_format ( $advertList ) 
        ];
    }

    /**
     * 获取弹窗广告信息
     * 
     * @param Request $request            
     * @return array
     */
    public function getAdvertInfo(Request $request) 
    {
        $id = $request->param ( 'id' );
        
        $advertLogic = Loader::model ( 'advert', 'logic' );
        $advertInfo = $advertLogic->getInfo ( $id );
        $advertInfo = $this->_packAdvertList ( $advertInfo );
        
        return [ 
                'errorcode' => $advertLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$advertLogic->errorcode],
                'data' => output_format ( $advertInfo ) 
        ];
    }

    /**
     * 新增弹窗广告
     * 
     * @param Request $request            
     * @return array
     */
    public function addAdvert(Request $request) 
    {
        $params ['advert_name'] = $request->param ( 'name' );
        $params ['advert_image'] = $request->param ( 'image' );
        $params ['advert_url'] = $request->param ( 'url' );
        $params ['advert_type'] = $request->param ( 'type' );
        $params ['advert_pos'] = $request->param ( 'pos' );
        $params ['advert_format'] = $request->param ( 'format' ,' ');
        $params ['advert_text_app'] = $request->param ( 'app' ,' ');
        $params ['advert_text_pc'] = $request->param ( 'pc' ,' ');
        $params ['advert_status'] = $request->param ( 'status' );
        
        $advertLogic = Loader::model ( 'advert', 'logic' );
        $advertInfo = $advertLogic->add ( $params );
        
        return [ 
                'errorcode' => $advertLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$advertLogic->errorcode],
                'data' => output_format ( $advertInfo ) 
        ];
    }

    /**
     * 编辑弹窗广告
     * 
     * @param Request $request            
     * @return array
     */
    public function editAdvert(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        $params ['advert_name'] = $request->param ( 'name' );
        $params ['advert_image'] = $request->param ( 'image' );
        $params ['advert_url'] = $request->param ( 'url' );
        $params ['advert_type'] = $request->param ( 'type' );
        $params ['advert_pos'] = $request->param ( 'pos' );
        $params ['advert_format'] = $request->param ( 'format' ,' ');
        $params ['advert_text_app'] = $request->param ( 'app' ,' ');
        $params ['advert_text_pc'] = $request->param ( 'pc' ,' ');
        $params ['advert_status'] = $request->param ( 'status' );
        
        $advertLogic = Loader::model ( 'advert', 'logic' );
        $result = $advertLogic->edit ( $params );
        
        return [ 
                'errorcode' => $advertLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$advertLogic->errorcode],
                'data' => $result 
        ];
    }

    /**
     * 删除弹窗广告
     * 
     * @param Request $request            
     * @return array
     */
    public function delAdvert(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        
        $advertLogic = Loader::model ( 'advert', 'logic' );
        $result = $advertLogic->del ( $params );
        
        return [ 
                'errorcode' => $advertLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$advertLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 修改弹窗广告状态
     * 
     * @param Request $request            
     * @return array
     */
    public function changeAdvertStatus(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        $params ['status'] = $request->param ( 'status' );
        
        $advertLogic = Loader::model ( 'advert', 'logic' );
        $result = $advertLogic->changeStatus ( $params );
        
        return [ 
                'errorcode' => $advertLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$advertLogic->errorcode],
                'data' => $result 
        ];
    }

    /**
     * 获取弹窗广告类型
     * @param Request $request
     * @return array
     */
    public function getTypeList(Request $request)
    {
        $data = array();
        foreach (Config::get('status.advert_type_name') as $key=>$val) {
            $data[] = array('id' => $key, 'name' => $val);
        }
        return [
                'errorcode' => EC_AD_SUCCESS,
                'message'   => Config::get('errorcode')[EC_AD_SUCCESS],
                'data'      => output_format($data),
        ];
    }

    /**
     * 获取弹窗广告位置
     * @param Request $request
     * @return array
     */
    public function getPosList(Request $request)
    {

        $data = array();
        $i=0;
        foreach (Config::get('status.advert_pos_name') as $key=>$val) {
            $data[$i] = array('id' => $key, 'name' => $val);
            $i++;
        }

        return [
            'errorcode' => EC_AD_SUCCESS,
            'message'   => Config::get('errorcode')[EC_AD_SUCCESS],
            'data'      => output_format($data),
        ];
    }


    /**
     * 获取弹窗广告格式
     * @param Request $request
     * @return array
     */
    public function getFormatList(Request $request)
    {

        $data = array();
        $i=0;
        foreach (Config::get('status.advert_format') as $key=>$val) {
            $data[$i] = array('id' => $key, 'name' => $val);
            $i++;
        }

        return [
            'errorcode' => EC_AD_SUCCESS,
            'message'   => Config::get('errorcode')[EC_AD_SUCCESS],
            'data'      => output_format($data),
        ];
    }
    
    private function _packAdvertList($info) {
        
        return [
                'id'            => $info ['advert_id'],
                'name'          => $info ['advert_name'],
                'image'         => $info ['advert_image'],
                'url'           => $info ['advert_url'],
                'type'          => $info ['advert_type'],
                'pos'           => $info ['advert_pos'],
                'format'        => $info ['advert_format'],
                'app'           => $info ['advert_text_app'],
                'pc'            => $info ['advert_text_pc'],
                'createtime'    => $info ['advert_createtime'],
                'status'        => $info ['advert_status'],
        ];
    }

}
