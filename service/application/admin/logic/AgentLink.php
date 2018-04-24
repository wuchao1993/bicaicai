<?php
namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;

class AgentLink extends Model {
    public $errorcode = EC_AD_SUCCESS;
    /**
     * 获取代理推广码列表
     * @param $params
     * @return array
     */
    public function getList($params) {
        $model = Loader::model ( 'AgentLink' );

        if (isset ( $params ['agl_code'] )) {
            $condition ['agl_code'] = $params ['agl_code'];
        }

        $condition['agl_status'] = ['neq',Config::get("qrcode.agl_status")['delete']];

        if (isset ( $params ['user_name'] )) {
            $userModel = Loader::model ( 'User' );
            $userId = $userModel->where (['user_name'=>$params ['user_name']] )->value ('user_id');
            $condition ['user_id'] = $userId;
        }

        // 获取总条数
        $count = $model->where ( $condition )->count ();

        $list = $model->where ( $condition )->order ( 'agl_id desc' )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();
        
        //批量获取用户名称
        $userIds = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where(['user_id'=>['IN', $userIds]])->column('user_name,ul_id', 'user_id');

        if(!empty ($list)) {
            foreach ($list as $key=>$vo){
                $list[$key]['user_name'] = $userList[$vo['user_id']]['user_name'];
            }
        }

        $returnArr = array (
            'totalCount' => $count,
            'list' => $list
        );

        return $returnArr;
    }

    //编辑代理推广码链接
    public function editAgentLink($params)
    {
        //空为永久
        if ($params ['agl_endtime'] == '') {
            $params ['agl_endtime'] = null;
        }
        $condition = [ ];
        $condition ['agl_id'] = $params ['agl_id'];

        $data ['agl_status'] = $params ['agl_status'];
        $data ['agl_endtime'] = $params ['agl_endtime'];

        $actionData = Loader::model('General','logic')->getActionData($params ['agl_id'],$data,'AgentLink');

        $res = Loader::model ( 'AgentLink' )->save ( $data, $condition );
        if($res !== false){
            Loader::model('General', 'logic')->actionLog('update_agent_link', 'AgentLink' ,$condition ['agl_id'] , MEMBER_ID, json_encode($actionData));
            return true;
        }
        $this->errorcode = EC_AD_UPDATE_ERROR;
        return false;
    }

}