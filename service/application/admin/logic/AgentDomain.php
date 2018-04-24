<?php

/**
 * 代理域名相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;

use think\Loader;
use think\Model;
use think\Config;

class AgentDomain extends Model {
    
    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;
    
    /**
     * 获取代理域名列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $agentDomainModel = Loader::model ( 'AgentDomain' );
        
        $condition = [ ];
        if (isset ( $params ['user_name'] )) {
            $condition ['u.user_name'] = ['LIKE',$params['user_name'].'%'];
        }

        if (isset ( $params ['agd_domain'] )) {
            $condition ['ad.agd_domain'] = ['LIKE','%'.$params['agd_domain'].'%'];
        }

        $condition['agd_status'] = ['neq',Config::get("status.agent_domain_status")['del']];
        
        // 获取总条数
        $count = $agentDomainModel->alias('ad')->join('User u', 'u.user_id=ad.user_id', 'LEFT')->where ( $condition )->count ();
        
        $list = $agentDomainModel->alias('ad')->join('User u', 'u.user_id=ad.user_id', 'LEFT')->field('ad.*,u.user_name')->where ( $condition )->order ( 'agd_id desc' )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();
        
        $returnArr = array (
                'totalCount' => $count,
                'list' => $list 
        );
        
        return $returnArr;
    }

    /**
     * 获取代理域名下的用户列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getUsersByDomain($params) {
        $agentDomainModel = Loader::model ( 'AgentDomain' );
        
        $condition = [ ];
        if (isset ( $params ['agd_domain'] )) {
            $condition ['ad.agd_domain'] = $params['agd_domain'];
            $condition ['agd_status']    = Config::get("status.agent_domain_status")['enable'];
        }
        
        // 获取总条数
        $count = $agentDomainModel->alias('ad')->join('User u', 'u.user_reg_url = ad.agd_id and u.user_pid = ad.user_id')->where ( $condition )->count ();
        
        $list = $agentDomainModel->alias('ad')->join('User u', 'u.user_reg_url = ad.agd_id and u.user_pid = ad.user_id')->field('ad.agd_domain,u.user_id, u.user_name, u.reg_terminal, u.reg_way, u.user_status, u.user_createtime')->where ( $condition )->order ( 'u.user_id desc' )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();

        $returnArr = array (
                'totalCount' => $count,
                'list' => $list 
        );
        
        return $returnArr;
    }
    
    /**
     * 获取代理域名信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($id) {
        $condition = [ 
                'agd_id' => $id
        ];
        $info = Loader::model ( 'AgentDomain' )->where ( $condition )->find ()->toArray ();
        
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

        $condition = [];
        $condition['agd_domain'] = $params['agd_domain'];
        $condition['agd_status'] = Config::get("status.agent_domain_status")['enable'];

        $info = Loader::model('AgentDomain')->where($condition)->find();
        if (!empty($info)) {
            $this->errorcode = EC_AD_ADD_AGENT_DOMAIN_EXISTING;
            return false;
        }

        // 入库
        $data ['user_id'] = $params ['user_id'];
        $data ['agd_domain'] = $params ['agd_domain'];
        $data ['agd_status'] = $params ['agd_status'];
        $data ['agd_remark'] = $params ['agd_remark'];
        $data ['agd_user_type'] = $params ['agd_user_type'];

        $agentDomainModel = Loader::model ( 'AgentDomain' );
        $ret = $agentDomainModel->save ( $data );
        if ($ret) {

            //添加默认返点
            Loader::model ("AgentDomainRebate","logic")->addRebate($agentDomainModel->agd_id,$params ['user_id']);

            $agentDomainInfo = [ 
                    'id' => $agentDomainModel->agd_id
            ];
            return $agentDomainInfo;
        }
        $this->errorcode = EC_AD_ADD_AGENT_DOMAIN_ERROR;
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

        $condition = [];
        $condition['agd_domain'] = $params['agd_domain'];
        $condition['agd_status'] = Config::get("status.agent_domain_status")['enable'];

        $info = Loader::model('AgentDomain')->where($condition)->find();
        if (!empty($info) && $info['agd_id'] != $params['id']) {
            $this->errorcode = EC_AD_ADD_AGENT_DOMAIN_EXISTING;
            return false;
        }

        // 修改信息
        $data ['agd_domain'] = $params ['agd_domain'];
        $data ['agd_status'] = $params ['agd_status'];
        $data ['agd_remark'] = $params ['agd_remark'];
        $data ['agd_user_type'] = $params ['agd_user_type'];
        Loader::model ( 'AgentDomain' )->save ( $data, [
                'agd_id' => $params ['id']
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

        $where = [];
        $where['agd_id'] = $params ['id'];

        $data = [];
        $data['agd_status'] = Config::get("status.agent_domain_status")['del'];

        $ret = Loader::model ( 'AgentDomain' )->save($data,$where);
        
        return $ret;
    }

    /**
     * 修改状态
     *
     * @param
     *            $params
     * @return array
     */
    public function changeStatus($params) {
        $updateData ['agd_status'] = $params ['status'];
        Loader::model ( 'AgentDomain' )->save ( $updateData, [ 
                'agd_id' => $params ['id']
        ] );
        
        return true;
    }
}