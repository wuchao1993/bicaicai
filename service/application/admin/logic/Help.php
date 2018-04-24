<?php

/**
 * 咨询相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class Help extends Model {
    
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
        $helpModel = Loader::model ( 'Help' );
        
        $condition = [ ];
        if (isset ( $params ['help_type'] )) {
            $condition ['help_type'] = $params ['help_type'];
        }
        
        // 获取总条数
        $count = $helpModel->where ( $condition )->count ();
        
        $list = $helpModel->where ( $condition )->order ( 'help_id desc' )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();
        
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
                'help_id' => $id 
        ];
        $info = Loader::model ( 'Help' )->where ( $condition )->find ()->toArray ();
        
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
        $data ['help_title'] = $params ['help_title'];
        $data ['help_type'] = $params ['help_type'];
        $data ['help_content'] = $params ['help_content'];
        $data ['help_createtime'] = $params ['help_createtime'];
        $data ['help_status'] = $params ['help_status'];
        
        $helpModel = Loader::model ( 'Help' );
        $ret = $helpModel->save ( $data );
        if ($ret) {
            $helpInfo = [ 
                    'id' => $helpModel->help_id 
            ];
            return $helpInfo;
        }
        $this->errorcode = EC_AD_ADD_NOTICE_ERROR;
        return false;
    }
    
    /**
     * 编辑
     *
     * @param
     *            $params
     * @return bool
     */
    public function edit($params) {
        
        // 修改咨询信息
        $data ['help_title'] = $params ['help_title'];
        $data ['help_type'] = $params ['help_type'];
        $data ['help_content'] = $params ['help_content'];
        $data ['help_createtime'] = $params ['help_createtime'];
        $data ['help_status'] = $params ['help_status'];
        Loader::model ( 'Help' )->save ( $data, [ 
                'help_id' => $params ['id'] 
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
        $ret = Loader::model ( 'Help' )->where ( [ 
                'help_id' => $params ['id'] 
        ] )->delete ();
        
        return $ret;
    }
    
    /**
     * 修改状态
     *
     * @param
     *            $params
     * @return bool
     */
    public function changeStatus($params) {
        $updateData ['help_status'] = $params ['status'];
        $ret = Loader::model ( 'Help' )->save ( $updateData, [
                'help_id' => $params ['id'] 
        ] );

        return $ret;
    }
}