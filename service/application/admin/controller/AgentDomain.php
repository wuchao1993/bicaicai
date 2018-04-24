<?php

/**
 * 代理域名控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class AgentDomain {
    
    /**
     * 获取代理域名列表
     * 
     * @param Request $request            
     * @return array
     */
    public function getAgentDomainList(Request $request)
    {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );

        if ($request->param ( 'username' ) != '') {
            $params ['user_name'] = $request->param ( 'username' );
        }

        if ($request->param ( 'domainName' ) != '') {
            $params ['agd_domain'] = $request->param ( 'domainName' );
        }
        
        $agentDomainLogic = Loader::model ( 'AgentDomain', 'logic' );
        $agentDomainList = $agentDomainLogic->getList ( $params );
        
        foreach ( $agentDomainList ['list'] as &$info ) {
            $info = $this->_packAgentDomainList( $info );
        }
        
        return [ 
                'errorcode' => $agentDomainLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$agentDomainLogic->errorcode],
                'data' => output_format ( $agentDomainList )
        ];
    }
    
    /**
     * 获取该代理域名下注册的用户
     * 
     * @param Request $request            
     * @return array
     */
    public function getUsersByAgentDomain(Request $request)
    {

        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',10 );
        $params ['agd_domain'] = $request->param ( 'domainName','' );
        $DomainUsersLogic = Loader::model ( 'AgentDomain', 'logic' );
        $agentDomainList = $DomainUsersLogic->getUsersByDomain ( $params );
        
        foreach ( $agentDomainList ['list'] as &$info ) {
            $info = $this->_packAgentDomainUsers( $info );
        }

        return [ 
                'errorcode' => $DomainUsersLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$DomainUsersLogic->errorcode],
                'data' => output_format ( $agentDomainList )
        ];

    }
    
    /**
     * 获取代理域名信息
     * 
     * @param Request $request            
     * @return array
     */
    public function getAgentDomainInfo(Request $request) 
    {
        $id = $request->param ( 'id' );
        
        $agentDomainLogic = Loader::model ( 'agentDomain', 'logic' );
        $agentDomainInfo = $agentDomainLogic->getInfo ( $id );
        $agentDomainInfo = $this->_packAgentDomainInfo ( $agentDomainInfo );
        
        return [ 
                'errorcode' => $agentDomainLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$agentDomainLogic->errorcode],
                'data' => output_format ( $agentDomainInfo ) 
        ];
    }
    
    /**
     * 新增代理域名
     * 
     * @param Request $request            
     * @return array
     */
    public function addAgentDomain(Request $request) 
    {
        $params ['user_id'] = $request->param ( 'uid' );
        $params ['agd_domain'] = $request->param ( 'domainName' );
        $params ['agd_status'] = $request->param ( 'status' );
        $params ['agd_remark'] = $request->param ( 'remark', '');
        $params ['agd_user_type'] = $request->param ( 'userType' );

        $agentDomainLogic = Loader::model ( 'agentDomain', 'logic' );
        $agentDomainInfo = $agentDomainLogic->add ( $params );
        
        return [ 
                'errorcode' => $agentDomainLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$agentDomainLogic->errorcode],
                'data' => output_format ( $agentDomainInfo ) 
        ];
    }
    
    /**
     * 编辑代理域名
     * 
     * @param Request $request            
     * @return array
     */
    public function editAgentDomain(Request $request) 
    {
        $params ['id'] = $request->param ( 'id' );
        $params ['agd_domain'] = $request->param ( 'domainName' );
        $params ['agd_status'] = $request->param ( 'status' );
        $params ['agd_remark'] = $request->param ( 'remark' );
        $params ['agd_user_type'] = $request->param ( 'userType' );

        $agentDomainLogic = Loader::model ( 'agentDomain', 'logic' );
        $result = $agentDomainLogic->edit ( $params );
        
        return [ 
                'errorcode' => $agentDomainLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$agentDomainLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 删除代理域名
     * 
     * @param Request $request            
     * @return array
     */
    public function delAgentDomain(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        
        $agentDomainLogic = Loader::model ( 'agentDomain', 'logic' );
        $result = $agentDomainLogic->del ( $params );
        
        return [ 
                'errorcode' => $agentDomainLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$agentDomainLogic->errorcode],
                'data' => $result 
        ];
    }
    
    /**
     * 修改代理域名状态
     * 
     * @param Request $request            
     * @return array
     */
    public function changeAgentDomainStatus(Request $request) {
        $params ['id'] = $request->param ( 'id' );
        $params ['status'] = $request->param ( 'status' );
        
        $agentDomainLogic = Loader::model ( 'agentDomain', 'logic' );
        $result = $agentDomainLogic->changeStatus ( $params );
        
        return [ 
                'errorcode' => $agentDomainLogic->errorcode,
                'message' => Config::get ( 'errorcode' ) [$agentDomainLogic->errorcode],
                'data' => $result 
        ];
    }

    private function _packAgentDomainList($info) {
        
        return [
                'id'            => $info ['agd_id'],
                'uid'           => $info ['user_id'],
                'username'      => $info ['user_name'],
                'domainName'    => $info ['agd_domain'],
                'useCount'      => $info ['agd_use_count'],
                'createtime'    => $info ['agd_createtime'],
                'status'        => $info ['agd_status'],
                'remark'        => $info ['agd_remark'],
                'userType'      => $info ['agd_user_type'],
        ];
    }
    
    private function _packAgentDomainInfo($info) {
        return [
            'id'            => $info ['agd_id'],
            'uid'           => $info ['user_id'],
            'username'      => $info ['user_name'],
            'domainName'    => $info ['agd_domain'],
            'useCount'      => $info ['agd_use_count'],
            'createtime'    => $info ['agd_createtime'],
            'status'        => $info ['agd_status'],
            'remark'        => $info ['agd_remark'],
            'userType'      => $info ['agd_user_type'],
        ];
    }

    private function _packAgentDomainUsers($info) {
        
        return [
                'uid'           => $info ['user_id'],
                'username'      => $info ['user_name'],
                'regTerminal'      => $info ['reg_terminal'],
                'regWay'      => $info ['reg_way'],
                'domainName'    => $info ['agd_domain'],
                'status'    => $info ['user_status'],
                'createtime'    => $info ['user_createtime'],
        ];
    }
    
}
