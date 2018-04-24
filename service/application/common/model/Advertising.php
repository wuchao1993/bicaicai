<?php

namespace app\common\model;

use think\Model;
use think\Config;

class Advertising extends Model
{

    public $pk = 'advertising_id';

    /**
     * 前台获取广告列表信息
     * @param $params
     * @return array
     */
    public function getList($params) {

        $where = [
            'advertising_status'    => Config::get('status.advertising_status')['enable'],
            'advertising_site_type' => $params['siteType'],
            'advertising_terminal'  => $params['terminal'],
        ];

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
        ];
        $advertisingData = $this->field($field)->where($where)->select();
        return $advertisingData;
    }
}