<?php
/**
 * 配置相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;

class ConfigManagement extends Model {

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
        $configModel = Loader::model('Config');

        $condition = [];
        if(!empty($params['group'])) {
            $condition = ['group' => $params['group']];
        }
        if(!empty($params['name'])) {
            $condition = [
                'name' => [
                    'LIKE',
                    '%' . $params['name'] . '%'
                ]
            ];
        }
        if(!empty($params['type'])) {
            $condition['type'] = $params['type'];
        }

        //获取总条数
        $count = $configModel->where($condition)->count();

        $list = $configModel->field('id,name,title,remark,group,extra,type,value,sort,status')->where($condition)->order('sort asc')->limit($params['num'])->page($params['page'])->select();

        if(!empty($list)) {
            foreach($list as &$val) {
                $val['extra'] = htmlspecialchars_decode($val['extra']);
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
        $condition     = ['id' => $id];
        $info          = Loader::model('Config')->where($condition)->find();
        $info['extra'] = htmlspecialchars_decode($info['extra']);

        return $info;
    }

    /**
     * 新增
     * @param $params
     * @return bool
     */
    public function add($params) {

        //查找是否有相同的名称
        $info = Loader::model('Config')->where(['name' => $params['name']])->find();
        if(!empty($info)) {
            $this->errorcode = EC_AD_CONFIG_EXISTING;

            return false;
        }

        //入库
        $data['name']        = $params['name'];
        $data['title']       = $params['title'];
        $data['remark']      = $params['remark'];
        $data['type']        = $params['type'];
        $data['group']       = $params['group'];
        $data['extra']       = $params['extra'];
        $data['value']       = $params['value'];
        $data['sort']        = $params['sort'];
        $data['status']      = 1;
        $data['create_time'] = time();

        $configModel = Loader::model('Config');
        $ret         = $configModel->save($data);
        if($ret) {
            $configInfo = [
                'id' => $configModel->id
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
        $info = Loader::model('Config')->where(['id' => $params['id']])->find();
        if(!$info) {
            $this->errorcode = EC_AD_CONFIG_NONE;

            return false;
        }

        //获取配置信息
        $info = Loader::model('Config')->where(['name' => $params['name']])->find();
        if(!empty($info) && $info['id'] != $params['id']) {
            $this->errorcode = EC_AD_CONFIG_EXISTING;

            return false;
        }

        //修改配置信息
        $updateData['name']        = $params['name'];
        $updateData['title']       = $params['title'];
        $updateData['remark']      = $params['remark'];
        $updateData['group']       = $params['group'];
        $updateData['extra']       = $params['extra'];
        $updateData['value']       = $params['value'];
        $updateData['sort']        = $params['sort'];
        $updateData['update_time'] = time();

        $actionData = Loader::model('General','logic')->getActionData($params['id'],$updateData,'Config');

        Loader::model('Config')->save($updateData, ['id' => $info['id']]);

        //记录行为
        Loader::model('General', 'logic')->actionLog('update_config', 'Config', $info['id'], MEMBER_ID,json_encode($actionData));

        return true;
    }

    /**
     * 删除
     * @param $params
     * @return array
     */
    public function del($params) {

        foreach($params['id'] as $val) {
            $actionData = [];
            $actionData =  Loader::model('General','logic')->getActionData($val,'','Config');

            $ret = Loader::model('Config')->where(['id' => $val])->delete();

            //记录行为
            Loader::model('General', 'logic')->actionLog('update_config', 'Config', $params['id'], MEMBER_ID,json_encode($actionData));
        }

        return $ret;
    }

    /**
     * 排序
     * @param $ids
     * @return array
     */
    public function sort($ids) {

        $configModel = Loader::model('Config');
        foreach($ids as $sort => $id) {

            $updateData['sort']        = $sort;
            $updateData['update_time'] = time();
            $configModel->save($updateData, ['id' => $id]);
        }

        return true;
    }

    /**
     * 获取网站配置列表
     * @param $params
     * @return array
     */
    public function getGroupList($params) {
        $condition = ['group' => $params['group']];

        $list = Loader::model('Config')->field('id,name,title,remark,group,extra,type,value,sort,status')->where($condition)->order('sort asc')->select();

        if(!empty($list)) {
            foreach($list as &$val) {
                $val['extra'] = htmlspecialchars_decode($val['extra']);
            }
        }

        return $list;
    }

    /**
     * 更新网站配置
     * @param $params
     * @return bool
     */
    public function editGroup($params) {

        $configModel = Loader::model('Config');

        foreach($params['config'] as $id => $value) {
            $updateData = [];
            $updateData['value']       = $value;
            $updateData['update_time'] = time();
            $configModel->where('id',$id)->update($updateData);
        }

        return true;
    }

    /**
     * 获取网站基本设置列表
     *
     * @return array
     */
    public function getSiteConfigList() {

        $condition = ['name' => ['IN', ['ADMIN_LOGIN_LOGO','ADMIN_SITE_LOGO','ADMIN_LOGIN_TEXT','COLOR_STYLE'] ] ];

        $list = Loader::model('Config')->field('name,value')->where($condition)->select();
        $appName = \think\Env::get('redis.prefix');
        if (empty($appName) ) {
            $this->errorcode = EC_AD_SWOOLE_APPNAME_EMPTY;
           // return false;
        }else{
            $list[] = ['name'=>'APP_NAME','value'=>$appName ];
        }
        return $list;
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

        $condition['name'] = $name;

        $info = Loader::model('Config')->where($condition)->find();
        $info['value'] = $this->parse($info['type'],$info['value']);

        return $info;
    }


    public function addRegConf(){

        $data = array(
            'name'  => CONFIG_REGISTER,
            'type'  => '3',
            'title' => '注册扩展配置',
            'group' => '3',
            'extra' => '1:是验证，0:不验证',
            'remark' => '注册扩展字段配置，例：\r\nneed_email:0\r\nneed_mobile:0\r\nneed_reg_code:0\r\n支持：邮箱、手机号；\r\n格式：need_字段英文:数值；\r\n有字段，代表显示这个字段，后面的数值代表是否验证1-是验证，0-不验证；\r\nreg_code 是一定显示的，只判断后面数值是否验证。',
            'create_time' => time(),
            'update_time' => time(),
            'status' => '1',
            'value' => 'need_reg_code:0',
            'sort' => '0',
        );
        Loader::model('Config')->add($data);
    }    

    public function addAgentRegConf(){

        $data = array(
            'name'  => AGENT_CONFIG_REGISTER,
            'type'  => '3',
            'title' => '代理注册扩展配置',
            'group' => '3',
            'extra' => '1:是验证，0:不验证',
            'remark' => '注册扩展字段配置，例：\r\nneed_email:0\r\nneed_contact_info:0\r\nneed_qq:0\r\n支持：邮箱、手机号；\r\n格式：need_字段英文:数值；\r\n有字段，代表显示这个字段，后面的数值代表是否验证1-是验证，0-不验证；\r\nneed_contact_info 是一定显示的，只判断后面数值是否验证。',
            'create_time' => time(),
            'update_time' => time(),
            'status' => '1',
            'value' => 'need_contact_info:0',
            'sort' => '0',
        );
        Loader::model('Config')->add($data);
    }

    public function editAgentRegConf($param,$id){

        $reg_extend = array();

        if(!empty($param)){
            //过滤字段
            $needField = ['need_contact_info','need_qq'];
            foreach ($param as $key=>$vo){
                if(in_array($key, $needField) && $vo['is_show'] == 1){
                    $reg_extend[] = $key.':'.$vo['is_check'];
                }
            }
            $value = implode("\n",$reg_extend);

            $res =  Loader::model('Config')->save(['value'=>$value],['name'=>AGENT_CONFIG_REGISTER]);

            if($res!=false){

                $updateData = ['value'=>$value];

                $actionData = Loader::model('General','logic')->getActionData($id,$updateData,'Config');

                Loader::model('General', 'logic')->actionLog('update_config', 'Config', $id, MEMBER_ID, json_encode($actionData));
                return true;
            }
        }
        return false;
    }

    public function editRegConf($param,$id){

        $reg_extend = array();
        if(!empty($param)){
            //过滤字段
            $needField = ['need_mobile','need_email','need_reg_code','need_qq'];
            foreach ($param as $key=>$vo){
                if( in_array($key, $needField) && $vo['is_show'] == 1){
                    $reg_extend[] = $key.':'.$vo['is_check'];
                }
            }
            $value = implode("\n",$reg_extend);

            $res =  Loader::model('Config')->save(['value'=>$value],['name'=>CONFIG_REGISTER]);

            if($res!=false){

                $updateData = ['value'=>$value];

                $actionData = Loader::model('General','logic')->getActionData($id,$updateData,'Config');

                Loader::model('General', 'logic')->actionLog('update_config', 'Config', $id, MEMBER_ID, json_encode($actionData));
                return true;
            }
        }
        return false;
    }



}