<?php
/**
 * 数据库状态配置文件
 * @createTime 2017/4/22 11:04
 */
return [
    //用户登录平台
    'login_log_platform' => [
        'pc'      => 1,
        'mobile'  => 2,
        //获取不到ios或android的时候传mobile
        'ios'     => 3,
        'android' => 4,
        'h5'      => 5,
    ],

    'sports_types_status' => [
        'close' => 0,
        'normal' => 1,
    ],

    //是否串关
    'football_game_parlay' => [
        'no'  => 0,
        //单关
        'yes' => 1,
        //串关
    ],

    //是否是主盘口
    'football_game_master' => [
        'yes' => 1,
        'no'  => 0,
    ],
    'football_game_master_id' => [
        1 => 'yes',
        0 => 'no',
    ],

    //是否是重要盘口
    'football_game_important' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //是否显示
    'football_game_is_show' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //赛事类型
    'football_game_event_type' => [
        'today'       => 1,
        'early'       => 2,
        'in_play_now' => 3,
    ],
    'football_game_event_type_id' => [
        1 => 'today',
        2 => 'early',
        3 => 'in_play_now',
    ],

    //是否热门联赛
    'football_match_is_hot' => [
        'yes' => 1,
        'no'  => 0,
    ],
    'football_match_is_hot_id' => [
        1 => 'yes',
        0 => 'no',
    ],

    //对阵是否是滚球
    'football_schedule_in_play_now' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //对阵是否中立场
    'football_schedule_neutral' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //对阵状态
    'football_schedule_status' => [
        'not_begin'        => 0,
        //未开始
        '1h_in_game'       => 1,
        //上半场
        'half_time'        => 2,
        //中场休息
        '2h_in_game'       => 3,
        //下半场
        'game_over'        => 4,
        //比赛结束
        'game_abnormal'    => 5,
        //比赛异常
        'game_rescheduled' => 6,
        //比赛改期
    ],

    'football_schedule_status_id' => [
        0 => 'not_begin',     //未开始
        1 => '1h_in_game',    //上半场
        2 => 'half_time',     //中场休息
        3 => '2h_in_game',    //下半场
        4 => 'game_over',     //比赛结束
        5 => 'game_abnormal', //比赛异常
        6 => 'game_rescheduled', //比赛改期
    ],

    'football_schedule_check_status' => [
        'normal'      => 0, //正常状态
        'halt_sales'  => 1, //封盘
        'wait_cancel' => 2, //标记等待撤单
        'wait_cancel_clearing' => 3, //标记等待撤销结算
        'wait_hand_clearing' => 4, //标记等待人工结算
        'wait_clearing' => 5, //标记等待结算
        'canceled' => 6, //已撤单
    ],
    'football_schedule_check_status_id' => [
        0 => 'normal',      //正常状态
        1 => 'halt_sales',  //封盘
        2 => 'wait_cancel', //标记等待人工撤单
        3 => 'wait_cancel_clearing', //标记等待撤销结算
        4 => 'wait_hand_clearing', //标记等待人工结算
        5 => 'wait_clearing', //标记等待结算
        6 => 'canceled', //已撤单
    ],

    //对阵是否算奖
    'football_schedule_clearing' => [
        'yes' => 1, //已算奖
        'no'  => 0, //未算奖
    ],

    'football_schedule_clearing_id' => [
        1 => 'yes', //已算奖
        0 => 'no',  //未算奖
    ],

    //ds_sports_football_outright表的sfo_is_show
    'football_outright_is_show' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //冠军盘口是否算奖
    'football_outright_clearing' => [
        'yes' => 1,
        //已算奖
        'no'  => 0,
        //未算奖
    ],

    //是否串关
    'basketball_game_parlay' => [
        'no'  => 0, //单关
        'yes' => 1, //串关
    ],

    //是否是主盘口
    'basketball_game_master' => [
        'yes' => 1,
        'no'  => 0,
    ],
    'basketball_game_master_id' => [
        1 => 'yes',
        0 => 'no',
    ],

    //盘口类型
    'basketball_game_type' => [
        '1q' => '第1节',
        '2q' => '第2节',
        '3q' => '第3节',
        '4q' => '第4节',
        '1h' => '上半',
        '2h' => '下半',
        'ot' => '加时赛',
    ],
    'basketball_game_type_name' => [
        '第1节' => '1q',
        '第2节' => '2q',
        '第3节' => '3q',
        '第4节' => '4q',
        '上半'  => '1h',
        '下半'  => '2h',
        '加时赛' => 'ot',
    ],

    //是否赛节盘口
    'basketball_game_is_period' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //是否是重要盘口
    'basketball_game_important' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //是否显示
    'basketball_game_is_show' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //赛事类型
    'basketball_game_event_type'  => [
        'today'       => 1,
        'early'       => 2,
        'in_play_now' => 3,
    ],
    'basketball_game_event_type_id' => [
        1 => 'today',
        2 => 'early',
        3 => 'in_play_now',
    ],

    //是否热门联赛
    'basketball_match_is_hot' => [
        'yes' => 1,
        'no'  => 0,
    ],
    'basketball_match_is_hot_id' => [
        1 => 'yes',
        0 => 'no',
    ],

    //对阵是否是滚球
    'basketball_schedule_in_play_now' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //对阵状态
    'basketball_schedule_status' => [
        'not_begin'        => 0,
        //未开始
        'in_game'          => 1,
        //比赛进行中
        'half_time'        => 2,
        //中场休息
        'game_over'        => 3,
        //比赛结束
        'game_abnormal'    => 4,
        //比赛异常
        'game_rescheduled' => 5,
        //比赛改期
    ],

    'basketball_schedule_status_id' => [
        0 => 'not_begin', //未开始
        1 => 'in_game',   //比赛进行中
        2 => 'half_time', //中场休息
        3 => 'game_over', //比赛结束
        4 => 'game_abnormal', //比赛异常
        5 => 'game_rescheduled', //比赛改期
    ],

    'basketball_schedule_check_status' => [
        'normal'      => 0, //正常状态
        'halt_sales'  => 1, //封盘
        'wait_cancel' => 2, //标记等待人工撤单
        'wait_cancel_clearing' => 3, //标记等待撤销结算
        'wait_hand_clearing' => 4, //标记等待人工结算
        'wait_clearing' => 5, //标记等待结算
        'canceled' => 6, //已撤单
    ],
    'basketball_schedule_check_status_id' => [
        0 => 'normal',      //正常状态
        1 => 'halt_sales',  //封盘
        2 => 'wait_cancel', //标记等待人工撤单
        3 => 'wait_cancel_clearing', //标记等待撤销结算
        4 => 'wait_hand_clearing', //标记等待人工结算
        5 => 'wait_clearing', //标记等待结算
        6 => 'canceled', //已撤单
    ],

    //对阵是否算奖
    'basketball_schedule_clearing' => [
        'yes' => 1, //已算奖
        'no'  => 0, //未算奖
    ],

    'basketball_schedule_clearing_id' => [
        1 => 'yes', //已算奖
        0 => 'no',  //未算奖
    ],

    //ds_sports_basketball_outright表的sbo_is_show
    'basketball_outright_is_show' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //冠军盘口是否算奖
    'basketball_outright_clearing' => [
        'yes' => 1,
        //已算奖
        'no'  => 0,
        //未算奖
    ],

    //是否赛节盘口
    'tennis_game_is_period' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //是否串关
    'tennis_game_parlay' => [
        'yes' => 1, //串关
        'no'  => 0, //单关
    ],

    //是否是主盘口
    'tennis_game_master' => [
        'yes' => 1,
        'no'  => 0,
    ],

    'tennis_game_master_id' => [
        1 => 'yes',
        0 => 'no',
    ],

    //是否显示
    'tennis_game_is_show' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //赛事类型
    'tennis_game_event_type' => [
        'today'       => 1,
        'early'       => 2,
        'in_play_now' => 3,
    ],
    'tennis_game_event_type_id' => [
        1 => 'today',
        2 => 'early',
        3 => 'in_play_now',
    ],

    //是否热门联赛
    'tennis_match_is_hot' => [
        'yes' => 1,
        'no'  => 0,
    ],
    'tennis_match_is_hot_id' => [
        1 => 'yes',
        0 => 'no',
    ],

    //是否显示延赛信息
    'tennis_schedule_show_delay' => [
        'yes' => 1,
        'no'  => 0,
    ],
    'tennis_schedule_show_delay_id' => [
        1 => 'yes',
        0 => 'no',
    ],

    //对阵是否是滚球
    'tennis_schedule_in_play_now' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //对阵状态
    'tennis_schedule_status' => [
        'not_begin'        => 0,
        //未开始
        'in_game'          => 1,
        //比赛进行中
        'game_over'        => 2,
        //比赛结束
        'game_abnormal'    => 3,
        //比赛异常
        'game_rescheduled' => 4,
        //比赛改期
    ],

    'tennis_schedule_status_id' => [
        0 => 'not_begin', //未开始
        1 => 'in_game',   //比赛进行中
        2 => 'game_over', //比赛结束
        3 => 'game_abnormal', //比赛异常
        4 => 'game_rescheduled', //比赛改期
    ],

    'tennis_schedule_check_status' => [
        'normal'      => 0, //正常状态
        'halt_sales'  => 1, //封盘
        'wait_cancel' => 2, //标记等待人工撤单
        'wait_cancel_clearing' => 3, //标记等待撤销结算
        'wait_hand_clearing' => 4, //标记等待人工结算
        'wait_clearing' => 5, //标记等待结算
        'canceled' => 6, //已撤单
    ],
    'tennis_schedule_check_status_id' => [
        0 => 'normal',      //正常状态
        1 => 'halt_sales',  //封盘
        2 => 'wait_cancel', //标记等待人工撤单
        3 => 'wait_cancel_clearing', //标记等待撤销结算
        4 => 'wait_hand_clearing', //标记等待人工结算
        5 => 'wait_clearing', //标记等待结算
        6 => 'canceled', //已撤单
    ],

    //对阵是否算奖
    'tennis_schedule_clearing' => [
        'yes' => 1, //已算奖
        'no'  => 0, //未算奖
    ],

    'tennis_schedule_clearing_id' => [
        1 => 'yes', //已算奖
        0 => 'no',  //未算奖
    ],

    //ds_sports_tennis_outright表的sfo_is_show
    'tennis_outright_is_show' => [
        'yes' => 1,
        'no'  => 0,
    ],

    //冠军盘口是否算奖
    'tennis_outright_clearing' => [
        'yes' => 1, //已算奖
        'no'  => 0,//未算奖
    ],

    //盘口类型
    'tennis_game_type' => [
        '1st' => '第一盘',
        '2nd' => '第二盘',
        '3rd' => '第三盘',
        '4th' => '第四盘',
        '5th' => '第五盘',
        'handicap' => '让局',
    ],
    'tennis_game_type_name' => [
        '第一盘' => '1st',
        '第二盘' => '2nd',
        '第三盘' => '3rd',
        '第四盘' => '4th',
        '第五盘' => '5th',
        '让局'   => 'handicap',
    ],

    //订单source_ids的来源表
    'order_source_ids_from'                => [
        'schedule' => 1,
        //来源schedules表
        'outright' => 2,
        //来源outright表
    ],

    //订单赛事类型
    'order_event_type'                     => [
        'in_play_now' => 1,
        'today'       => 2,
        'early'       => 3,
        'parlay'      => 4,
    ],
    'order_event_type_id'                  => [
        1 => 'in_play_now',
        2 => 'today',
        3 => 'early',
        4 => 'parlay',
    ],
    'order_event_type_name'                => [
        1 => '滚球',
        2 => '今日赛事',
        3 => '早盘',
        4 => '综合过关',
    ],

    //订单审核状态
    'order_check_status' => [
        'wait'      => 0,
        //待审核
        'yes'       => 1,
        //审核通过
        'system_no' => 2,
        //系统审核不通过
        'hand_no'   => 3,
        //人工审核不通过
    ],

    //注单输赢结果状态
    'order_bet_status' => [
        ''          => 0,
        //为空
        'win'       => 1,
        //赢
        'win_half'  => 2,
        //赢一半
        'lose'      => 3,
        //输
        'lose_half' => 4,
        //输一半
        'back'      => 5,
        //和局，本金返还
    ],

    //注单输赢结果状态
    'order_bet_status_id' => [
        0 => '',
        //为空
        1 => 'win',
        //赢
        2 => 'win_half',
        //赢一半
        3 => 'lose',
        //输
        4 => 'lose_half',
        //输一半
        5 => 'back',
        //和局，本金返还
    ],

    //体彩注单状态
    'order_status' => [
        'wait'                 => 1, //等待开奖
        'wait_hand_clearing'   => 2, //等待人工开奖
        'wait_cancel'          => 3, //审核不通过等待撤票
        'result_abnormal'      => 4, //赛果异常，等待人工撤单或结算
        'clearing'             => 5, //已结算
        'distribute'           => 6, //已派奖
        'system_cancel'        => 7, //系统撤票
        'hand_cancel'          => 8, //人工撤票
        'cancel_fail'          => 9, //撤票失败
        'funds_not_enough'     => 10,//余额不足撤销结算失败
    ],

    //体彩玩法状态
    'play_type_status'                     => [
        'yes' => 1,
        //正常
        'no'  => 2,
        //禁用
    ],

    //公告分类
    'notice_type'                          => [
        'member'       => 1,
        //会员公告
        'alert'        => 2,
        //弹出公告
        'new'          => 3,
        //最新公告
        'game'         => 4,
        //游戏公告
        'sports_event' => 5,
        //体育赛事公告
    ],

    //公告类型
    'notice_lottery_type' => [
        'digital' => 1,
        'sports'  => 2,
    ],

    //公告分类
    'notice_status'       => [
        'yes' => 1,
        //启用
        'no'  => 0,
        //停用
    ],
    //公告是否跑马灯
    'notice_marquee'      => [
        'yes' => 1,
        'no'  => 0,
    ],

    //体彩公告分类列表
    'sports_notice_type_list'              => [
        [
            'type_id'   => 5,
            'type_name' => '赛事公告',
        ],
        [
            'type_id'   => 3,
            'type_name' => '最新公告',
        ],
    ],

    //ds_sports_low_odds_bonus_record 是否已经提现稽核过
    'bonus_record_withdraw_status' => [
        'yes' => 1,
        //已完成
        'no'  => 0,
        //未完成
    ],
    'bonus_record_type' => [
        'bonus'  => 1,
        //已完成
        'cancel' => 2,
        //未完成
    ],

    //ds_user_account_record表里的uar_transaction_type金流类型
    'account_record_transaction_type'      => [
        'recharge'         => 1,
        //会员入款在线充值
        'discount'         => 2,
        //优惠赠送
        'rebate'           => 3,
        //返点
        'bonus'            => 5,
        //奖金
        'bet'              => 7,
        //投注
        'withdraw'         => 8,
        //提现
        'artificial_in'    => 9,
        //人工入款
        'cancel_order'     => 10,
        //取消订单
        'change'           => 11,
        //金流转换
        'agent_rebate'     => 12,
        //代理返水
        'artificial_out'   => 13,
        //人工扣款
        'recharge_company' => 14,
        //公司入款
        'withdraw_deduct'  => 15,
        //提现扣除手续费
        'withdraw_cancel'  => 16,
        //取消提现
        'sports_rebate'    => 17,
        //体彩返点
        'withdraw_complete' => 18,
        //提现成功
    ],

    //ds_user_account_record表里的uar_transaction_type金流类型
    'account_record_transaction_type_name' => [
        1  => '在线充值',
        2  => '优惠赠送',
        3  => '返点',
        5  => '奖金',
        7  => '投注',
        8  => '提现',
        9  => '人工入款',
        10 => '取消订单',
       // 11 => '金流转换',
        12 => '代理返水',
        13 => '人工扣款',
        14 => '公司入款',
        15 => '提现扣除手续费',
        16 => '取消提现',
        17 => '体彩返点',
        18 => '提现成功',
    ],

    //ds_user_account_record表，金流类型
    'account_record_action_type'           => [
        'deposit' => 1,
        //存入
        'fetch'   => 2,
        //取出
    ],

    'account_record_action_type_name' => [
        1 => '存入',
        2 => '取出',
    ],
    //客户角度金流状态
    'account_record_action_type_name_for_client' => [
        1  => '盈利',
        7  => '盈利',
        9  => '盈利',
        13 => '盈利',
        14 => '盈利',
        15 => '盈利',
        16 => '盈利',
        2  => '亏损',
        3  => '亏损',
        5  => '亏损',
        8  => '亏损',
        10 => '亏损',
        12 => '亏损',
        17 => '亏损',
        18 => '亏损',
    ],

    //ds_user_account_record表，状态
    'account_record_status'           => [
        'yes' => 1,
        //已完成
        'no'  => 0,
        //未完成
    ],

    //数字彩注单状态
    'lottey_order_status' => [
        'wait'                 => 1, //未结算
        'win'                  => 2, //已中奖
        'lose'                 => 3, //未中奖
        'cancel'               => 4, //已取消
//        'clearing'             => 5, //已结算 (停用)
//        'distribute'           => 6, //已派奖 (停用)
//        'system_cancel'        => 7, //系统撤票 (停用)
//        'hand_cancel'          => 8, //人工撤票 (停用) - 参考旧平台-使用状态-4
//        'cancel_fail'          => 9, //撤票失败 (停用)
    ],

    //数字彩注单状态中文
    'lottey_order_status_name'        => [
        1 => '未结算',
        2 => '已中奖',
        3 => '未中奖',
        4 => '已取消',
        5 => '和局',
    ],
    //数字彩彩种状态
    'lottery_status'                   => [
        'yes' => 1,
        //启用
        'no'  => 0,
        //停用
    ],
   //数字彩玩法状态
    'lottery_type_status'              => [
        'yes' => 1,
        //启用
        'no'  => 0,
        //停用
    ],

    //操作类型-存款
    'operation_type_in'                 => [
                'recharge'  => 1,
                'discount'  => 2,
                'rebate'    => 3,
                'plus'      => 4,
                'reg'       => 5
    ],

    //操作类型-取出
    'operation_type_out'                => [
            'deduct_recharge'   => 5,
            'deduct_discount'   => 6,
            'deduct_handsel'    => 7,
            'deduct_rebate'     => 8,
            'other'             => 9
    ],

    //用户人工入款类型中文
    'user_recharge_system_name'       => [
        1 => '人工存入-存款',
        2 => '人工存入-优惠',
        3 => '人工存入-返点',
        4 => '余额负数冲正',
        5 => '注册优惠',
    ],

    //user_recharge_system_name 反向，excel导入人工入款
    'system_recharge'               =>[
        '存款'    => 1,
        '优惠'    => 2,
        '返点'    => 3,
        '余额负数冲正'=> 4,
        '注册优惠'  => 5
    ],

    //入款类型，对应user_recharge_record的type
    'user_recharge_type'              => [
        'online'        => 1,
        'company'       => 2,
        'system'        => 3,
    ],
    'user_recharge_type_name'         => [
        1 => '在线充值',
        2 => '转账汇款',
        3 => '系统充值',
    ],

    //用户人工出款类型中文
    'user_withdraw_system_name'       => [
        5 => '冲帐-扣除[入款误存]金额',
        6 => '冲帐-扣除误存的[入款优惠]金额',
        7 => '冲帐-扣除误存的[优惠活动赠送]金额',
        8 => '冲帐-扣除误存的[返水]金额',
        9 => '其他原因',
    ],

    //人工扣款类型，对应user_withdraw_system_name
    'user_withdraw_system_type'       => [
        'recharge_mistake' => 5
    ],

    //出款类型，对应user_withdraw_record的type
    'user_withdraw_type'              => [
        'online' => 1,
        'system' => 2,
    ],

    'user_withdraw_type_name'              => [
        1 => '线上出款',
        2 => '人工出款',
    ],

    'user_level_status'               => [
        'normal' => 1,
        'lock'   => 0,
        'deleted'   => -1,
    ],

    //充值搜索类型
    'recharge_search_type'            => [
        'user'     => 1,
        'orderno'  => 2,
        'operator' => 3,
    ],

    //充值搜索类型中文
    'recharge_search_type_name'       => [
        1 => '会员账号',
        2 => '订单号',
        3 => '操作者账号',
    ],

    //充值状态类型
    'recharge_status'                 => [
        'wait'    => 0,
        'success' => 1,
        'fail'    => 2,
        'close'   => 3,
    ],

    'recharge_status_id'                 => [
        0 => 'wait',
        1 => 'success',
        2 => 'fail',
        3 => 'close',
    ],

    //充值状态类型中文
    'recharge_status_name'            => [
        0 => '待支付',
        1 => '充值成功',
        2 => '充值失败',
        3 => '已关闭',
    ],

    //在线充值平台类型中文
    'pay_category_type_name'          => [
        1   => '网银支付',
        4   => '微信wap',
        5   => '支付宝wap',
        6   => '支付宝扫码',
        7   => '微信扫码',
        11  => 'QQ钱包',
        12  => 'QQ钱包wap',
        13  => '京东钱包',
        14  => '京东钱包wap',
        15  => '百度钱包',
        16  => '百度钱包wap',
        17  => '银联钱包',
        18  => '银联钱包wap',
        23  => '网银无卡',
        24  => '网银无卡wap',
        25  => '代付',
    ],

    //提款状态类型
    'withdraw_status'                 => [
        'submit'  => 1,
        'lock'    => 2,
        'confirm' => 3,
        'cancel'  => 4,
        'refuse'  => 5,
    ],

    //提款状态类型中文
    'withdraw_status_name'            => [
        1 => '提交',
        2 => '锁定',
        3 => '确认',
        4 => '取消',
        5 => '拒绝',
    ],
    //是否代付
    'withdraw_is_payment'               =>[
        'cancel'=>2,
        'confirm' => 1,
        'no'  => 0
    ],

    //提款是否首次
    'withdraw_is_first'               =>[
        'before' => 2,
        'yes'    => 1,
        'no'     => 0
    ],

    //充值是否首次
    'recharge_is_first'               =>[
        'yes' => 1,
        'no'  => 0
    ],

    //充值记录是否已经提现稽核过
    'recharge_record_withdraw_status' => [
        'yes' => 1,
        //已完成
        'no'  => 0,
        //未完成
    ],

    //提款审核状态类型
    'withdraw_check_status'           => [
        'refuse' => 0,
        'pass'   => 1,
    ],

    //提款审核状态类型中文
    'withdraw_check_status_name'      => [
        0 => '不可提现',
        1 => '可提现',
    ],

    //用户注册优惠类型中文
    'reg_discount_type_name'          => [
        1 => '全部',
        2 => '电脑',
        3 => 'IOS手机',
        4 => 'ANDROID手机',
    ],

    //支付类型状态
    'recharge_type_status'            => [
        'normal'   => 1,
        'disabled' => 0,
    ],
    'action_type'                     => [
        'account_transfer_in'   => '存入',
        'account_transfer_out'  => '取出',
    ],
    'account_transaction_type'        => [
        'recharge'  => '会员入款',
        'discount'  => '优惠赠送',
    ],
    //银行状态
    'bank_status'                     => [
        'normal'   => 1,
        'disabled' => 0,
    ],

    //公司入款账号状态
    'pay_account_status'              => [
        'enable'  => 1,
        'disable' => 0,
        'del'     => -1,
    ],

    //公告类型
    'notice_type_name'                => [
        1 => '会员公告',
        2 => '弹出公告',
        3 => '最新公告',
        4 => '游戏公告',
        5 => '赛事公告',
    ],

    //资讯类型
    'information_type_name'           => [
        0 => '新闻',
        1 => '技巧',
    ],

    //帮助类型
    'help_type_name'                  => [
        1 => '常见问题',
        2 => '个人账号',
        3 => '充值提款',
        4 => '规则说明',
        5 => '新手上路',
    ],

    //推送人群类型
    'pushmessage_type_name'           => [
        1 => '群推',
        2 => '指定用户',
    ],

    //用户银行卡状态
    'user_bank_status'                => [
        'enable'  => 1,
        'disable' => 0,
    ],

    //流水日志来源类型(1、订单2、充值 3、提现 4、视讯 5、体育
    'user_account_record_source_type' => [
        'order'    => 1,
        'recharge' => 2,
        'withdraw' => 3,
        'live'     => 4,
        'sports_order' => 10,
    ],


    //支付平台状态
    'pay_platform_status'             => [
        'enable'  => 1,
        'disable' => 0,
    ],

    //公司入款类型
    'company_recharge_type'           => [
        COMPANY_RECHARGE_TYPE_ONLINEBANK_TRANSFER => '网银转账',
        COMPANY_RECHARGE_TYPE_ATM_MACHINE         => 'ATM自动柜员机',
        COMPANY_RECHARGE_TYPE_ATM_CASH            => 'ATM现金入款',
        COMPANY_RECHARGE_TYPE_BANK_COUNTER        => '银行柜台',
        COMPANY_RECHARGE_TYPE_MOBILEBANK_TRANSFER => '手机银行转账',
        COMPANY_RECHARGE_TYPE_WECHAT              => "微信转账",
        COMPANY_RECHARGE_TYPE_ALIPAY              => '支付宝转账',
    ],

    //用户登录类型
    'user_login_type_name'            => [
        1 => 'PC登陆',
        2 => '手机登陆',
        3 => 'ios App',
        4 => 'Android App',
        5 => 'H5',
    ],


    'pay_type_status' => [
        'enable'  => 1,
        'disable' => 0,
    ],

    'account_transfer' => [
        'in'  => 1,
        'out' => 2,
    ],

    //用户状态
    'user_status'                => [
        'unverified' => 2,
        'enable'     => 1,
        'disable'    => 0,
    ],

    //用户行为状态
    'action_status'                 => [
        'delete'  => -1,
        'disable' => 0,
        'enable'  => 1,
    ],


    //弹窗广告分类
    'advert_type_name'                => [
        1 => '手机',
        2 => '网站',
    ],

    //弹窗广告位置
    'advert_pos_name'                => [
        1 => '首页',
        2 => '用户登录',
    ],

    //弹窗广告格式
    'advert_format'                => [
        1 => '图片',
        2 => '文本',
    ],

    //弹窗信息状态
    'advert_status'  => [
        'normal' => 1,
        'lock'   => 0,
        'deleted'   => -1,
    ],

    //支付配置
    'pay_config_display_name'                => [
        1 => '终端号',
        2 => 'MD5密匙',
        3 => 'RSA商户私钥',
        4 => 'RSA服务端公钥',
        5 => '上传私钥证书',
        6 => '私钥文件密钥',
        7 => '回调密钥'
    ],

    //代理链接状态
    'agent_link_status'         =>[
        'enable'        => 1,
        'disable'       => 0,//删除，不显示
        'disable_show'  => 2,//禁用，可显示
    ],

    //代理链接类型
    'agent_link_type' => [
        'agent' => 2,
        'user'  => 1,
    ],

    //注册平台
    'reg_terminal' => [
        'pc'      => 1,
        'mobile'  => 2,
        'ios'     => 3,
        'android' => 4,
        'h5'      => 5,
        'unknown' => 6,
    ],

    //彩期开奖状态
    'issue_prize_status'        => [
        'no'            => 0,
        'yes'           => 1,
        'cancel'        => 2
    ],

    //用户是否代理
    'user_is_agent'     => [
        'yes'   => 1,
        'no'    => 0
    ],

    'user_agent_check_status' =>[
        'pend'      => 0,
        'enable'    => 1,
        'disable'   => 2
    ],

    //用户层级是否默认
    'user_level_default'   => [
            'yes'   => 1,
            'no'    => 0
    ],

    //用户层级状态
    'ul_status'  => [
        'normal' => 1,
        'lock'   => 0,
        'deleted'   => -1,
    ],

     //活动信息状态
    'activity_status'  => [
        'normal' => 1,
        'lock'   => 0,
        'deleted'   => -1,
    ],

    'activity_lottery_type' => [
        'digital' => 1,
        'sports'  => 2,
    ],
    //消息推送类型设置
    'pm_type'  => [
        '1' => '群推',
        '2' => '指定用户',
    ],
    //消息推送推送至设置
    'pm_type2'  => [
        '1' => '渠道：',
        '2' => '用户：',
    ],

    //用户注册来源
    'user_register_way' => [
        'default' => 0,
        'common' => 1,
        'domain' => 2,
        'code' => 3,
    ],

    //代理域名状态
    'agent_domain_status' => [
        'disable' => 0,
        'enable'  => 1,
         'del'    => -1
    ],

    'agent_check_status' => [
        'wait' => 0,
        'past' => 1,
    ],

    //创建下级用户默认密码
    'create_subordinate_default_password' => 'a123456',

    //文档类型
    'document_status' => [
        'enable'    => 1,
        'disable'   => 2,
        'delete'    => -1,
    ],

    'document_type' => [
        'agent' => 1,
    ],

    'document_status_description' => [
        1 => '代理相关',
    ],
    'document_condition' => [
        'page'          => 0,
        'count'         => 10,
        'page_start'    => 0,
        'page_end'      => 1,
    ],
    'document_default_time' => [
        'start_time'    => 0,
        'end_time'      => 0,
    ],

    //时时彩下单位置设置
    'order_bet_position'  => [
        '1' => '万位',
        '2' => '千位',
        '3' => '百位',
        '4' => '十位',
        '5' => '个位',
    ],
    //用户代理层级
    'user_grade'  => [
        'level_zero' => 0,
        'level_one' => 1,
        'level_two' => 2,
    ],

    //站点配置状态
    'site_config_status' => [
        'enable' => 1,
        'disable' => 2,
    ],

    'site_config_type' => [
        'number'      => 0,
        'string'      => 1,
        'text'        => 2,
        'array'       => 3,
        'enumeration' => 4,
        'json'        => 5,
    ],

    'site_config_lottery_type' => [
        'digital' => 1,
        'sports'  => 2,
    ],

    'site_config_group' => [
        'common'  => 1,
        'pc'      => 2,
        'h5'      => 3,
        'app'     => 4,
        'mobile'  => 4,
        'ios'     => 4,
        'android' => 4,
    ],

    'pay_center_status' => [
        'enable' => 1,
        'disable' => 2,
    ],

    'message_status' => [
        'enable' => 1,      //正常
        'disable' => 2,     //删除
    ],
    'message_is_read' => [
        'yes' => 2,      //已读取
        'no' => 1,     //未读取
    ],
    'message_read_status' => [
        1 => 'no',     //未读取
        2 => 'yes',      //已读取
    ],
    'advertising_status' => [
        'enable'  => 1,
        'disable' => 2,
        'deleted' => -1,
    ],
];