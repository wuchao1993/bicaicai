<?php
/**
 * 代理推广码控制器
 * @author jesse.lin.989@gmail.com
 */
namespace app\admin\controller;
use think\Request;
use think\Loader;
use think\Config;

class AgentLink
{

    /**
     * 获取代理推广码列表
     *
     * @param Request $request
     * @return array
     */
    public function getAgentLinkList(Request $request)
    {
        $params ['page'] = $request->param ( 'page',1 );
        $params ['num'] = $request->param ( 'num',100 );

        if ($request->param ( 'code' ) != '') {
            $params ['agl_code'] = $request->param ( 'code' );
        }
        if ($request->param ( 'username' ) != '') {
            $params ['user_name'] = $request->param ( 'username' );
        }

        $logic = Loader::model ( 'AgentLink', 'logic' );
        $data = $logic->getList ( $params );

        foreach ( $data ['list'] as &$info ) {
            $info = $this->_packInfo( $info );
        }

        return [
            'errorcode' => $logic->errorcode,
            'message' => Config::get ( 'errorcode' ) [$logic->errorcode],
            'data' => output_format ( $data )
        ];
    }

    //编辑推广码链接
    public function editAgentLink(Request $request)
    {
        $params ['agl_id'] = $request->param ( 'id', 0 );
        $params ['agl_status'] = $request->param ( 'status', 2 );
        $params ['agl_endtime'] = $request->param ( 'endtime', null );

        $logic = Loader::model ( 'AgentLink', 'logic' );
        $data = $logic->editAgentLink ( $params );

        return [
            'errorcode' => $logic->errorcode,
            'message' => Config::get ( 'errorcode' ) [$logic->errorcode],
            'data' => output_format ( $data )
        ];

    }

    private function _packInfo($info) {
        //0过期
        $expired = 0;
        if ( $info ['agl_endtime'] == null || strtotime($info ['agl_endtime']) > time() ) {
            $expired = 1;
        }

        return [
            'id'            => $info ['agl_id'],
            'user_id'       => $info ['user_id'],
            'code'          => $info ['agl_code'],
            'user_type'     => $info ['agl_user_type'],
            'use_count'     => $info ['agl_use_count'],
            'endtime'       => $info ['agl_endtime'],
            'createtime'    => $info ['agl_createtime'],
            'status'        => $info ['agl_status'],
            'user_name'     => $info ['user_name'],
            'expired'       => $expired,
        ];
    }


}