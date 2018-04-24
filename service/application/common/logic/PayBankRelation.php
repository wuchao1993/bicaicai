<?php

namespace app\common\logic;

use think\Loader;

class PayBankRelation{

    public function getPayTypeIds($bankId){
        $condition['bank_id'] = $bankId;
        return Loader::model('PayBankRelation')->where($condition)->column('pay_type_id');
    }

}