<?php

namespace app\api\logic;

use think\Config;
use think\Loader;

class SiteConfig {

    public $errorcode = EC_SUCCESS;

    public function getSiteConfig($siteType, $terminal) {
        $condition = [
            'sc_lottery_type' => $siteType,
            'sc_group'        => Config::get('status.site_config_group')[$terminal],
            'sc_status'       => Config::get('status.site_config_status')['enable']
        ];

        return Loader::model('SiteConfig')->where($condition)->column('sc_name, sc_value');
    }
}