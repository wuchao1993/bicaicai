<?php
/**
 * 用户表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class User extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'user_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'user_id'                    => '主键',
        'user_pid'                   => '父级',
        'user_grade'                 => '用户代理层级',
        'bl_id'                      => '用户限额设定ID（此字段老版本彩票使用）',
        'ul_id'                      => '用户层级ID',
        'bank_id'                    => '银行ID',
        'user_lower_count'           => '跳转协议',
        'user_name'                  => '用户名',
        'user_nickname'              => '昵称',
        'user_email'                 => '邮件',
        'user_mobile'                => '用户手机号',
        'user_contact_info'          => '联系方式',
        'user_qq'                    => '代理注册QQ号',
//        'user_password'              => '登陆密码',
//        'user_funds_password'        => '资金密码',
//        'user_salt'                  => '加密 登录密码 的字符串',
//        'user_funds_salt'            => '加密 资金密码 的字符串',
        'user_realname'              => '真实姓名',
        'user_is_agent'              => '是否代理',
        'user_online_status'         => '在线状态',
        'user_handicap_type'         => '盘口',
        'user_last_login_time'       => '上次登录时间',
        'user_last_login_ip'         => '上次登录',
        'user_reg_ip'                => '注册',
        'user_reg_url'               => '注册链接',
        'user_remark'                => '备注',
        'change_password_time'       => '更改密码时间',
        'user_createtime'            => '创建时间',
        'user_agent_check_status'    => '代理审核状态',
        'user_level_status'          => '用户锁定状态',
        'user_status'                => '状态',
        'user_last_login_session_id' => '上次登录session',
        'user_all_pid'               => '用户所有上级ID',
        'reg_terminal'               => '注册终端',
        'reg_way'                    => '注册方式',
        'channel_id'                 => '渠道表主键ID',
    ];

    public function getUserLevelStatusMap($userIds) {

        $userIds = is_array($userIds) ? $userIds : explode(',', $userIds);

        $condition = [
            'user_id' => [
                'in',
                $userIds,
            ],
        ];


        return $this->where($condition)->column('user_level_status', 'user_id');

    }


    //old 获取团队用户ID
    public function getAllNextUsersAndOwn($userId) {
        $nextUsers = $this->getAllNextUsers($userId);
        return array_merge($nextUsers, explode(',', $userId));
    }


    //old 获取团队用户ID
    public function getAllNextUsers($userId) {
        $response = [];
        $nextUsers = is_array($userId) ? $userId : explode(',',$userId);
        $condition = [
            'user_pid' => [
                'IN',
                $nextUsers,
            ],
        ];

        $nextUsers = $this->where($condition)->column('user_id');
        $response = array_merge($response, $nextUsers);
        while($nextUsers){
            $nextUsers = $this->getAllNextUsers($nextUsers);
        }
        return $response;
    }



    /**
     * 获取团队全部人员 *new
     * @param $user_id
     * @param bool $is_self 是否包含自己
     * @return array
     */
    public function getTeamAllUsers($userId,$isSelf = true){

        $userIds = [];

        if($isSelf) $userIds[] = "{$userId}";

        $uids = $this->where('','exp',"FIND_IN_SET('".$userId."',user_all_pid)")->column('user_id');

        if($userIds&&$uids){
            $userIds = array_merge($userIds,$uids);
        }elseif(!empty($uids)){
            $userIds = $uids;
        }

        return $userIds;
    }




    public function getAllUidsByLevel($ulId){
        return $this->where(array('ul_id' => array('IN', $ulId)))->column('user_id');
    }



    public function getUserAgentInfo($userId){
        if(empty($userId)) return [];

        $condition = [];
        $condition["user_id"] = is_array($userId)?['IN',$userId]:$userId;

        return $this->where($condition)->column("user_id,user_is_agent");
    }


    /**
     * 获取用户信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($uid)
    {
        if(empty($uid)) return [];

        $condition = [
            'user_id' => $uid,
        ];
        $info = $this->where($condition)->find();

        return $info;
    }


    /**
     * 获取用户ID
     * @param $userNames
     */
    public function getUserIdByUserName($userNames){

        if(empty($userNames)) return [];

        $condition = [];

        $condition['user_name'] = ["IN",$userNames];

        return $this->where($condition)->column('user_name,user_id');
    }


}