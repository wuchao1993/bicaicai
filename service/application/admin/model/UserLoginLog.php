<?php
/**
 * 用户登陆日志表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class UserLoginLog extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'ull_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'ull_id'         => '主键',
        'user_id'        => '用户ID',
        'ull_type'       => '类型',
        'ull_login_time' => '登录时间',
        'ull_login_ip'   => '登录',
        'ull_country'    => '国家',
        'ull_area'       => '地区',
        'ull_region'     => '省份',
        'ull_county'     => '县',
        'ull_city'       => '城市',
        'ull_isp'        => '服务商',
        'ull_ip_status'  => 'IP状态',
        'ull_createtime' => '创建时间',
        'ull_modifytime' => '修改时间',
    ];

}