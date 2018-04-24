<?php

namespace app\admin\model;

use think\Model;

class Channel extends Model {

    protected $pk = "id";

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'id'          		   => '主键',
        'channel_name'         => '渠道名称',
        'create_time'          => '渠道创建时间',
    ];

}