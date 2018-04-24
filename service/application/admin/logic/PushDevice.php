<?php

/**
 * 推送设备相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class PushDevice extends Model {
    
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
        $pushMessageModel = Loader::model ( 'PushDevice' );
        
        $condition = [ ];
        if (isset ( $params ['pd_type'] )) {
            $condition ['pd_type'] = $params ['pd_type'];
        }
        
        // 获取总条数
        $count = $pushMessageModel->where ( $condition )->count ();
        
        $list = $pushMessageModel->where ( $condition )->order ( 'pd_id desc' )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();
        
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
                'pd_id' => $id 
        ];
        $info = Loader::model ( 'PushDevice' )->where ( $condition )->find ()->toArray ();
        
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
        $data ['pd_title'] = $params ['pd_title'];
        $data ['pd_type'] = $params ['pd_type'];
        $data ['pd_content'] = $params ['pd_content'];
        $data ['pd_createtime'] = date ( 'Y-m-d H:i:s' );
        
        $pushMessageModel = Loader::model ( 'PushDevice' );
        $ret = $pushMessageModel->save ( $data );
        if ($ret) {
            $id = $pushMessageModel->pd_id;
            
            // 是否推送
            if ($params ['add_type'] == 2) {
                $this->report ( $id );
            }
            
            $pushMessageInfo = [ 
                    'id' => $pushMessageModel->pd_id 
            ];
            return $pushMessageInfo;
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
        $data ['pd_title'] = $params ['pd_title'];
        $data ['pd_type'] = $params ['pd_type'];
        $data ['pd_content'] = $params ['pd_content'];
        $data ['pd_modifytime'] = $params ['pd_modifytime'];
        
        Loader::model ( 'PushDevice' )->save ( $data, [ 
                'pd_id' => $params ['id'] 
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
        $ret = Loader::model ( 'PushDevice' )->where ( [ 
                'pd_id' => $params ['id'] 
        ] )->delete ();
        
        return $ret;
    }
    public function getInfoByUserName($user_name) {
        $user_id = Loader::model ( 'User', 'logic' )->getUserIdByUsername ( $user_name );
        if ($user_id) {
            $condition ['user_id'] = $user_id;
            return Loader::model ( 'PushDevice' )->where ( $condition )->order ( 'pd_modifytime Desc' )->find ();
        } else {
            return false;
        }
    }
    public function getListByAppKey($app_key, $offset = 0, $count = 10) {
        $condition ['pd_app_key'] = $app_key;
        return Loader::model ( 'PushDevice' )->where ( $condition )->limit ( $offset, $count )->select ();
    }
}