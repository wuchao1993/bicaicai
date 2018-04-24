<?php

/**
 * 活动相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;
class Activity extends Model {
    
    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取公告列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $activityModel = Loader::model ( 'Activity' );
        
        $condition = [ ];
        if (isset ( $params ['activity_name'] )) {
            $condition ['at.activity_name'] = ['LIKE','%'.$params['activity_name'].'%'];
        }
        $condition ['at.activity_status'] = ['NEQ',Config::get('status.activity_status')['deleted']];
        if (isset($params['activity_category_id'])) {
            $condition['at.activity_category_id'] = $params['activity_category_id'];
        }
        // 获取总条数
        $count = $activityModel->alias('at')->where ( $condition )->count ();
        
        $list  = $activityModel->alias('at')
            ->join('ActivityCategory ac', 'ac.activity_category_id=at.activity_category_id', 'LEFT')
            ->field('at.*, ac.activity_category_name')->where($condition)->order('at.activity_id desc')
            ->limit($params ['num'] )->page ( $params ['page'])->select();
        $returnArr = array (
                'totalCount' => $count,
                'list' => $list 
        );
        
        return $returnArr;
    }
    
    /**
     * 获取公告信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($id) {
        $condition = [ 
                'activity_id' => $id 
        ];
        $info = Loader::model ( 'Activity' )->where ( $condition )->find ()->toArray ();
        
        return $info;
    }
    
    /**
     * 新增
     *
     * @param
     *            $params
     * @return bool
     */
    public function add($params) {
        
        // 入库
        $data ['activity_name'] = $params ['activity_name'];
        $data ['activity_image'] = $params ['activity_image'];
        $data ['activity_mobile_image'] = $params ['activity_mobile_image'];
        $data ['activity_list_image'] = $params ['activity_list_image'];
        $data ['activity_introduction'] = htmlspecialchars_decode($params ['activity_introduction']);
        $data ['activity_description'] = htmlspecialchars_decode($params ['activity_description']);
        $data ['activity_starttime'] = $params ['activity_starttime'];
        $data ['activity_finishtime'] = $params ['activity_finishtime'];
        $data ['activity_is_banner'] = $params ['activity_is_banner'];
        $data ['activity_sort'] = $params ['activity_sort'];
        $data ['activity_status'] = $params ['activity_status'];
        $data ['activity_category_id'] = $params ['activity_category_id'] ? $params ['activity_category_id'] : 0;
        $data ['activity_lottery_type'] = $params ['activity_lottery_type'];

        $activityModel = Loader::model ( 'Activity' );
        $ret = $activityModel->save ( $data );
        if ($ret) {
            $activityInfo = [ 
                    'id' => $activityModel->activity_id 
            ];
            return $activityInfo;
        }
        $this->errorcode = EC_AD_ADD_ACTIVITY_ERROR;
        return false;
    }
    
    /**
     * 编辑
     *
     * @param
     *            $params
     * @return array
     */
    public function edit($params) {
        
        // 修改公告信息
        $data ['activity_name'] = $params ['activity_name'];
        $data ['activity_image'] = $params ['activity_image'];
        $data ['activity_mobile_image'] = $params ['activity_mobile_image'];
        $data ['activity_list_image'] = $params ['activity_list_image'];
        $data ['activity_introduction'] = htmlspecialchars_decode($params ['activity_introduction']);
        $data ['activity_description'] = htmlspecialchars_decode($params ['activity_description']);
        $data ['activity_starttime'] = $params ['activity_starttime'];
        $data ['activity_finishtime'] = $params ['activity_finishtime'];
        $data ['activity_is_banner'] = $params ['activity_is_banner'];
        $data ['activity_sort'] = $params ['activity_sort'];
        $data ['activity_status'] = $params ['activity_status'];
        $data ['activity_category_id'] = $params ['activity_category_id'] ? $params ['activity_category_id'] : 0;
        $data ['activity_lottery_type'] = $params ['activity_lottery_type'];

        Loader::model ( 'Activity' )->save ( $data, [ 
                'activity_id' => $params ['id'] 
        ] );
        
        return true;
    }
    
    /**
     * 删除
     *
     * @param
     *            $params
     * @return array
     */
    public function del($params) {
        $data ['activity_status'] = Config::get('status.activity_status')['deleted'];
        Loader::model ( 'Activity' )->save ( $data, [
            'activity_id' => $params ['id']
        ] );

        return true;
    }
    
    /**
     * 修改状态
     *
     * @param
     *            $params
     * @return array
     */
    public function changeStatus($params) {
        $updateData ['activity_status'] = $params ['status'];
        Loader::model ( 'Activity' )->save ( $updateData, [ 
                'activity_id' => $params ['id'] 
        ] );
        
        return true;
    }

    /**
     * 添加活动类目
     * @param
     * @return
     */
    public function addCategory($params){
        $data = array();
        $data['activity_category_name'] = $params['activity_category_name'];
        $data['sort'] = $params['sort'];
        $data['add_time'] = date('Y-m-d H:i:s');
        $activityModel = Loader::model('ActivityCategory');
        $result = $activityModel->save($data);   
        if($result) {
            return true;
        }else{
            $this->errorcode = EC_AD_ADD_CATEGORY_ERROR;
            return false;            
        }     
    }

    /**
     * 修改活动类目
     * @param
     * @return
     */
    public function editCategory($params){
        $data = array();
        $data['activity_category_name'] = $params['activity_category_name'];
        $data['sort'] = $params['sort'];
        $data['edit_time'] = date('Y-m-d H:i:s');
        $activityModel = Loader::model('ActivityCategory');
        $result = $activityModel->save($data,[ 
                'activity_category_id' => $params['activity_category_id'] 
        ] );   
        if($result) {
            return true;
        }else{
            $this->errorcode = EC_AD_EDIT_CATEGORY_ERROR;
            return false;            
        }     
    }

    /**
     * 删除活动类目
     * @param
     * @return
     */
    public function deleteCategory($params){
        $id = $params['activity_category_id'];
        $activityModel = Loader::model('ActivityCategory');
        $result = $activityModel->where(['activity_category_id' => $id])->delete();   
        if($result) {
            return true;
        }else{
            $this->errorcode = EC_AD_DEL_CATEGORY_ERROR;
            return false;            
        }     
    }

    /**
     * 获取类目列表
     */
    public function getCategoryList($params){
        $condition = [];
        if (isset($params ['activity_category_name'])){
            $condition['activity_category_name'] = ['LIKE','%'.$params['activity_category_name'].'%'];
        }
        $activityModel = Loader::model('ActivityCategory');
        $count = $activityModel->where($condition)->count();
        $list  = $activityModel->where($condition)->order('sort desc')->limit($params['num'])->page($params['page'])->select();
        $returnArr = array (
                'totalCount' => $count,
                'list' => $list 
        );
        return $returnArr;        
    }
}