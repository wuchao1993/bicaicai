<?php
/**
 * 六合彩玩法类型表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class LhcType extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'lhc_type_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'lhc_type_id'              => '主键',
        'lhc_type_name'            => '名称',
        'lhc_verify_fun'           => '下注验证函数',
        'lhc_settle_fun'           => '结算',
        'lhc_type_status'          => '类型状态',
        'play_stake_bet_min_money' => '单注最低投注额',
        'play_stake_bet_max_money' => '单注最高投注额',
        'play_item_bet_max_money'  => '单项最高投注额',
        'lhc_odds_status'          => '赔率状态',
        'lhc_type_sort'            => '排序',
    ];
}