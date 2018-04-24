<?php

/**
 * 咨询相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class Information extends Model {
    
    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取咨询列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $informationModel = Loader::model ( 'Information' );
        
        $condition = [ ];
        if (isset ( $params ['information_type'] )) {
            $condition ['information_type'] = $params ['information_type'];
        }
        
        // 获取总条数
        $count = $informationModel->where ( $condition )->count ();
        
        $list = $informationModel->where ( $condition )->order ( 'information_sort asc,information_id desc' )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();
        
        $returnArr = array (
                'totalCount' => $count,
                'list' => $list 
        );
        
        return $returnArr;
    }
    
    /**
     * 获取咨询信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($id) {
        $condition = [ 
                'information_id' => $id 
        ];
        $info = Loader::model ( 'Information' )->where ( $condition )->find ()->toArray ();
        
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
        $data ['information_title'] = $params ['information_title'];
        $data ['information_type'] = $params ['information_type'];
        $data ['information_source'] = $params ['information_source'];
        $data ['information_content'] = $params ['information_content'];
        $data ['information_createtime'] = $params ['information_createtime'];
        $data ['information_status'] = $params ['information_status'];
        $data ['information_sort'] = $params ['information_sort'];

        $informationModel = Loader::model ( 'Information' );
        $ret = $informationModel->save ( $data );
        if ($ret) {
            $informationInfo = [ 
                    'id' => $informationModel->information_id 
            ];
            return $informationInfo;
        }
        $this->errorcode = EC_AD_ADD_NOTICE_ERROR;
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
        
        // 修改咨询信息
        $data ['information_title'] = $params ['information_title'];
        $data ['information_type'] = $params ['information_type'];
        $data ['information_source'] = $params ['information_source'];
        $data ['information_content'] = $params ['information_content'];
        $data ['information_createtime'] = $params ['information_createtime'];
        $data ['information_status'] = $params ['information_status'];
        $data ['information_sort'] = $params ['information_sort'];
        Loader::model ( 'Information' )->save ( $data, [ 
                'information_id' => $params ['id'] 
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
        $ret = Loader::model ( 'Information' )->where ( [ 
                'information_id' => $params ['id'] 
        ] )->delete ();
        
        return $ret;
    }
    
    /**
     * 修改状态
     *
     * @param
     *            $params
     * @return array
     */
    public function changeStatus($params) {
        $updateData ['information_status'] = $params ['status'];
        $ret = Loader::model ( 'Information' )->save ( $updateData, [
                'information_id' => $params ['id'] 
        ] );
        
        return $ret;
    }
}