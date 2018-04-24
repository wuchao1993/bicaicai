<?php
/**
 * 推送渠道/应用信息表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class PushChannel extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'pc_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'pc_id'                => '主键',
        'pc_app_name'          => '名称',
        'pc_app_key'           => 'app钥匙',
        'pc_app_master_secret' => 'master_secret',
        'pc_platform'          => '平台',
        'pc_createtime'        => '创建时间',
        'pc_modifytime'        => '修改时间',
    ];

}