<?php
/**
 * 管理员表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Member extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'uid';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'uid'                   => '主键',
        'nickname'              => '昵称',
        'password'              => '密码',
        'salt'                  => 'salt',
        'sex'                   => '性别',
        'birthday'              => '生日',
        'qq'                    => 'qq号',
        'score'                 => '用户积分',
        'login'                 => '登录次数',
        'reg_ip'                => '注册IP',
        'reg_time'              => '注册时间',
        'last_login_ip'         => '最后登录IP',
        'last_login_time'       => '最后登录时间',
        'mobile'                => '用户积分',
        'email'                 => '用户积分',
        'last_login_session_id' => '用户积分',
        'status'                => '状态',
        'remark'                => '备注',
    	'is_two_factor'			=> '二次验证'
    ];

    /**
     * 获取用户信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserIdByUsername($username) {
        $condition = [
            'nickname' => $username,
        ];
        $info      = $this->where($condition)->find();

        if(!empty ($info)) {
            return $info ['uid'];
        } else {
            return 0;
        }
    }
}