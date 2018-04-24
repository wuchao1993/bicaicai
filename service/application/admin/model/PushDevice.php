<?php
/**
 * 推送设备表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class PushDevice extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'pd_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pd_id'         => '主键',
        'device_id'     => '设备ID',
        'pd_platform'   => '平台',
        'pd_token'      => 'token',
        'pd_app_key'    => 'app钥匙',
        'user_id'       => '用户ID',
        'pd_createtime' => '创建时间',
        'pd_modifytime' => '修改时间',
    ];
}