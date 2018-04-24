<?php
/**
 * 推送内容表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class PushMessage extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'pm_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pm_id'             => '主键',
        'pm_title'          => '设备ID',
        'pm_platform'       => '平台',
        'pm_app_key'        => 'appKey',
        'pm_type'           => '分类',
        'pm_content'        => '推送内容',
        'pm_extra'          => '操作参数',
        'pm_ios_status'     => 'ios状态',
        'pm_android_status' => 'android状态',
        'pm_status'         => '状态',
        'pm_createtime'     => '创建时间',
        'pm_modifytime'     => '修改时间',
    ];

}