<?php
/**
 * 弹窗广告表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class Advert extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'advert_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'advert_id'         => '主键',
        'advert_name'       => '广告名称',
        'advert_image'      => '广告图片',
        'advert_url'        => '广告url',
        'advert_type'       => '广告类型',
        'advert_pos'        => '广告位置',
        'advert_format'     => '广告格式',
        'advert_text_app'   => '广告app文本',
        'advert_text_pc'    => '广告pc文本',
        'advert_createtime' => '广告创建时间',
        'advert_status'     => '广告状态',
    ];

}