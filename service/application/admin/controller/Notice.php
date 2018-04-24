<?php

/**
 * 公告控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class Notice {
    
    /**
     * 获取公告列表
     * 
     * @param Request $request            
     * @return array
     */
    public function getNoticeList(Request $request) 
    {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );
        
        if ($request->param ( 'typeId' ) != '') {
            $params ['notice_type'] = $request->param ( 'typeId' );
        }
        
        $noticeLogic = Loader::model ( 'Notice', 'logic' );
        $noticeList = $noticeLogic->getList ( $params );
        
        foreach ( $noticeList ['list'] as &$info ) {
            $info = $this->_packNoticeList( $info );
        }
        
        return [ 
                'errorcode' => $noticeLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$noticeLogic->errorcode],
                'data' => output_format ( $noticeList ) 
        ];
    }
    
    /**
     * 获取公告信息
     * 
     * @param Request $request            
     * @return array
     */
    public function getNoticeInfo(Request $request) 
    {
        $id = $request->param ( 'id' );
        
        $noticeLogic = Loader::model ( 'notice', 'logic' );
        $noticeInfo = $noticeLogic->getInfo ( $id );
        $noticeInfo = $this->_packNoticeInfo ( $noticeInfo );
        
        return [ 
                'errorcode' => $noticeLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$noticeLogic->errorcode],
                'data' => output_format ( $noticeInfo ) 
        ];
    }
    
    /**
     * 新增公告
     * 
     * @param Request $request            
     * @return array
     */
    public function addNotice(Request $request) 
    {
        $params ['notice_title'] = $request->param ( 'title' );
        $params ['notice_type'] = $request->param ( 'type' );
        $params ['notice_lottery_type'] = $request->param ( 'lotteryType',1 );
        $params ['notice_introduction'] = $request->param ( 'introduction' );
        $params ['notice_content'] = $request->param ( 'content' );
        $params ['notice_createtime'] = $request->param ( 'createtime' );
        $params ['notice_status'] = $request->param ( 'status' );
        $params ['notice_sort'] = $request->param ( 'sort' );
        $params ['notice_marquee'] = $request->param('marquee');

        $noticeLogic = Loader::model ( 'notice', 'logic' );
        $noticeInfo = $noticeLogic->add ( $params );
        
        return [ 
                'errorcode' => $noticeLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$noticeLogic->errorcode],
                'data' => output_format ( $noticeInfo ) 
        ];
    }
    
    /**
     * 编辑公告
     * 
     * @param Request $request            
     * @return array
     */
    public function editNotice(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        $params ['notice_title'] = $request->param ( 'title' );
        $params ['notice_type'] = $request->param ( 'type' );
        $params ['notice_lottery_type'] = $request->param ( 'lotteryType',1 );
        $params ['notice_introduction'] = $request->param ( 'introduction' );
        $params ['notice_content'] = $request->param ( 'content' );
        $params ['notice_createtime'] = $request->param ( 'createtime' );
        $params ['notice_status'] = $request->param ( 'status' );
        $params ['notice_sort'] = $request->param ( 'sort' );
        $params ['notice_marquee'] = $request->param('marquee');

        $noticeLogic = Loader::model ( 'notice', 'logic' );
        $result = $noticeLogic->edit ( $params );
        
        return [ 
                'errorcode' => $noticeLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$noticeLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 删除公告
     * 
     * @param Request $request            
     * @return array
     */
    public function delNotice(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        
        $noticeLogic = Loader::model ( 'notice', 'logic' );
        $result = $noticeLogic->del ( $params );
        
        return [ 
                'errorcode' => $noticeLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$noticeLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 修改公告状态
     * 
     * @param Request $request            
     * @return array
     */
    public function changeNoticeStatus(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        $params ['status'] = $request->param ( 'status' );
        
        $noticeLogic = Loader::model ( 'notice', 'logic' );
        $result = $noticeLogic->changeStatus ( $params );
        
        return [ 
                'errorcode' => $noticeLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$noticeLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 获取公告类型
     * @param Request $request
     * @return array
     */
    public function getTypeList(Request $request)
    {
        $noteLogic = Loader::model('notice', 'logic');
        
        $data = array();
        $i=0;
        foreach (Config::get('status.notice_type_name') as $key=>$val) {
            $data[$i] = array('id' => $key, 'name' => $val);
            $i++;
        }
        
        return [
                'errorcode' => $noteLogic->errorcode,
                'message'   => Config::get('errorcode')[$noteLogic->errorcode],
                'data'      => output_format($data),
        ];
    }
    
    private function _packNoticeList($info) {
        return [
                'id' => $info ['notice_id'],
                'type' => $info ['notice_type'],
                'lotteryType' => $info ['notice_lottery_type'],
                'title' => $info ['notice_title'],
                'introduction' => mb_substr(strip_tags(htmlspecialchars_decode($info ['notice_introduction'])), 0, 30).'...',
                'content' => mb_substr(strip_tags(htmlspecialchars_decode($info ['notice_content'])), 0, 30).'...',
                'createtime' => $info ['notice_createtime'],
                'status' => $info ['notice_status'],
                'sort' => $info['notice_sort'],
                'marquee' => $info['notice_marquee'],
        ];
    }
    
    private function _packNoticeInfo($info) {
        return [ 
                'id' => $info ['notice_id'],
                'type' => $info ['notice_type'],
                'lotteryType' => $info ['notice_lottery_type'],
                'title' => $info ['notice_title'],
                'introduction' => $info ['notice_introduction'],
                'content' => $info ['notice_content'],
                'createtime' => $info ['notice_createtime'],
                'status' => $info ['notice_status'],
                'sort' => $info['notice_sort'],
                'marquee' => $info['notice_marquee'],
        ];
    }
}
