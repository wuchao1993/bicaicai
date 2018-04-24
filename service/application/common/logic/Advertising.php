<?php
namespace app\common\logic;

use think\Config;
use think\Loader;
use think\Model;

class Advertising extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 前台广告列表
     * @param $params
     */
    public function getList($params) {
        $advertisingData =  Loader::model('Advertising')->getList($params);
        return $advertisingData;
    }


}