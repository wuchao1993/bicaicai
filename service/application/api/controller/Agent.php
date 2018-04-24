<?php

namespace app\api\controller;

use think\Hook;
use think\Loader;
use think\Request;
use think\Config;

class Agent {

    /**
     * 申请代理接口
     * @param Request $request
     * @return array
     */
    public function applyAgent(Request $request) {
        $params     = $request->post();
        $agentLogic = Loader::model('Agent', 'logic');
        $userInfo   = $agentLogic->applyAgent($params);
        $token      = $userInfo['token'];
        unset($userInfo['token']);

        return json([
            'errorcode' => $agentLogic->errorcode,
            'message'   => $agentLogic->message ?: Config::get('errorcode')[$agentLogic->errorcode],
            'data'      => output_format($userInfo),
        ], 200, ['Auth-Token'    => $token,
                 'Auth-Identity' => $GLOBALS['auth_identity']
        ]);
    }

    /**
     *  注册下级代理接口
     * @param Request $request
     * @return array
     */
    public function registerSubordinateAgent(Request $request) {
        $params     = $request->post();
        $agentLogic = Loader::model('Agent', 'logic');
        $result     = $agentLogic->registerSubordinate($params);

        return [
            'errorcode' => $agentLogic->errorcode,
            'message'   => $agentLogic->message ?: Config::get('errorcode')[$agentLogic->errorcode],
            'data'      => output_format($result),
        ];
    }

    /**
     * 获取代理说明配置信息
     * @param Request $request
     * @return array
     */
    public function getIntro(Request $request) {
        $agentLogic = Loader::model('Agent', 'logic');
        $result     = $agentLogic->getIntro();

        return send_response($result, $agentLogic->errorcode);
    }

    /**
     * 代理团队管理接口
     * 下级列表获取
     * 下级用户查询
     * @param Request $request
     * @return array
     */
    public function getTeamList(Request $request) {
        Hook::listen('auth_check');
        $params     = $request->post();
        $agentLogic = Loader::model('Agent', 'logic');
        $result     = $agentLogic->getTeamList($params);

        return send_response($result, $agentLogic->errorcode);
    }

    public function getSubordinateUserInfo(Request $request) {
        Hook::listen('auth_check');
        $params     = $request->post();
        $agentLogic = Loader::model('Agent', 'logic');
        $result     = $agentLogic->getSubordinateUserInfo($params);

        return send_response($result, $agentLogic->errorcode);
    }

    /**
     * 下级开户
     * @param Request $request
     * @return array
     */
    public function createSubordinate(Request $request) {
        Hook::listen('auth_check');
        $params     = $request->post();
        $agentLogic = Loader::model('Agent', 'logic');

        $result = $agentLogic->createSubordinate($params);

        return send_response($result, $agentLogic->errorcode);
    }

    /**
     * 获取代理基本信息
     * @param Request $request
     * @return array
     */
    public function getAgentInfo(Request $request) {
        Hook::listen('auth_check');
        $agentLogic = Loader::model('Agent', 'logic');
        $result     = $agentLogic->getAgentInfo($request->post());

        return send_response($result, $agentLogic->errorcode);
    }

    public function getAgentStatistics(Request $request) {
        Hook::listen('auth_check');
        $agentLogic = Loader::model('Agent', 'logic');
        $result     = $agentLogic->getAgentStatistics($request->post());

        return send_response($result, $agentLogic->errorcode);
    }

    public function getAgentDayStatistics(Request $request) {
        Hook::listen('auth_check');
        $agentLogic = Loader::model('Agent', 'logic');
        $result     = $agentLogic->getAgentDayStatistics($request->post());

        return send_response($result, $agentLogic->errorcode);
    }

    /***
     * /***
     * 新增邀请码
     */
    public function generateInvitationCode(Request $request) {
        Hook::listen('auth_check');
        $agentLinkLogic = Loader::model("AgentLink", "logic");
        $param          = $request->post();
        $agentLinkData  = $agentLinkLogic->generateCode($param);

        return send_response($agentLinkData, $agentLinkLogic->errorcode);
    }

    /***
     * 编辑邀请码
     */
    public function editInvitationCode(Request $request) {
        Hook::listen('auth_check');
        $agentLinkLogic = Loader::model("AgentLink", "logic");
        $param          = $request->post();
        $agentLinkLogic->editQrCode($param);

        return send_response('', $agentLinkLogic->errorcode);
    }

    /***
     * 删除邀请码
     */
    public function deleteInvitationCode(Request $request) {
        $agentLinkLogic = Loader::model("AgentLink", "logic");
        $agentLinkLogic->deleteQrCode($request->post());

        return send_response('', $agentLinkLogic->errorcode);
    }

    /***
     * 邀请码列表
     */
    public function getInvitationCodeList() {
        Hook::listen('auth_check');
        $agentLinkLogic = Loader::model("AgentLink", "logic");
        $agentLinkList  = $agentLinkLogic->getQrcodeList();

        return send_response($agentLinkList, $agentLinkLogic->errorcode);
    }
}