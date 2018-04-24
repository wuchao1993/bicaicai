<?php

/**
 * 配置控制器
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class ConfigManagement {

    /**
     * 获取配置列表
     *
     * @param Request $request
     * @return array
     */
    public function getConfigList(Request $request) {
        $params ['group'] = $request->param('group',0);
        $params ['page']  = $request->param('page',1);
        $params ['num']   = $request->param('num',10);

        if($request->param('name') != '') {
            $params ['name'] = $request->param('name');
        }
        if($request->param('type') != '') {
            $params ['type'] = $request->param('type');
        }

        $configLogic = Loader::model('ConfigManagement', 'logic');
        $configList  = $configLogic->getList($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => output_format($configList)
        ];
    }

    /**
     * 获取配置信息
     *
     * @param Request $request
     * @return array
     */
    public function getConfigInfo(Request $request) {
        $id = $request->param('id');

        $configLogic = Loader::model('ConfigManagement', 'logic');
        $configInfo  = $configLogic->getInfo($id);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => output_format($configInfo)
        ];
    }

    /**
     * 新增配置
     *
     * @param Request $request
     * @return array
     */
    public function addConfig(Request $request) {
        $params ['name']   = $request->param('name');
        $params ['title']  = $request->param('title');
        $params ['remark'] = $request->param('remark','');
        $params ['type']   = $request->param('type',0);
        $params ['group']  = $request->param('group',0);
        $params ['extra']  = $request->param('extra','');
        $params ['value']  = $request->param('value','');
        $params ['sort']   = $request->param('sort',0);

        $configLogic = Loader::model('ConfigManagement', 'logic');
        $configInfo  = $configLogic->add($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => output_format($configInfo)
        ];
    }

    /**
     * 编辑配置
     *
     * @param Request $request
     * @return array
     */
    public function editConfig(Request $request) {
        $params ['id']     = $request->param('id');
        $params ['name']   = $request->param('name');
        $params ['title']  = $request->param('title');
        $params ['remark'] = $request->param('remark','');
        $params ['group']  = $request->param('group',3);
        $params ['extra']  = $request->param('extra','');
        $params ['value']  = $request->param('value','');
        $params ['sort']   = $request->param('sort',0);

        $configLogic = Loader::model('ConfigManagement', 'logic');
        $result      = $configLogic->edit($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 删除配置
     *
     * @param Request $request
     * @return array
     */
    public function delConfig(Request $request) {
        $params ['id'] = $request->param('id/a');

        $configLogic = Loader::model('ConfigManagement', 'logic');
        $result      = $configLogic->del($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 配置排序
     *
     * @param Request $request
     * @return array
     */
    public function sortConfig(Request $request) {
        $ids = $request->param('ids/a');

        $configLogic = Loader::model('ConfigManagement', 'logic');
        $result      = $configLogic->sort($ids);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 获取网站设置列表
     *
     * @param Request $request
     * @return array
     */
    public function getGroupList(Request $request) {
        $params ['group'] = $request->param('group',1);
        $configLogic      = Loader::model('ConfigManagement', 'logic');
        $groupList        = $configLogic->getGroupList($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => output_format($groupList)
        ];
    }

    /**
     * 编辑网站设置
     *
     * @param Request $request
     * @return array
     */
    public function editGroup(Request $request) {
        $params ['config'] = $request->param('config/a');

        $configLogic = Loader::model('ConfigManagement', 'logic');
        $result      = $configLogic->editGroup($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => $result
        ];
    }


    /**
     * 获取注册配置
     * @author jesse.lin.989@gmail.com
     * @return array
     */
    public function getRegConf(){
        $logic = Loader::model('ConfigManagement','logic');

        $data = $logic->getMapByName(CONFIG_REGISTER);
        if(empty($data)){
            $logic->addRegConf();
        }

        $value = $data['value'];
        if(!empty($value)){
            $tmp = [];
            foreach ($value as $key=>$item){
                $tmp[$key]['is_show'] = 1;
                $tmp[$key]['is_check']= (int)$item;
            }
            $value = $tmp;
        }
        $response['value'] = $value;
        $response['id']    = $data['id'];
        $response['title'] = $data['title'];

        return [
            'errorcode' => $logic->errorcode,
            'message'   => Config::get('errorcode') [$logic->errorcode],
            'data'      => $response
        ];
    }    

    /**
     * 获取代理注册配置
     * @author fore
     * @return array
     */
    public function getAgentRegConf(){
        $logic = Loader::model('ConfigManagement','logic');

        $data = $logic->getMapByName(AGENT_CONFIG_REGISTER);
        if(empty($data)){
            $logic->addAgentRegConf();
        }

        $value = $data['value'];
        if(!empty($value)){
            $tmp = [];
            foreach ($value as $key=>$item){
                $tmp[$key]['is_show'] = 1;
                $tmp[$key]['is_check']= (int)$item;
            }
            $value = $tmp;
        }
        $response['value'] = $value;
        $response['id']    = $data['id'];
        $response['title'] = $data['title'];

        return [
            'errorcode' => $logic->errorcode,
            'message'   => Config::get('errorcode') [$logic->errorcode],
            'data'      => $response
        ];
    }

    /**
     * 编辑代理注册配置
     */
    public function editAgentRegConf(Request $request){
        $id = $request->param('id/d');
        $param = $request->param();

        $logic = Loader::model('ConfigManagement','logic');

        $result = $logic->editAgentRegConf($param,$id);

        return [
            'errorcode' => $logic->errorcode,
            'message'   => Config::get('errorcode') [$logic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 编辑注册配置
     * @author jesse.lin.989@gmail.com
     */
    public function editRegConf(Request $request){
        $id = $request->param('id/d');
        $param = $request->param();
        unset($param['id']);

        $logic = Loader::model('ConfigManagement','logic');

        $result = $logic->editRegConf($param,$id);

        return [
            'errorcode' => $logic->errorcode,
            'message'   => Config::get('errorcode') [$logic->errorcode],
            'data'      => $result
        ];
    }


}
