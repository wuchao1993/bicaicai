<?php
/**
 * 数字彩玩法类型表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;

class LotteryPlay extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'play_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'play_id'                  => '主键',
        'lottery_type_id'          => '分类ID',
        'play_group_name'          => '玩法组名称',
        'play_name'                => '玩法名称',
        'play_real_name'           => '真实名称',
        'play_short_name'          => '短名称',
        'play_min_odds'            => '最小赔率',
        'play_max_odds'            => '最大赔率',
        'play_stake_bet_min_money' => '单注最小下注金额',
        'play_stake_bet_max_money' => '单注最大下注金额',
        'play_item_bet_max_money'  => '单项最大下注金额',
        'play_help'                => '玩法帮助',
        'play_example'             => '玩法示例',
        'play_tips'                => '玩法tips',
        'play_balls'               => 'balls',
        'play_checkbox'            => 'checkbox',
        'play_sort'                => '排序',
        'play_bet_example'         => '投注例子',
        'play_verify_fun'          => '验证方法',
        'play_settle_fun'          => '算奖方法',
        'play_status'              => '状态',
    ];

}