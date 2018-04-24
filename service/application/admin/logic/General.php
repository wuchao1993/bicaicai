<?php

/**
 * 业务逻辑
 * @createTime 2017/4/1 17:10
 */

namespace app\admin\logic;

use think\Loader;
use Filipac\Ip;
use jwt\Jwt;
use think\Config;
use think\Cache;
use think\Model;
use think\Request;

class General extends Model {

    /**
     * 错误代码变量
     *
     * @var
     *
     */
    public $errorcode;

    /**
     * 刷新token，延长token过期时间
     *
     * @param
     *            $token
     * @return bool
     */
    public function refreshToken($token) {
        // 验证token合法性
        $tokenInfo = Jwt::decode($token, Config::get('token_sign_key'));
        if(false === $tokenInfo) {
            $this->errorcode = Jwt::$errorcode;

            return false;
        }

        // 验证是否被人踢下线
        // redis里存每个uid对应token的创建时间，如果redis里的创建时间和当前Token里的创建时间不一样，说明有新的token生成
        $tokenCreateTime = Cache::get(Config::get('token_cache_key') . $tokenInfo->uid);
        if($tokenCreateTime != $tokenInfo->iat) {
            $this->errorcode = EC_OTHER_LOGIN;

            return false;
        }

        // 生成新的token
        $userInfo = [
            'uid'       => $tokenInfo->uid,
            'user_name' => $tokenInfo->user_name
        ];

        return Loader::model('Member', 'logic')->generateToken($userInfo);
    }

    /**
     * 校验是否拥有权限
     *
     * @param
     *            $uid
     * @param
     *            $method
     * @return bool
     */
    public function checkAccess($uid, $method) {
        // 如果是超级管理员，则跳过权限验证
        if($uid == 1) {
            return true;
        }

        // 获取用户组权限
        $authGroupAccessInfo = Loader::model('AuthGroupAccess')->field('group_id')->where(['uid' => $uid])->select();
        if(empty ($authGroupAccessInfo)) {
            return false;
        }

        $groupInfo = [];
        foreach($authGroupAccessInfo as $val) {
            $ruleInfo = Loader::model('AuthGroup', 'logic')->getInfo($val['group_id']);
            if(!empty($ruleInfo['rules'])) {
                $ruleArray = explode(',', $ruleInfo['rules']);
                $groupInfo = array_merge($groupInfo, $ruleArray);
            }
        }

        $groupInfo['rules'] = implode(',', $groupInfo);

        $ruleList = Loader::model('AuthRule')->field('name')->where('id in (' . $groupInfo ['rules'] . ')')->select();

        foreach($ruleList as $ruleInfo) {
            if($ruleInfo ['name'] == $method) {
                return true;
            }
        }

        return false;
    }

    /**
     * 记录行为日志，并执行该行为的规则
     * @param string $action 行为标识
     * @param string $model 触发行为的模型名
     * @param int $recordId 触发行为的记录id
     * @param int $uid 执行行为的用户id
     * @return boolean
     * @author huajie <banhuajie@163.com>
     */
    public function actionLog($action = null, $model = null, $recordId = null, $uid = null, $recordDetail = '') {

        //参数检查
        if(empty($action) || empty($model) || empty($recordId)) {
            $this->errorcode = EC_AD_PARAMS_ILLEGAL;

            return false;
        }
        if(empty($uid)) {
            $uid = MEMBER_ID;
        }

        //查询行为,判断是否执行
        $actionInfo = Loader::model('Action')->getByName($action);
        if($actionInfo['status'] != 1) {
            $this->errorcode = EC_AD_ACTION_NONE;

            return false;
        }

        //插入行为日志
        $data['action_id']   = $actionInfo['id'];
        $data['user_id']     = $uid;
        $data['action_ip']   = ip2long(Ip::get());
        $data['model']       = $model;
        $data['record_id']   = $recordId;
        $data['create_time'] = time();

        //解析日志规则,生成日志备注
        if(!empty($actionInfo['log'])) {
            if(preg_match_all('/\[(\S+?)\]/', $actionInfo['log'], $match)) {
                $log['user']   = $uid;
                $log['record'] = $recordId;
                $log['model']  = $model;
                $log['time']   = time();
                $log['data']   = array(
                    'user'   => $uid,
                    'model'  => $model,
                    'record' => $recordId,
                    'time'   => time()
                );
                foreach($match[1] as $value) {
                    $param = explode('|', $value);
                    if(isset($param[1])) {
                        //获取管理员昵称
                        if($param[1] == 'get_nickname') {
                            $memberLogic = Loader::model('Member', 'logic');
                            $memberInfo  = $memberLogic->getInfo($uid);

                            if(!empty($memberInfo)) {
                                $replace[] = $memberInfo['nickname'];
                            }
                        }
                        //获取时间
                        if($param[1] == 'time_format') {
                            $replace[] = date('Y-m-d H:i:s');
                        }
                        //获取用户名
                        if($param[1] == 'get_username') {
                            $userLogic = Loader::model('User', 'logic');
                            $userInfo  = $userLogic->getInfo($recordId);
                            if(!empty($userInfo)) {
                                $replace[] = $userInfo['user_name'];
                            }
                        }
                    } else {
                        $replace[] = $log[$param[0]];
                    }
                }

                $data['remark'] = str_replace($match[0], $replace, $actionInfo['log']);
            } else {
                $data['remark'] = $actionInfo['log'];
            }
        } else {
            //未定义日志规则，记录操作url
            $data['remark'] = '操作url：' . $_SERVER['REQUEST_URI'];
        }
        $data['record_detail'] = $recordDetail;
        Loader::model('ActionLog')->insert($data);

        //插入日志文件
        $this->writeActionLog($data);

        if(!empty($actionInfo['rule'])) {
            //解析行为
            $rules = $this->parseAction($action, $uid);

            //执行行为
            $this->executeAction($rules, $actionInfo['id'], $uid);
        }
    }

