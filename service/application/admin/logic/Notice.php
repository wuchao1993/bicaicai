<?php

/**
 * 公告相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;

class Notice extends Model {
    
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
        $noticeModel = Loader::model ( 'Notice' );
        
        $condition = [ ];
        if (isset ( $params ['notice_type'] )) {
            $condition ['notice_type'] = $params ['notice_type'];
        }
        
        // 获取总条数
        $count = $noticeModel->where ( $condition )->count ();
        $orderBy = ['notice_sort' => 'desc', 'notice_createtime' => 'desc'];
        $list = $noticeModel->where ( $condition )->order ( $orderBy )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();
        
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
                'notice_id' => $id 
        ];
        $info = Loader::model ( 'Notice' )->where ( $condition )->find ()->toArray ();
        
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
        $data ['notice_title'] = $params ['notice_title'];
        $data ['notice_type'] = $params ['notice_type'];
        $data ['notice_lottery_type'] = $params ['notice_lottery_type'];
        $data ['notice_introduction'] = $params ['notice_introduction'];
        $data ['notice_content'] = $params ['notice_content'];
        $data ['notice_createtime'] = $params ['notice_createtime'];
        $data ['notice_status'] = $params ['notice_status'];
        $data ['notice_sort'] = $params ['notice_sort'];
        $data ['notice_marquee'] = $params['notice_marquee'];

        $noticeModel = Loader::model ( 'Notice' );
        $ret = $noticeModel->save ( $data );
        if ($ret) {
            $noticeInfo = [ 
                    'id' => $noticeModel->notice_id 
            ];
            return $noticeInfo;
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
        
        // 修改公告信息
        $data ['notice_title'] = $params ['notice_title'];
        $data ['notice_type'] = $params ['notice_type'];
        $data ['notice_lottery_type'] = $params ['notice_lottery_type'];
        $data ['notice_introduction'] = $params ['notice_introduction'];
        $data ['notice_content'] = $params ['notice_content'];
        $data ['notice_createtime'] = $params ['notice_createtime'];
        $data ['notice_status'] = $params ['notice_status'];
        $data ['notice_sort'] = $params ['notice_sort'];
        $data ['notice_marquee'] = $params['notice_marquee'];
        Loader::model ( 'Notice' )->save ( $data, [ 
                'notice_id' => $params ['id']
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
        $ret = Loader::model ( 'Notice' )->where ( [ 
                'notice_id' => $params ['id'] 
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
        $updateData ['notice_status'] = $params ['status'];
        Loader::model ( 'Notice' )->save ( $updateData, [ 
                'notice_id' => $params ['id'] 
        ] );
        
        return true;
    }
}