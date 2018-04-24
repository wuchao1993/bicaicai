<?php

namespace app\common\model;

use think\Model;

class PayAccount extends Model{

    /**
     * 定义主键
     * @var int
     */
    protected $pk = 'pa_id';


    public function getBankId($paId) {
        $condition = [
            'pa_id' => $paId,
        ];

        return $this->where($condition)->value('bank_id');
    }

}