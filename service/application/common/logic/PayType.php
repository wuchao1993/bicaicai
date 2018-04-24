<?php

namespace app\api\common;


use think\Config;
use think\Loader;

class PayType {

    public function getList() {
        $condition['pay_type_status'] = Config::get('status.pay_type_status')['enable'];

        $fields = 'pay_type_id, pay_type_name, pay_class_name, pay_type_createtime, pay_type_image, pay_type_status';

        return Loader::model('PayType')->where($condition)->column($fields, 'pay_type_id');
    }

}