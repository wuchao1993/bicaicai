<?php
/**
 * 足球赛事验证器
 * @createTime 2017/4/15 9:49
 */

namespace app\api\validate;

use think\Config;
use think\helper\Str;
use think\Validate;

class SportsEvents extends Validate {
    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'matches'       => 'checkMatches',
        'order'         => 'in:time_asc,time_desc,match_asc,match_desc',
        'playTypeGroup' => 'require|checkPlayTypeGroup',
        'page'          => 'number|gt:0',
        'pageAll'       => 'number|egt:0',
        'sportId'       => 'require|number|gt:0',
        'gameId'        => 'require|number|gt:0',
        'eventType'     => 'require|checkEventType',
        'playType'      => 'require|checkPlayType',
        'oddsKey'       => 'require|checkOddsKey',
        'gameInfo'      => 'require|checkGameInfo',
        'date'          => 'date',
        'sportType'     => 'require',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'order.in'         => '排序不合法',
        'page'             => '分页参数不合法',
        'pageAll'          => '分页参数不合法',
        'sportId.require'  => '请传入体育赛事id',
        'sportId.number'   => 'sportId不合法',
        'sportId.gt'       => 'sportId不合法',
        'gameId'           => '盘口id不合法',
        'date'             => '日期格式不合法',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'index'       => ['matches', 'order', 'playTypeGroup', 'page', 'pageAll', 'eventType', 'date', 'sportType'],
        'inPlayNow'   => ['matches', 'order', 'playTypeGroup', 'page', 'pageAll'],
        'today'       => ['matches', 'order', 'playTypeGroup', 'page', 'pageAll'],
        'early'       => ['matches', 'order', 'playTypeGroup', 'page', 'pageAll'],
        'parlay'      => ['matches', 'order', 'playTypeGroup', 'page', 'pageAll'],
        'playType'    => ['sportId'],
        'leagueMatches' => ['eventType', 'playTypeGroup', 'sportType'],
        'eventsType'  => ['sportId'],
        'refreshOdds' => ['gameId', 'eventType', 'playType', 'oddsKey', 'sportType'],
        'refreshOddsMulti' => ['gameInfo'],
        'sportsPlayTypes' => ['eventType'],
    ];

    /**
     * 联赛id串验证，1,2,4,8
     * @param $value
     * @return bool|string
     */
    public function checkMatches($value) {
        if (!is_string($value)) {
            return '联赛id不合法';
        }
        $matchArr = explode(',', $value);
        foreach($matchArr as $key => $item) {
            if (!is_numeric($item)) {
                return '联赛id不合法';
            }
        }
        return true;
    }

    /**
     * 验证玩法分组
     * @param $value
     * @return bool|string
     */
    public function checkPlayTypeGroup($value) {
        $playTypeGroup = Config::get('common.play_type_group');
        if (!$playTypeGroup[$value]) {
            return '玩法类型错误';
        }
        return true;
    }

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
     * 验证玩法
     * @param $value
     * @return bool|string
     */
    public function checkPlayType($value) {
        $value = Str::snake($value);
        $playTypeAll = Config::get('common.play_type');

        $playTypeArr = [];
        array_map(function ($value) use (&$playTypeArr) {
            $playTypeArr = array_merge($playTypeArr, array_keys($value));
        }, $playTypeAll);
        $playTypeArr = array_unique($playTypeArr);

        if (!in_array($value, $playTypeArr)) {
            return '玩法类型错误';
        }
        return true;
    }

    /**
     * 验证玩法赔率类型
     * @param $value
     * @return bool|string
     */
    public function checkOddsKey($value) {
        $value = Str::snake($value);
        $oddsKey = Config::get('common.odds_key');
        if (!in_array($value, $oddsKey) && !preg_match('/^fs(.*?)/', $value)) {
            return '赔率类型错误';
        }
        return true;
    }

    /**
     * 验证盘口信息
     * @param $value
     * @return bool|string
     */
    public function checkGameInfo($value) {
        $gameInfo = json_decode(htmlspecialchars_decode($value), true);
        if (empty($gameInfo) || !is_array($gameInfo)) {
            return '盘口信息不是合法的json字符串';
        }
        foreach($gameInfo as $item) {
            if (!$item['gameId'] || !is_numeric($item['gameId'])) {
                return '盘口id不合法';
            }

            if (!$item['eventType']) {
                return '赛事类型为空';
            }
            if(true !== ($ret = $this->checkEventType($item['eventType']))) {
                return $ret;
            }

            if (!$item['playType']) {
                return '玩法类型为空';
            }
            if(true !== ($ret = $this->checkPlayType($item['playType']))) {
                return $ret;
            }

            if (!$item['oddsKey']) {
                return '赔率类型为空';
            }
            if(true !== ($ret = $this->checkOddsKey($item['oddsKey']))) {
                return $ret;
            }
        }
        return true;
    }
}