<?php
/**
 * 订单验证器
 * @createTime 2017/5/2 9:49
 */

namespace app\api\validate;

use think\Config;
use think\helper\Str;
use think\Validate;

class Order extends Validate {
    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'autoOdds'  => 'in:yes,no,none',
        'betAmount' => 'require|float|integer|gt:0',
        'sportId'   => 'require|number|gt:0',
        'eventType' => 'require|checkEventType',
        'betInfo'   => 'checkBetInfo',
        'status'    => 'in:cleared,win,lose,back,wait,cancel',
        'page'      => 'number|gt:0',
        'id'        => 'gt:0',
        'orderNo'   => 'number',
        'orderNos'  => 'require',
        'startTime' => 'date',
        'endTime'   => 'date',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'autoOdds'  => '是否自动接受较佳赔率？',
        'betAmount' => '请输入正确的下注金额',
        'sportId'   => '请输入球类id',
        'eventType' => '赛事类型不合法',
        'betInfo'   => '下注信息不合法',
        'status'    => '状态不合法',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'bet'       => ['autoOdds', 'betAmount', 'sportId', 'eventType', 'betInfo'],
        'mineBet'   => ['sportId' => 'number|egt:0', 'status', 'startTime', 'endTime', 'page'],
        'info'      => ['id', 'orderNo'],
        'multiInfo' => ['orderNos'],
    ];

    /**
     * 验证赛事类型
     * @param $value
     * @return bool|string
     */
    public function checkEventType($value) {
        $eventType = Config::get('common.events_type');
        if (!$eventType[$value]) {
            return '赛事类型错误';
        }
        return true;
    }

    /**
     * 验证下注信息
     * @param $value
     * @return bool|string
     */
    public function checkBetInfo($value) {
        $betInfo = json_decode(htmlspecialchars_decode($value), true);
        if (empty($betInfo) || !is_array($betInfo)) {
            return '下注信息不是合法的json字符串';
        }
        foreach($betInfo as $item) {
            if (!$item['gameId'] || !is_numeric($item['gameId'])) {
                return '盘口id不合法';
            }
            if (!$item['odds'] || !is_numeric($item['odds'])) {
                return '赔率不合法';
            }
            if (!$item['playType']) {
                return '玩法类型为空';
            }
            $playType = Str::snake($item['playType']);
            $playTypeAll = Config::get('common.play_type');

            $playTypeArr = [];
            array_map(function ($value) use (&$playTypeArr) {
                $playTypeArr = array_merge($playTypeArr, array_keys($value));
            }, $playTypeAll);
            $playTypeArr = array_unique($playTypeArr);
            if (!in_array($playType, $playTypeArr)) {
                return '玩法类型错误';
            }

            if (!$item['oddsKey']) {
                return '赔率类型为空';
            }
            $oddsKey = Str::snake($item['oddsKey']);
            $oddsKeyArr = Config::get('common.odds_key');
            if (!in_array($oddsKey, $oddsKeyArr) && !preg_match('/^fs(.*?)/', $oddsKey)) {
                return '赔率类型错误';
            }
        }
        return true;
    }
}