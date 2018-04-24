<?php
/**
 * 公共配置文件
 * @createTime 2017/3/22 11:04
 */
return [
    //定义某些控制器或方法不走安全验证
    'security_allow' => [
        //定义某个控制器不走auth行为
        'controller' => ['General'],

        //控制器里的某个方法不走auth行为，规范controller/action
        'action' => [''],
    ],

    //定义后台某些控制器或方法不走安全验证
    'admin_auth_allow' => [
        //定义某个控制器不走auth行为
        'controller' => ['General'],

        //控制器里的某个方法不走auth行为，规范controller/action
        'action' => [
            'Member/memberLogin',
            'Member/memberLogout',
            'Member/getMenuList',
            'Menu/getAllMenuList',
            'Test/index',
            'Report/getAgentReportByNoLogin',
            'Member/twoAuth'
        ],
    ],

    //需要切到试玩库的方法
    'guest_action' => [
        'user/guestsignup',
    ],

    'agent_action' => [
        'user/specialagentsignin',
        'user/specialagentsignup',
        'withdraw/specialagentwithdraw',
        'recharge/specialagentpay',
    ],

    //后台订单列表的状态
    'order_status_name' => [
        'wait'     => '未结算',
        'clear'    => '已结算',
        'cancel'   => '已撤单',
        'win'      => '已中奖',
        'lose'     => '未中奖',
        'back'     => '和局',
        'abnormal' => '异常'
    ],

    //最小串关数和最大串关数
    'parlay_count' => [
        'min' => 2,
        'max' => 10
    ],

    //存储token创建时间的缓存key
    'token_cache_key' => 'token_create_time_',

    //缓存时间配置
    'cache_time' => [
        'events_data'            => 60,
        'in_play_now'            => 5,
        'match_info'             => 864000, //10天
        'schedule_info'          => 86400, //1天
        'game_info'              => 1800,
        'result_info'            => 600,
        'outright_info'          => 60,
        'order_info'             => 3600,
        'user_info'              => 60,
        'user_online'            => 600,
        'count_events_type_num'  => 30,
        'sports_type'            => 900,
        'play_type_groups'       => 300,
        'events_type'            => 30,
        'lock'                   => 900,
        'order_lock'             => 600,
        'schedule_lock'          => 600,
        'calendar'               => 900,
        'in_play_now_sports'     => 900,
        'team_info'              => 864000,
        'bet_limit_setting'      => 1800,
        'betting_limit_setting'  => 90,
        'collect_status'         => 180,
    ],

    //用户名系统保留前缀
    'system_reserved_prefix' => ['special', 'guest'],

    //赛事类型
    'events_type' => [
        'in_play_now' => '滚球',
        'today'       => '今日赛事',
        'early'       => '早盘',
        'parlay'      => '综合过关',
    ],

    //玩法分组
    'play_type_group' => [
        '1x2-handicap-ou-oe' => '独赢/让球/大小',
        'correct_score'      => '波胆(全)',
        'ft_correct_score'   => '波胆(全)',
        '1h_correct_score'   => '波胆(半)',
        'ht_ft'              => '半场/全场',
        'total_goals'        => '总进球',
        'outright'           => '冠军',
        'events'             => '网球赛事',
    ],

    //玩法
    'play_type' => [
        'football' => [
            //全场
            'ft1x2'            => '独赢',
            'ft_handicap'      => '让球',
            'ft_ou'            => '大小',
            'ft_oe'            => '单双',
            'ft_correct_score' => '波胆',
            'ft_total_goals'   => '总进球',

            //半场
            '1h1x2'            => '半场独赢',
            '1h_handicap'      => '半场让球',
            '1h_ou'            => '半场大小',
            '1h_oe'            => '半场单双',
            '1h_correct_score' => '半场波胆',
            '1h_total_goals'   => '半场进球',

            'ht_ft'            => '半场/全场',
            'outright'         => '冠军',
        ],
        'basketball' => [
            //不区分半场全场
            '1x2'           => '独赢',
            'handicap'      => '让球',
            'ou'            => '大小',
            'ou_team'       => '球队得分: 大/小',
            'oe'            => '单双',
            'outright'      => '冠军',
        ],
        'tennis' => [
            //不区分半场全场
            '1x2'           => '独赢',
            'handicap'      => '让盘',
            'ou'            => '大小',
            'ou_pg'         => '球员局数: 大/小',
            'oe'            => '单双',
            'correct_score' => '波胆',
            'outright'      => '冠军',
        ],
    ],

    //赔率字段
    'odds_key' => [
        //独赢
        CAPOT_HOME_WIN,
        CAPOT_GUEST_WIN,
        CAPOT_TIE,
        CAPOT_1H_HOME_WIN,
        CAPOT_1H_GUEST_WIN,
        CAPOT_1H_TIE,

        //让球
        HANDICAP_HOME_WIN,
        HANDICAP_GUEST_WIN,
        HANDICAP_1H_HOME_WIN,
        HANDICAP_1H_GUEST_WIN,

        //大小
        OU_UNDER,
        OU_OVER,
        OU_1H_UNDER,
        OU_1H_OVER,

        //网球球员局数大小
        //篮球球队得分大小
        OUH_UNDER,
        OUH_OVER,
        OUC_UNDER,
        OUC_OVER,

        //单双
        OE_ODD,
        OE_EVEN,

        //波胆
        CORRECT_SCORE_H1C0,
        CORRECT_SCORE_H2C0,
        CORRECT_SCORE_H2C1,
        CORRECT_SCORE_H3C0,
        CORRECT_SCORE_H3C1,
        CORRECT_SCORE_H3C2,
        CORRECT_SCORE_H4C0,
        CORRECT_SCORE_H4C1,
        CORRECT_SCORE_H4C2,
        CORRECT_SCORE_H4C3,
        CORRECT_SCORE_H0C0,
        CORRECT_SCORE_H1C1,
        CORRECT_SCORE_H2C2,
        CORRECT_SCORE_H3C3,
        CORRECT_SCORE_H4C4,
        CORRECT_SCORE_OVH,
        CORRECT_SCORE_H0C1,
        CORRECT_SCORE_H0C2,
        CORRECT_SCORE_H1C2,
        CORRECT_SCORE_H0C3,
        CORRECT_SCORE_H1C3,
        CORRECT_SCORE_H2C3,
        CORRECT_SCORE_H0C4,
        CORRECT_SCORE_H1C4,
        CORRECT_SCORE_H2C4,
        CORRECT_SCORE_H3C4,
        CORRECT_SCORE_OVC,

        //半场/全场
        HT_FT_HOME_HOME,
        HT_FT_HOME_TIE,
        HT_FT_HOME_GUEST,
        HT_FT_TIE_HOME,
        HT_FT_TIE_TIE,
        HT_FT_TIE_GUEST,
        HT_FT_GUEST_HOME,
        HT_FT_GUEST_TIE,
        HT_FT_GUEST_GUEST,

        //总入球
        TOTAL_GOALS_0_TO_1,
        TOTAL_GOALS_2_TO_3,
        TOTAL_GOALS_4_TO_6,
        TOTAL_GOALS_1H_0,
        TOTAL_GOALS_1H_1,
        TOTAL_GOALS_1H_2,
        TOTAL_GOALS_1H_OVER,
        TOTAL_GOALS_OVER,
    ],

    //波胆允许的比分, 主队-客队
    'ft_correct_score' => [
        '1-0', '0-1',
        '2-0', '0-2',
        '2-1', '1-2',
        '3-0', '0-3',
        '3-1', '1-3',
        '3-2', '2-3',
        '4-0', '0-4',
        '4-1', '1-4',
        '4-2', '2-4',
        '4-3', '3-4',
        '0-0', '1-1',
        '2-2', '3-3',
        '4-4',
    ],

    '1h_correct_score' => [
        '1-0', '0-1',
        '2-0', '0-2',
        '2-1', '1-2',
        '3-0', '0-3',
        '3-1', '1-3',
        '3-2', '2-3',
        '0-0', '1-1',
        '2-2', '3-3',
    ],

    //以下这几种玩法的赔率没有加上本金
    'no_principal_play_type' => [
        'handicap',
        'ou',
        'ou_team',
        'ou_pg',
        'ft_handicap',
        '1h_handicap',
        'ft_ou',
        '1h_ou',
    ],

    //危险球的确认时间
    'dangerous_ball_confirm_time' => [
        'football' => 90,
    ],

    //测试用户前缀
    'test_user_name_pre' => 'Guest',

    //赔率在采集数据基础上的调整幅度
    'adjust_odds_value' => 0.03,

    //平台默认代理ID
    'default_agent_uid'  => 1,

    //用户注册时salt的长度
    'user_register_salt_length' => 8,
    //管理员用户ID
    'user_administrator' => 1,
    //userId分段修改
    'per_update_user_ids' => 5000,
    //userId分层用户日记数量
    'per_log_user_ids' => 1000,
];