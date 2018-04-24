<?php
/**
 * 采集配置文件
 * @createTime 2017/3/22 11:04
 */

use \think\Env;

$collectDomain = Env::get('app.collect_url');

return [
    'collect_url' => [
        //足球
        'football_today'               => $collectDomain . '/api/Football/getTodayData',
        'football_early'               => $collectDomain . '/api/Football/getEarlyData',
        'football_in_play_now'         => $collectDomain . '/api/Football/getRunningData',
        'football_result'              => $collectDomain . '/api/Football/getGameResult',
        'football_outright'            => $collectDomain . '/api/Football/getChampionList',
        'football_outright_result'     => $collectDomain . '/api/Football/getChampionResultList',
        'football_result_by_game_id'   => $collectDomain . '/api/Football/getResultById',

        //篮球
        'basketball_today'             => $collectDomain . '/api/Basketball/getTodayData',
        'basketball_early'             => $collectDomain . '/api/Basketball/getEarlyData',
        'basketball_in_play_now'       => $collectDomain . '/api/Basketball/getRunningData',
        'basketball_result'            => $collectDomain . '/api/Basketball/getGameResult',
        'basketball_outright'          => $collectDomain . '/api/Basketball/getChampionList',
        'basketball_outright_result'   => $collectDomain . '/api/Basketball/getChampionResultList',
        'basketball_result_by_game_id' => $collectDomain . '/api/Basketball/getResultById',

        //网球
        'tennis_today'                 => $collectDomain . '/api/Tennis/getTodayData',
        'tennis_early'                 => $collectDomain . '/api/Tennis/getEarlyData',
        'tennis_in_play_now'           => $collectDomain . '/api/Tennis/getRunningData',
        'tennis_result'                => $collectDomain . '/api/Tennis/getGameResult',
        'tennis_outright'              => $collectDomain . '/api/Tennis/getChampionList',
        'tennis_outright_result'       => $collectDomain . '/api/Tennis/getChampionResultList',
        'tennis_result_by_game_id'     => $collectDomain . '/api/Tennis/getResultById',

        //公告
        'notice' => $collectDomain . '/api/Notice/getNotice',

        //系统
        'system' => $collectDomain . '/api/System/getCollectStatus',
    ],
];