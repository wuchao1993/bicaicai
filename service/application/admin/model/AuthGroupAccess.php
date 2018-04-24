<?php
/**
 * 权限组用户表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class AuthGroupAccess extends Model {

    protected $table = 'ds_auth_group_access_new';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'uid'      => '用户ID',
        'group_id' => '用户组ID',
    ];

}