    /**
     * 解析行为规则
     * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
     * 规则字段解释：table->要操作的数据表，不需要加表前缀；
     *              field->要操作的字段；
     *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
     *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
     *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
     *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
     * 单个行为后可加 ； 连接其他规则
     * @param string $action 行为id或者name
     * @param int $self 替换规则里的变量为执行用户的id
     * @return boolean|array: false解析出错 ， 成功返回规则数组
     * @author huajie <banhuajie@163.com>
     */
    private function parseAction($action = null, $self) {
        if(empty($action)) {
            return false;
        }

        //参数支持id或者name
        if(is_numeric($action)) {
            $map = array('id' => $action);
        } else {
            $map = array('name' => $action);
        }

        //查询行为信息
        $info = Loader::model('Action')->where($map)->find();
        if(!$info || $info['status'] != 1) {
            return false;
        }

        //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
        $rules  = $info['rule'];
        $rules  = str_replace('{$self}', $self, $rules);
        $rules  = explode(';', $rules);
        $return = array();
        foreach($rules as $key => &$rule) {
            $rule = explode('|', $rule);
            foreach($rule as $k => $fields) {
                $field = empty($fields) ? array() : explode(':', $fields);
                if(!empty($field)) {
                    $return[$key][$field[0]] = $field[1];
                }
            }

            if(isset($return[$key])) {
                //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
                if(!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])) {
                    unset($return[$key]['cycle'], $return[$key]['max']);
                }
            }
        }

        return $return;
    }

    /**
     * 执行行为
     * @param array $rules 解析后的规则数组
     * @param int $actionId 行为id
     * @param array $uid 执行的用户id
     * @return boolean false 失败 ， true 成功
     * @author huajie <banhuajie@163.com>
     */
    private function executeAction($rules = false, $actionId = null, $uid = null) {
        if(!$rules || empty($actionId) || empty($uid)) {
            return false;
        }

        $return = true;
        foreach($rules as $rule) {

            //检查执行周期
            $map                = array(
                'action_id' => $actionId,
                'user_id'   => $actionId
            );
            $map['create_time'] = array(
                'gt',
                time() - intval($rule['cycle']) * 3600
            );
            $exec_count         = Loader::model('ActionLog')->where($map)->count();
            if($exec_count > $rule['max']) {
                continue;
            }

            //执行数据库操作
            $Model = Loader::model(ucfirst($rule['table']));
            $field = $rule['field'];
            $res   = $Model->where($rule['condition'])->setField($field, array(
                'exp',
                $rule['rule']
            ));

            if(!$res) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * 根据数据字段返回对应的中文
     * @return array
     */
    public function getFieldsByName($model, $fieldArray) {

        $result = [];

        if(is_array($fieldArray)) {
            foreach($fieldArray as $key => $val) {
                if(class_exists('app\admin\model\\'.$model)) {
                    $chineseKey          = Loader::model($model)->_fields[$key];
                    $result[$chineseKey] = $val;
                }
            }
        }

        return $result;
    }


    /**
     * 获取行为日志数据
     * @param $id 主键ID
     * @param $change_data 变更的数据
     * @param string $model
     * @return array
     * @author jesse.lin.989@gmail.com
     */
    public function getActionData($id,$changeData = [],$model = ''){

        if(empty($id)) {
            return [];
        }

        if(empty($model)){
            $request = Request::instance();
            $model   = $request->controller();
        }

        $model = Loader::model($model);

        $actionData  = array();

        $field_key = $model->getPk();
        $condition[$field_key] = $id;
        $actionData = $model->where($condition)->find()->toArray();

        $newData = array();
        if(!empty($changeData)&&is_array($changeData)){
            foreach ($actionData as $key=>$val){
                if(isset($changeData[$key])&&$val!=$changeData[$key]){
                    $newData[$key] = $changeData[$key];
                }
            }
        }

        if(!empty($newData)){
            $actionData['_change_'] = json_encode($newData);
        }

        return $actionData;
    }

    /**
     * 写行为日志给ELK
     * @return bool
     */
    public function  writeActionLog($data) {

        $filename = Config::get('action_log_path').'action_log.log';

        if(!empty($data)) {

            $data['record_detail'] = json_decode($data['record_detail'], true);
            if(isset($data['record_detail']['_change_'])) {
                $data['record_detail']['_change_'] = json_decode($data['record_detail']['_change_'], true);
            }
            //重新封装数据
            $result = array(
                'time'          => date('Y-m-d H:i:s',$data['create_time']),
                'ip'            => long2ip($data['action_ip']),
                'user_id'       => $data['user_id'],
                'model'         => $data['model'],
                'data'          => $data['record_detail'],
            );

            //记录日记文件给ELK查询
            @file_put_contents($filename, json_encode($result,JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        return true;
    }
}