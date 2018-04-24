<?php
/**
 * 站内信逻辑
 * @createTime 2017/4/3 16:14
 */

namespace app\common\logic;

use think\Config;
use think\Loader;
use think\Model;

class Message extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 后台站内信列表页
     * @param $params
     * @return array
     */
    public function getList($params) {
        if($params['userName']){
            $userIds = Loader::model('User')->where(['user_name' => $params['userName']])->column('user_id');

            if(!$userIds[0]) {
                return [];
            }
            $field = [
                'message_content_id' => 'messageId',
            ];
            $order = 'message_create_time desc';
            $where = [
                'message_to_user_id'=> $userIds[0],
                'message_status'    => Config::get('status.message_status')['enable'],
            ];
            $messageData = Loader::model('message')
                ->field($field)
                ->where($where)
                ->order($order)
                ->page($params['page'])
                ->limit($params['num'])
                ->select();
            $totalCount = Loader::model('message')->where($where)->count();
            foreach ($messageData as $key => &$value) {
                $mcData = Loader::model('messageContent')->where(['mc_id' => $value['messageId'],
                                                                  'mc_status' => Config::get('status.message_status')['enable'],
                                                         ])->find();
                $value['title']    = $mcData['mc_title'];
                $value['content']  = $mcData['mc_content'];
                $value['number']   = $mcData['mc_number'];
                $value['userName'] = $params['userName'];
            }
        } else {
            $field = [
                'mc_id'      => 'messageId',
                'mc_title'   => 'title',
                'mc_content' => 'content',
                'mc_number'  => 'number',
            ];
            $where = [
                'mc_status' => Config::get('status.message_status')['enable'],
            ];
            $order = 'mc_create_time desc';
            $messageData = Loader::model('MessageContent')->field($field)
                ->order($order)
                ->where($where)
                ->page($params['page'])
                ->limit($params['num'])
                ->select();
            $totalCount = Loader::model('MessageContent')->where($where)->count();
            foreach ($messageData as &$value) {
                    $userId = Loader::model('message')
                        ->where(['message_content_id' => $value['messageId'],
                                 'message_status' => Config::get('status.message_status')['enable']
                                ])
                        ->order('message_create_time desc')
                        ->field('message_to_user_id')
                        ->limit(0,1)->select();

                    $userName = Loader::model('user')->where(['user_id' => $userId[0]['message_to_user_id']])->column('user_name');
                    $value['userName'] = $userName[0];
            }
        }
        return ['list' => $messageData, 'totalCount' => $totalCount];
    }

    /**
     * 显示站内信详情
     * @param $messageId
     * @return mixed
     */
    public function getMessage($messageId) {
            $where['mc_id'] = $messageId;
            $field = [
                'mc_id'      => 'messageId',
                'mc_title'   => 'title',
                'mc_content' => 'content',
            ];
            $messageData = Loader::model('messageContent')->field($field)->where($where)->find();
            $userIds = Loader::model('message')->where(['message_content_id' => $messageId,
                                                        'message_status'=>Config::get('status.message_status')['enable']])
                                               ->column('message_to_user_id');
            $userNames = Loader::model('User')->where(['user_id' => ['in',$userIds]])->column('user_name');
            $messageData['userNames'] = implode(',', $userNames);
            return $messageData;
    }

    /**
     * 编辑站内信功能
     * @param $params
     * @return bool
     */
    public function sendMessage($params) {
        $messageContent['mc_title']       = $params['title'];
        $messageContent['mc_content']     = $params['content'];
        $messageContent['mc_number']      = $params['number'];
        $messageContent['mc_create_time'] = date('Y-m-d H:i:s');
        $messageContent['mc_modify_time'] = date('Y-m-d H:i:s');
        $userIds = explode(',', $params['userIds']);
        $messageContent['mc_number']      = count($userIds);
        $messageId = Loader::model('messageContent')->insertGetId($messageContent);
        if($messageId) {
            foreach ($userIds as $value) {
                $message[] = [
                    'message_to_user_id'  => $value,
                    'message_create_time' => date('Y-m-d H:i:s'),
                    'message_modify_time' => date('Y-m-d H:i:s'),
                    'message_content_id'  => $messageId,
                    'message_user_id'     => MEMBER_ID,
                ];
            }
            Loader::model('message')->insertAll($message);
            return true;
        } else {
            $this->errorcode = EC_MESSAGE_INSERT_FAILED;
            return false;
        }
    }

    /**
     * 站内信列表
     * @array params
     * @return array
     */

    public function getMessageList($params) {

        $where = [
            'message_to_user_id' => USER_ID,
            'message_status'     => Config::get('status.message_status')['enable'],
        ];
        $order = 'message_create_time desc';
        $field = [
            'message_content_id'  => 'messageId',
            'message_is_read'     => 'isRead',
            'message_create_time' => 'createTime',
        ];

        //未读取站内信条数
        $notReadNum = $this->getNotReadNum();
        $messageData = Loader::model('Message')->field($field)
                                              ->where($where)
                                              ->order($order)
                                              ->page($params['page'])
                                              ->limit(10)
                                              ->select();
        $totalPage = Loader::model('Message')->where($where)->count();
        $totalPage = ceil($totalPage/10);
        $fieldContent = [
            'mc_title'   => 'title',
            'mc_content' => 'content',
        ];
        foreach ($messageData as &$value) {
             $messageContent   = Loader::model('messageContent')->where([
                                                                'mc_id' => $value['messageId'],
                                                                'mc_status' => Config::get('status.message_status')['enable']])
                                                                ->field($fieldContent)
                                                                ->find();
             $value['title']   = $messageContent['title'];
             $value['content'] = $messageContent['content'];
             $value['isRead']  = Config::get('status.message_read_status')[$value['isRead']];
        }
        return ['result' => $messageData, 'notReadNum' => $notReadNum, 'totalPage' => $totalPage];
    }

    /**
     * 站内信详情信息
     * @param $messageId
     * @return mixed
     */
    public function getMessageInfo($messageId) {

        $field = [
            'mc_id'          => 'messageId',
            'mc_title'       => 'title',
            'mc_content'     => 'content',
            'mc_create_time' => 'createTime',
        ];
        $messageData = Loader::model('messageContent')->field($field)
                                                      ->where(['mc_id' => $messageId])
                                                      ->find();
        $where = [
            'message_to_user_id' => USER_ID,
            'message_content_id' => $messageId,
        ];
        $messRead = Loader::model('Message')->where($where)
                                            ->column('message_is_read');
        //修改站内信读取状态
        if($messRead[0] == Config::get('status.message_is_read')['no']){
            $update['message_is_read']     = Config::get('status.message_is_read')['yes'];
            $update['message_modify_time'] = date('Y-m-d H:i:s');
            Loader::model('Message')->where($where)->update($update);
        }
        return $messageData;
    }

    /**
     * 前台删除站内信信息
     * @param $messageId
     * @return mixed
     */
    public function deleteMessageInfo($messageId) {
        $where['message_content_id']       = $messageId;
        $where['message_to_user_id']       = USER_ID;
        $updateData['message_modify_time'] = date('Y-m-d H:i:s');
        $updateData['message_status']      = Config::get('status.message_status')['disable'];
        return Loader::model('Message')->where($where)->update($updateData);
    }

    /**
     * 获取站内信未读条数
     * @return mixed
     */
    public function getNotReadNum() {
        $where['message_to_user_id'] = USER_ID;
        $where['message_is_read']    = Config::get('status.message_is_read')['no'];
        $where['message_status']     = Config::get('status.message_status')['enable'];
        $notReadNum = Loader::model('message')->where($where)->count();
        return $notReadNum;
    }


    /**
     * 批量删除站内信息
     * @param $messageIds
     * @return bool
     */
    public function deleteMessage($messageIds) {
        $messageIds = explode(',',$messageIds);
        $where = [
            'mc_id' => ['in',$messageIds],
        ];
        $updateData = [
            'mc_status' => Config::get('status.message_status')['disable'],
        ];
        Loader::model('messageContent')->where($where)->update($updateData);
        $wheres = [
            'message_content_id' => ['in',$messageIds],
        ];
        $updateDatas = [
            'message_status' => Config::get('status.message_status')['disable']
        ];
        Loader::model('message')->where($wheres)->update($updateDatas);
        return true;
    }
}