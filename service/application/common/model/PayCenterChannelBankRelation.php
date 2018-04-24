<?php
namespace app\common\model;

use think\Model;

class PayCenterChannelBankRelation extends Model{

    public $pk = 'id';

    public function getChannelMerchantId($bankId){
        $condition = [
            'bank_id' => $bankId
        ];

        return $this->where($condition)->column('pc_id');
    }

}