<?php
/**
 * 用户行为相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;

class Action extends Model {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取用户行为列表
     * @param $params
     * @return array
     */
    public function getList($params) {
        $actionModel = Loader::model('Action');

        $condition = [
            'status' => [
                'neq',
                Config::get('status.action_status')['delete']
            ]
        ];

        //获取总条数
        $count = $actionModel->where($condition)->count();

        if($count > 0) {
            $list = $actionModel->field('id,name,title,remark,rule,log,type,status')->where($condition)->order('id desc')->limit($params['num'])->page($params['page'])->select();
        }else {
            $list = [];
        }
        $returnArr = array(
            'totalCount' => $count,
            'list'       => $list
        );

        return $returnArr;
    }

    /**
     * 获取用户行为信息
     * @param $id
     * @return array
     */
    public function getInfo($id) {
        $condition = ['id' => $id];
        $info      = Loader::model('Action')->where($condition)->find()->toArray();

        return $info;
    }

    /**
     * 新增
     * @param $params
     * @return bool
     */
    public function add($params) {

        //获取用户行为信息
        $info = Loader::model('Action')->where(['name' => $params['name'], 'status' => Config::get('status.action_status')['delete']])->find();
        if($info) {
            $this->errorcode = EC_AD_ACTION_NONE;

            return false;
        }

        //入库
        $data['name']        = $params['name'];
        $data['title']       = $params['title'];
        $data['remark']      = $params['remark'];
        $data['rule']        = $params['rule'];
        $data['log']         = $params['log'];
        $data['type']        = $params['type'];
        $data['update_time'] = time();

        $actionModel = Loader::model('Action');
        $ret         = $actionModel->save($data);

        if($ret) {
            $actionInfo = [
                'id' => $actionModel->id
            ];

            return $actionInfo;
        }else {
            $this->errorcode = EC_AD_ADD_ACTION_ERROR;
            return false;
        }
    }

    /**
     * 编辑
     * @param $params
     * @return array
     */
    public function edit($params) {

        //获取用户行为信息
        $info = Loader::model('Action')->where(['id' => $params['id']])->find();
        if(!$info) {
            $this->errorcode = EC_AD_ACTION_NONE;

            return false;
        }

        //修改用户行为信息
        $updateData['name']        = $params['name'];
        $updateData['title']       = $params['title'];
        $updateData['remark']      = $params['remark'];
        $updateData['rule']        = $params['rule'];
        $updateData['log']         = $params['log'];
        $updateData['type']        = $params['type'];
        $updateData['update_time'] = time();

        return Loader::model('Action')->save($updateData, ['id' => $info['id']]);

    }

    /**
     * 删除
     * @param $params
     * @return array
     */
    public function del($params) {

        foreach($params['id'] as $val) {
            $updateData['status'] = Config::get('status.action_status')['delete'];
            $ret = Loader::model('Action')->save($updateData, ['id' => $val]);

            if($ret == false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 修改状态
     * @param $params
     * @return array
     */
    public function changeStatus($params) {

        foreach($params['id'] as $val) {
            $updateData['status'] = $params['status'];
            $ret = Loader::model('Action')->save($updateData, ['id' => $val]);

            if($ret == false) {
                return false;
            }
        }

        return true;
    }

}