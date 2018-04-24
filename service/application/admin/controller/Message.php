<?php

/**
 * 公告控制器
 * @author paulli
 */
namespace app\admin\controller;

use think\Request;
use think\Loader;

class Message {
    /**
     * 获取所有会员站内信列表
     *
     */
    public function getList(Request $request) {
        $params['userName'] = $request->post('userName');
        $params['page']     = $request->post('page', 0);
        $params['num']      = $request->post('num', 20);
        $messageLogic       = Loader::model('Message', 'logic');
        $messageData        = $messageLogic->getList($params);
        return return_result($messageLogic->errorcode, output_format($messageData));
    }

    /**
     * 显示站内信详情
     */

    public function getMessage(Request $request) {
        $messageId    = $request->post('messageId');
        $messageLogic = Loader::model('Message', 'logic');
        $messageData  = $messageLogic->getMessage($messageId);
        return return_result($messageLogic->errorcode, output_format($messageData));
    }

    /**
     * 编辑站内信功能
     */
    public function sendMessage(Request $request) {
        $params['userIds'] = $request->post('userIds');
        $params['title']   = $request->post('title');
        $params['content'] = $request->post('content');
        $messageLogic      = Loader::model('Message', 'logic');
        $messageData       = $messageLogic->sendMessage($params);
        return return_result($messageLogic->errorcode, output_format($messageData));
    }

    /**
     * 批量删除站内信信息
     * @param Request $request
     */
    public function deleteMessage(Request $request) {
        $messageIds   = $request->post('messageId');
        $messageLogic = Loader::model('Message', 'logic');
        $messageData  = $messageLogic->deleteMessage($messageIds);
        return return_result($messageLogic->errorcode, output_format($messageData));
    }

}
