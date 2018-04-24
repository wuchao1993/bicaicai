<?php
/**
 * 行为日志相关业务逻辑
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;

class ActionLog extends Model {

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取行为日志列表
     * @param $params
     * @return array
     */
    public function getList($params) {

        $returnArr = array(
            'totalCount' => 0,
            'list'       => []
        );

        $condition = [];

        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['al.create_time'] = [
                [
                    'EGT',
                    strtotime($params ['start_date']),
                ],
                [
                    'ELT',
                    strtotime($params ['end_date']),
                ],
            ];
        }

        if (isset ($params ['action_name'])) {
            $condition ['a.title'] = $params ['action_name'];
        }

        if (isset ($params ['nickname'])) {
            $condition ['m.nickname'] = ['LIKE', '%' . $params ['nickname'] . '%'];
        }

        if (isset ($params ['action_ip'])) {
            $condition ['al.action_ip'] = ip2long($params ['action_ip']);
        }

        if(isset ($params ['user_name'])) {
            $userId = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            if(!empty($userId)) {
                $condition['record_id'] = $userId;
            }else {
                return $returnArr;
            }
        }
        //过滤账号admin及kaifa行为日志
        $memberId = Loader::model('Member')->getUserIdByUsername('kaifa');
        !empty($memberId)?$memberIds = [1,$memberId]:$memberIds = [1];
        $condition ['m.uid'] = ['NOT IN', $memberIds];
        $actionLogModel = Loader::model('ActionLog');

        //获取总条数
        $count = $actionLogModel->alias('al')->join('Action a', 'al.action_id=a.id', 'LEFT')->join('Member m', 'm.uid=al.user_id', 'LEFT')->where($condition)->count();

        $list = $actionLogModel->alias('al')->join('Action a', 'al.action_id=a.id', 'LEFT')->join('Member m', 'm.uid=al.user_id', 'LEFT')->field('a.title as actionName,m.nickname,al.id,al.action_ip,al.remark,al.status,al.create_time')->where($condition)->order('al.id desc')->limit($params['num'])->page($params['page'])->select();

        if(!empty($list)) {
            foreach($list as &$val) {
                $val['action_ip'] = long2ip($val['action_ip']);
            }
        }

        $returnArr = array(
            'totalCount' => $count,
            'list'       => $list
        );

        return $returnArr;
    }

    /**
     * 获取行为日志信息
     * @param $id
     * @return array
     */
    public function getInfo($id) {
        $condition = ['al.id' => $id];
        $info      = Loader::model('ActionLog')->alias('al')->join('Action a', 'al.action_id=a.id', 'LEFT')->join('Member m', 'm.uid=al.user_id', 'LEFT')->field('a.title as actionName,m.nickname,al.id,al.action_ip,al.model,al.record_id,al.remark,al.record_detail,al.create_time')->where($condition)->find();

        if(!empty($info)) {
            $info['action_ip'] = long2ip($info['action_ip']);
            $record_detail = json_decode($info['record_detail'], true);

            //支持显示更新内容
            if(isset($record_detail['_change_'])){
                $change_detail = json_decode($record_detail['_change_'],true);
                unset($record_detail['_change_']);
            }

            //支持二维数组，多行数据
            if(!is_multi_array($record_detail)){
                $record_detail = [$record_detail];
            }
            foreach ($record_detail as $key=>$detail){
                $record = Loader::model('General', 'logic')->getFieldsByName($info['model'], $detail);
                if($key>0){
                    foreach ($record as $k=>$v){
                        $record[$k."*".$key] = $v;
                        unset($record[$k]);
                    }
                }
                $info['record_detail'] = is_array($info['record_detail'])?array_merge($info['record_detail'],$record):$record;
            }

            if($change_detail){
                $change = Loader::model('General', 'logic')->getFieldsByName($info['model'], $change_detail);
                $change_temp = [];
                foreach ($change as $key=>$val){
                    $change_temp["new*{$key}"] = $val;
                }
                unset($change);
                $info['record_detail'] = array_merge($info['record_detail'],$change_temp);
            }
        }

        return $info;
    }

    /**
     * 删除
     * @param $params
     * @return array
     */
    public function del($params) {

        foreach($params['id'] as $val) {
            $ret = Loader::model('ActionLog')->where(['id' => $val])->delete();

            if($ret == false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 清空
     *
     * @return array
     */
    public function clear() {

        return Loader::model('ActionLog')->where('1=1')->delete();

    }
}