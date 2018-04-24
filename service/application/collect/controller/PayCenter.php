<?php
/**
 * 公告
 * @createTime 2017/7/20 9:38
 */

namespace app\collect\controller;

use think\Loader;

class PayCenter {
    
    public function updateChannelMerchantBank(){
    	Loader::model('PayCenter', 'logic')->updateBank();
    }

    public function updateChannelList(){
        Loader::model('PayCenter', 'logic')->updateChannelList();
    }

    public function updateChannelMerchantList(){
        Loader::model('PayCenter', 'logic')->updateChannelMerchantList();
    }

}