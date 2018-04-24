<?php
/**
 * 六合彩配置表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class LhcConfig extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'lhc_config_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'lhc_config_id'    => '主键',
        'lhc_config_name'  => '名称',
        'lhc_config_value' => '配置值',
    ];

}