<?php

/**
 * 活动控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class Activity {

    /**
     * 获取活动类目列表
     * @param Request $request
     * @return array
     */
    public function getActivityCategoryList(Request $request)
    {
        $params['page']  = $request->param('page',1);
        $params['num']   = $request->param('num',10);
        $params ['activity_category_name'] = $request->param('activityCategoryName');
        $activityCategory = Loader::model('Activity', 'logic');
        $categoryList     = $activityCategory->getCategoryList($params); 
        return [
                'errorcode' => $activityCategory->errorcode,
                'message' => Config::get ('errorcode') [$activityCategory->errorcode],
                'data' => output_format($categoryList) 
        ];     

    }

    /**
     * 添加活动类目
     * @param Request $request            
     * @return array
     */
    public function addActivityCategory(Request $request)
    {
        $params['activity_category_name'] = $request->param('activityCategoryName');
        $params['sort']  =  $request->param('sort');
        $activityCategory = Loader::model('Activity','logic');
        $categoryInfo = $activityCategory->addCategory($params);
        return [
                'errorcode' => $activityCategory->errorcode,
                'message' => Config::get ('errorcode') [$activityCategory->errorcode]
        ];  
    }

    /**
     * 修改活动类目
     * @param
     * @return 
     */
    public function editActivityCategory(Request $request)
    {
        $params['activity_category_id']   = $request->param('activityCategoryId');
        $params['activity_category_name'] = $request->param('activityCategoryName');
        $params['sort']  =  $request->param('sort');
        $activityCategory = Loader::model('Activity','logic');
        $categoryInfo = $activityCategory->editCategory($params);
        return [
                'errorcode' => $activityCategory->errorcode,
                'message' => Config::get ('errorcode') [$activityCategory->errorcode]
        ]; 
    }

    /**
     * 删除活动类目
     * @param
     * @return
     */
    public function deleteActivityCategory(Request $request)
    {
        $params['activity_category_id']   = $request->param('activityCategoryId');
        $activityCategory = Loader::model('Activity','logic');
        $categoryInfo = $activityCategory->deleteCategory($params);
        return [
                'errorcode' => $activityCategory->errorcode,
                'message' => Config::get ('errorcode') [$activityCategory->errorcode]
        ]; 
    }

    /**
     * 获取活动列表
     * 
     * @param Request $request            
     * @return array
     */
    public function getActivityList(Request $request) 
    {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );
        
        if ($request->param ( 'name' ) != '') {
            $params ['activity_name'] = $request->param ( 'name' );
        }
        $params['activity_category_id'] = $request->param('activityCategoryId');

        
        $activityLogic = Loader::model ( 'Activity', 'logic' );
        $activityList = $activityLogic->getList ( $params );
        
        foreach ( $activityList ['list'] as &$info ) {
            $info = $this->_packActivityList( $info );
        }
        
        return [ 
                'errorcode' => $activityLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$activityLogic->errorcode],
                'data' => output_format ( $activityList ) 
        ];
    }
    
    /**
     * 获取活动信息
     * 
     * @param Request $request            
     * @return array
     */
    public function getActivityInfo(Request $request) 
    {
        $id = $request->param ( 'id' );
        
        $activityLogic = Loader::model ( 'activity', 'logic' );
        $activityInfo = $activityLogic->getInfo ( $id );
        $activityInfo = $this->_packActivityInfo ( $activityInfo );
        
        return [ 
                'errorcode' => $activityLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$activityLogic->errorcode],
                'data' => output_format ( $activityInfo ) 
        ];
    }
    
    /**
     * 新增活动
     * 
     * @param Request $request            
     * @return array
     */
    public function addActivity(Request $request) 
    {
        $params ['activity_name'] = $request->param ( 'name' );
        $params ['activity_image'] = $request->param ( 'image' );
        $params ['activity_mobile_image'] = $request->param ( 'mobileImage' );
        $params ['activity_list_image'] = $request->param ( 'listImage' );
        $params ['activity_introduction'] = $request->param ( 'introduction' );
        $params ['activity_description'] = $request->param ( 'description' );
        $params ['activity_starttime'] = $request->param ( 'starttime' );
        $params ['activity_finishtime'] = $request->param ( 'finishtime' );
        $params ['activity_is_banner'] = $request->param ( 'isBanner' );
        $params ['activity_sort'] = $request->param ( 'sort' );
        $params ['activity_status'] = $request->param ( 'status' );
        $params ['activity_category_id'] = $request->param('activityCategoryId');
        $params ['activity_lottery_type'] = $request->param('lotteryType', Config::get('status.site_config_lottery_type')['digital']);

        $activityLogic = Loader::model ( 'activity', 'logic' );
        $activityInfo = $activityLogic->add ( $params );
        
        return [ 
                'errorcode' => $activityLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$activityLogic->errorcode],
                'data' => output_format ( $activityInfo ) 
        ];
    }
    
    /**
     * 编辑活动
     * 
     * @param Request $request            
     * @return array
     */
    public function editActivity(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        $params ['activity_name'] = $request->param ( 'name' );
        $params ['activity_image'] = $request->param ( 'image' );
        $params ['activity_mobile_image'] = $request->param ( 'mobileImage' );
        $params ['activity_list_image'] = $request->param ( 'listImage' );
        $params ['activity_introduction'] = $request->param ( 'introduction' );
        $params ['activity_description'] = $request->param ( 'description' );
        $params ['activity_starttime'] = $request->param ( 'starttime' );
        $params ['activity_finishtime'] = $request->param ( 'finishtime' );
        $params ['activity_is_banner'] = $request->param ( 'isBanner' );
        $params ['activity_sort'] = $request->param ( 'sort' );
        $params ['activity_status'] = $request->param ( 'status' );
        $params ['activity_category_id'] = $request->param('activityCategoryId');
        $params ['activity_lottery_type'] = $request->param('lotteryType',Config::get('status.site_config_lottery_type')['digital']);

        $activityLogic = Loader::model ( 'activity', 'logic' );
        $result = $activityLogic->edit ( $params );
        
        return [ 
                'errorcode' => $activityLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$activityLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 删除活动
     * 
     * @param Request $request            
     * @return array
     */
    public function delActivity(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        
        $activityLogic = Loader::model ( 'activity', 'logic' );
        $result = $activityLogic->del ( $params );
        
        return [ 
                'errorcode' => $activityLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$activityLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 修改活动状态
     * 
     * @param Request $request            
     * @return array
     */
    public function changeActivityStatus(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        $params ['status'] = $request->param ( 'status' );
        
        $activityLogic = Loader::model ( 'activity', 'logic' );
        $result = $activityLogic->changeStatus ( $params );
        
        return [ 
                'errorcode' => $activityLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$activityLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 获取活动类型
     * @param Request $request
     * @return array
     */
    public function getTypeList(Request $request)
    {
        $noteLogic = Loader::model('activity', 'logic');
        
        $data = array();
        $i=0;
        foreach (Config::get('status.activity_type_name') as $key=>$val) {
            $data[$i] = array('id' => $key, 'name' => $val);
            $i++;
        }
        
        return [
                'errorcode' => $noteLogic->errorcode,
                'message'   => Config::get('errorcode')[$noteLogic->errorcode],
                'data'      => output_format($data),
        ];
    }
    
    private function _packActivityList($info) {
        
        return [
                'id'            => $info ['activity_id'],
                'name'          => $info ['activity_name'],
                'introduction'  => mb_substr(strip_tags(htmlspecialchars_decode($info ['activity_introduction'])), 0, 30).'...',
                'description'   => mb_substr(strip_tags(htmlspecialchars_decode($info ['activity_description'])), 0, 30).'...',
                'image'         => $info ['activity_image'],
                'mobileImage'   => $info ['activity_mobile_image'],
                'listImage'     => $info ['activity_list_image'],
                'starttime'     => $info ['activity_starttime'],
                'finishtime'    => $info ['activity_finishtime'],
                'isBanner'      => $info ['activity_is_banner'],
                'sort'          => $info ['activity_sort'],
                'status'        => $info ['activity_status'],
                'activityCategoryName'  => $info ['activity_category_name'],
                'lotteryType'   => $info['activity_lottery_type'],
        ];
    }
    
    private function _packActivityInfo($info) {
        return [ 
                'id'                 => $info ['activity_id'],
                'name'               => $info ['activity_name'],
                'introduction'       => $info ['activity_introduction'],
                'description'        => $info ['activity_description'],
                'image'              => $info ['activity_image'],
                'mobileImage'        => $info ['activity_mobile_image'],
                'listImage'          => $info ['activity_list_image'],
                'starttime'          => $info ['activity_starttime'],
                'finishtime'         => $info ['activity_finishtime'],
                'isBanner'           => $info ['activity_is_banner'],
                'sort'               => $info ['activity_sort'],
                'status'             => $info ['activity_status'],
                'activityCategoryId' => $info ['activity_category_id'],
                'lotteryType'        => $info ['activity_lottery_type'],

        ];
    }
}
