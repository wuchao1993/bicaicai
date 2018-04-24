<?php
/**
 * 六合彩玩法类型表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class LhcOdds extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'lhc_odds_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'lhc_odds_id'     => '主键',
        'lottery_id'      => '彩种类型',
        'lhc_type_id'     => '类型',
        'lhc_odds_labelx' => 'x值',
        'lhc_odds_labely' => 'y值',
        'lhc_odds_value'  => '值',
        'lhc_odds_name'   => '名称',
        'check_fun'       => 'check_fun',
        'clearing_fun'    => 'clearing_fun',
        'lhc_odds_status' => '状态',
        'lhc_odds_sort'   => '排序',
    ];

}