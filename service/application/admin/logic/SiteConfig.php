<?php
/**
 * 网站配置（新）
 * @author paulli
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;

class SiteConfig extends Model {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 获取配置列表
     * @param $params
     * @return array
     */
    public function getList($params) {
        $configModel = Loader::model('SiteConfig');

        $condition = [];

        if(!empty($params['sc_group'])) {
            $condition = ['sc_group' => $params['sc_group']];
        }

        //获取总条数
        $count = $configModel->where($condition)->count();

        $list = $configModel->where($condition)->order('sc_sort asc')->limit($params['num'])->page($params['page'])->select();

        if(!empty($list)) {
            foreach($list as &$val) {
                $val['sc_extra'] = htmlspecialchars_decode($val['sc_extra']);
            }
        }

        $returnArr = array(
            'totalCount' => $count,
            'list'       => $list
        );

        return $returnArr;
    }

    /**
     * 获取配置信息
     * @param $id
     * @return array
     */
    public function getInfo($id) {
        $condition     = ['sc_id' => $id];
        $info          = Loader::model('Config')->where($condition)->find();
        $info['sc_extra'] = htmlspecialchars_decode($info['sc_extra']);

        return $info;
    }

    /**
     * 新增
     * @param $params
     * @return bool
     */
    public function add($params) {

        $configModel = Loader::model('SiteConfig');

        //查找是否有相同的名称
        $info = $configModel->where(['sc_name' => $params['sc_name']])->find();
        if(!empty($info)) {
            $this->errorcode = EC_AD_CONFIG_EXISTING;

            return false;
        }

        //入库
        $data['sc_name']         = $params['sc_name'];
        $data['sc_title']        = $params['sc_title'];
        $data['sc_remark']       = $params['sc_remark'];
        $data['sc_lottery_type'] = $params['sc_lottery_type'];
        $data['sc_type']         = $params['sc_type'];
        $data['sc_group']        = $params['sc_group'];
        $data['sc_extra']        = $params['sc_extra'];
        $data['sc_value']        = $params['sc_value'];
        $data['sc_sort']         = $params['sc_sort'];
        $data['sc_status']       = 1;
        $data['sc_createtime']   = time();


        $ret         = $configModel->save($data);
        if($ret) {
            $configInfo = [
                'id' => $configModel->sc_id
            ];

            return $configInfo;
        }
        $this->errorcode = EC_USER_REG_FAILURE;

        return false;
    }

    /**
     * 编辑
     * @param $params
     * @return array
     */
    public function edit($params) {

        //查找是否有相同的名称
        $info = Loader::model('SiteConfig')->where(['sc_id' => $params['sc_id']])->find();
        if(!$info) {
            $this->errorcode = EC_AD_CONFIG_NONE;

            return false;
        }

        //获取配置信息
        $info = Loader::model('SiteConfig')->where(['sc_name' => $params['sc_name']])->find();
        if(!empty($info) && $info['sc_id'] != $params['sc_id']) {
            $this->errorcode = EC_AD_CONFIG_EXISTING;

            return false;
        }

        //修改配置信息
        $updateData['sc_name']        = $params['sc_name'];
        $updateData['sc_title']       = $params['sc_title'];
        $updateData['sc_remark']      = $params['sc_remark'];
        $updateData['sc_group']       = $params['sc_group'];
        $updateData['sc_extra']       = $params['sc_extra'];
        $updateData['sc_value']       = $params['sc_value'];
        $updateData['sc_sort']        = $params['sc_sort'];
        $updateData['sc_modifytime']  = time();

        $actionData = Loader::model('General','logic')->getActionData($params['sc_id'],$updateData,'SiteConfig');

        Loader::model('SiteConfig')->where('sc_id',$info['sc_id'])->update($updateData);

        //记录行为
        Loader::model('General', 'logic')->actionLog('update_config', 'SiteConfig', $info['sc_id'], MEMBER_ID,json_encode($actionData));

        return true;
    }

    /**
     * 删除
     * @param $params
     * @return array
     */
    public function del($params) {

        foreach($params['sc_id'] as $val) {
            $actionData = [];
            $actionData =  Loader::model('General','logic')->getActionData($val,'','SiteConfig');

            $ret = Loader::model('SiteConfig')->where(['sc_id' => $val])->delete();

            //记录行为
            Loader::model('General', 'logic')->actionLog('update_config', 'SiteConfig', $params['sc_id'], MEMBER_ID,json_encode($actionData));
        }

        return $ret;
    }

    /**
     * 更新网站配置
     * @param $params
     * @return bool
     */
    public function editGroup($params) {

        $configModel = Loader::model('SiteConfig');

        foreach($params['config'] as $id => $value) {
            $updateData = [];
            $updateData['sc_value']       = $value;
            $updateData['sc_modifytime'] = time();
            $configModel->where('sc_id',$id)->update($updateData);
        }

        return true;
    }

    /**
     * 根据配置类型解析配置
     * @param  integer $type  配置类型
     * @param  string  $value 配置值
     */
    public function parse($type, $value){
        switch ($type) {
            case 3: //解析数组
                $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
                if(strpos($value,':')){
                    $value  = array();
                    foreach ($array as $val) {
                        list($k, $v) = explode(':', $val);
                        $value[$k]   = $v;
                    }
                }else{
                    $value =    $array;
                }
                break;
        }
        return $value;
    }

    /**
     * 根据英文名称获取配置
     * @param $name
     * @return mixed
     */
    public function getMapByName($name){
        $condition = array();

        $condition['sc_name'] = $name;

        $info = Loader::model('SiteConfig')->where($condition)->find();
        $info['value'] = $this->parse($info['sc_type'],$info['sc_value']);

        return $info;
    }

}