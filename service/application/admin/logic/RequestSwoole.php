<?php
/**
 * swoole初始请求参数
 * @author fores
 */

namespace app\admin\logic;

use think\Cache;
use think\Loader;
use think\Model;

class RequestSwoole extends Model {

    public $errorcode = EC_SUCCESS;

    public function serviceInit($subscribeName='') {
        if(!$subscribeName) {
            $this->errorcode = EC_AD_SWOOLE_SUBSCRIBE_NAME_EMPTY;
            return;
        }
        
        $configModel = Loader::model('Config');
        try {

            $ret = $configModel->save(['value'=>$subscribeName], ['name' => 'SOCKET_SUBSCRIBE_NAME']);
            $this->errorcode = EC_SUCCESS;

        } catch (Exception $e) {
            $this->errorcode = EC_AD_SWOOLE_INIT_FAIL;
        }
        
    }

}