<?php
/**
 * 弹窗广告表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;
use think\Config;

class Advertising extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'advertising_id';

    /**
     * 获取广告信息详情
     * @param $id
     * @return array
     */
    public function getAdvertisingInfo($id) {
        $where['advertising_id'] = $id;
        $where['advertising_status'] = ['neq', Config::get('status.advertising_status')['deleted']];
        $field = [
            'advertising_id'           => 'id',
            'advertising_name'         => 'name',
            'advertising_sketch_image' => 'sketchImage',
            'advertising_size'         => 'size',
            'advertising_remark'       => 'remark',
            'advertising_web_image'    => 'webImage',
            'advertising_link'         => 'link',
            'advertising_status'       => 'status',
            'advertising_site_type'    => 'siteType',
            'advertising_terminal'     => 'terminal',
            'advertising_identifier'   => 'identifier',
        ];
        $advertisingData = $this->field($field)->where($where)->find();
        return $advertisingData;
    }

}