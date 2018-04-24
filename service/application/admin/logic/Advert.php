<?php

/**
 * 弹窗广告相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;

class Advert extends Model {
    
    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取弹窗广告列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $advertModel = Loader::model ( 'Advert' );
        
        $condition = [ ];
        $condition ['advert_status'] = ['NEQ',Config::get('status.advert_status')['deleted']];
        // 获取总条数
        $count = $advertModel->where ( $condition )->count ();
        
        $list = $advertModel->where ( $condition )->order ( 'advert_id desc' )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();
        
        $returnArr = array (
                'totalCount' => $count,
                'list' => $list 
        );
        
        return $returnArr;
    }

    /**
     * 获取弹窗广告信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($id) {
        $condition = [ 
                'advert_id' => $id 
        ];
        $info = Loader::model ( 'Advert' )->where ( $condition )->find ()->toArray ();
        
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
        $data ['advert_name'] = $params ['advert_name'];
        $data ['advert_image'] = $params ['advert_image'];
        $data ['advert_url'] = $params ['advert_url'];
        $data ['advert_type'] = $params ['advert_type'];
        $data ['advert_pos'] = $params ['advert_pos'];
        $data ['advert_format'] = $params ['advert_format'];
        $data ['advert_text_app'] = $params ['advert_text_app'];
        $data ['advert_text_pc'] = $params ['advert_text_pc'];
        $data ['advert_status'] = $params ['advert_status'];
        
        $advertModel = Loader::model ( 'Advert' );
        $ret = $advertModel->save ( $data );
        if ($ret) {
            $advertInfo = [ 
                    'id' => $advertModel->advert_id 
            ];
            return $advertInfo;
        }
        $this->errorcode = EC_AD_ADD_ADVERT_ERROR;
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
        
        // 修改弹窗广告信息
        $data ['advert_name'] = $params ['advert_name'];
        $data ['advert_image'] = $params ['advert_image'];
        $data ['advert_url'] = $params ['advert_url'];
        $data ['advert_type'] = $params ['advert_type'];
        $data ['advert_pos'] = $params ['advert_pos'];
        $data ['advert_format'] = $params ['advert_format'];
        $data ['advert_text_app'] = $params ['advert_text_app'];
        $data ['advert_text_pc'] = $params ['advert_text_pc'];
        $data ['advert_status'] = $params ['advert_status'];
        Loader::model ( 'Advert' )->save ( $data, [ 
                'advert_id' => $params ['id'] 
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
        $data ['advert_status'] = Config::get('status.advert_status')['deleted'];
        Loader::model ( 'Advert' )->save ( $data, [
            'advert_id' => $params ['id']
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
        $updateData ['advert_status'] = $params ['status'];
        $ret = Loader::model ( 'Advert' )->save ( $updateData, [
                'advert_id' => $params ['id'] 
        ] );
        
        return $ret;
    }

}