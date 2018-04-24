<?php
namespace app\common\logic;

use think\Loader;

class Config{

    public function checkRegisterAllow()
    {
        $registerAllow = Loader::model('Config')->getConfig('USER_ALLOW_REGISTER');
        return (isset($registerAllow) && $registerAllow == '0') ? false : true;
    }

}