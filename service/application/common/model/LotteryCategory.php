<?php

namespace app\common\model;

use think\Model;

class LotteryCategory extends Model
{

    public $pk = 'lottery_category_id';

    public function getDefaultRebateMap()
    {
        return $this->column('lottery_default_rebate', 'lottery_category_id');
    }

}