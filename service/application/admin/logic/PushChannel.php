<?php

/**
 * 推送渠道/应用信息相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;

class PushChannel extends Model {

    /**
     * 错误变量
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取咨询列表
     * @params $params
     * @return array
     */
    public function getList($params) {
        $pushChannelModel = Loader::model('PushChannel');
        $condition = [];
        // 获取总条数
        $count = $pushChannelModel->where($condition)->count();

        $list = $pushChannelModel->where($condition)->order('pc_id desc')->limit($params ['num'])->page($params ['page'])->select();

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }


    /**
     * 获取咨询信息
     * @params $params
     * @return array
     */
    public function getInfo($id) {
        $condition = [
            'pc_id' => $id,
        ];
        $info      = Loader::model('PushChannel')->where($condition)->find()->toArray();

        return $info;
    }


    public function getAppKey($id) {
        $info = $this->getInfo($id);
        return $info['pc_app_key'];
    }


    /**
     * 新增
     * @param $params
     * @return bool
     */
    public function add($params) {
        $pushChannelModel = Loader::model('PushChannel');

        // 查找是否有相同的名称
        $info = $pushChannelModel->where([
            'pc_app_key' => $params ['pc_app_key'],
        ])->find();
        if(!empty ($info)) {
            $this->errorcode = EC_AD_ADD_CHANNEL_EXISTING;

            return false;
        }

        // 入库
        $data ['pc_app_name']          = $params ['pc_app_name'];
        $data ['pc_app_key']           = $params ['pc_app_key'];
        $data ['pc_app_master_secret'] = $params ['pc_app_master_secret'];
        $data ['pc_platform']          = $params ['pc_platform'];
        $data ['pc_createtime']        = date('Y-m-d H:i:s');

        $ret = $pushChannelModel->save($data);
        if($ret) {
            $pushChannelInfo = [
                'id' => $pushChannelModel->pc_id,
            ];

            return $pushChannelInfo;
        }
        $this->errorcode = EC_AD_ADD_PUSH_CHANNEL_ERROR;

        return false;
    }


    /**
     * 编辑
     * @params $params
     * @return array
     */
    public function edit($params) {
        $pushChannelModel = Loader::model('PushChannel');

        // 查找是否有相同的名称
        $info = $pushChannelModel->where([
            'pc_app_key' => $params ['pc_app_key'],
        ])->find();
        if(!empty ($info) && $info ['pc_id'] != $params ['pc_id']) {
            $this->errorcode = EC_AD_ADD_CHANNEL_EXISTING;

            return false;
        }

        // 修改咨询信息
        $data ['pc_app_name']          = $params ['pc_app_name'];
        $data ['pc_app_key']           = $params ['pc_app_key'];
        $data ['pc_app_master_secret'] = $params ['pc_app_master_secret'];
        $data ['pc_platform']          = $params ['pc_platform'];
        $data ['pc_modifytime']        = date('Y-m-d H:i:s');

        $pushChannelModel->save($data, [
            'pc_id' => $params ['pc_id'],
        ]);

        return true;
    }


    /**
     * 删除
     * @params $params
     * @return array
     */
    public function del($params) {
        $ret = Loader::model('PushChannel')->where([
            'pc_id' => $params ['pc_id'],
        ])->delete();

        return $ret;
    }


    public function getInfoByAppKey($app_key) {
        $condition ['pc_app_key'] = $app_key;

        return Loader::model('PushChannel')->where($condition)->find();
    }
}