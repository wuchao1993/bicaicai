<?php
/**
 * 订单业务逻辑
 * @createTime 2017/8/9 14:29
 */

namespace app\api\basketball;

use think\Config;
use think\Loader;

class Orders extends \app\common\basketball\Orders {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 获取足球下注的盘口信息
     * @param $params
     * @return array|bool|mixed
     */
    public function getBetGameInfo($params) {
        if($params['event_type'] == 'parlay') {
            $betGameInfo = $this->getBetGameInfoParlay($params['bet_info'], $params['auto_odds']);
        } else {
            $betGameInfo = $this->getBetGameInfoSingle($params['event_type'], $params['bet_info'], $params['auto_odds']);
        }
        return $betGameInfo;
    }

    /**
     * 获取单关篮球下注的信息
     * @param $eventType
     * @param $betInfo 二维数组
     * [
     *     'game_id' : 盘口id,
     *     'play_type' : 玩法,
     *     'odds_key' : 玩法的赔率key值,
     *     'odds' : 赔率,
     * ]
     * @param $autoOdds
     * @return array|bool
     */
    public function getBetGameInfoSingle($eventType, $betInfo, $autoOdds) {
        if ($betInfo[0]['play_type'] == 'outright') {
            //获取盘口信息
            $gameInfo = Loader::model('SportsBasketballOutright')->get($betInfo[0]['game_id']);
            if ($gameInfo->sbo_is_show == Config::get('status.basketball_outright_is_show')['no']) {
                $this->errorcode = EC_ORDER_GAME_STATUS_CHANGE;
                return false;
            }

            //判断赔率变化
            $latestOdds = $this->checkOutrightOdds($gameInfo, $betInfo[0], $autoOdds);
            if (true !== $latestOdds) {
                return ['odds' => $latestOdds, 'game_id' => $betInfo[0]['game_id']];
            }

            $sourceIdsFrom = Config::get('status.order_source_ids_from')['outright'];
            $sourceIds = $betInfo[0]['game_id'];
        } else {
            //获取盘口信息
            $gameInfo = Loader::model('SportsBasketballGames')->get($betInfo[0]['game_id']);
            if ($gameInfo->sbg_is_show == Config::get('status.basketball_game_is_show')['no']) {
                $this->errorcode = EC_ORDER_GAME_STATUS_CHANGE;
                return false;
            }

            //判断是否从今日变到滚球
            if ($eventType == 'today' && Config::get('status.basketball_game_event_type_id')[$gameInfo->sbg_event_type] != $eventType) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //判断对阵状态
            $scheduleInfo = Loader::model('SportsBasketballSchedules')->get($gameInfo->sbg_sbs_id);
            if ($scheduleInfo->sbs_check_status != Config::get('status.basketball_schedule_check_status')['normal']) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //判断赔率变化
            $latestOdds = $this->checkScheduleOdds($gameInfo, $betInfo[0], $autoOdds);
            if (true !== $latestOdds) {
                return $latestOdds;
            }

            //获取对阵信息
            //滚球需要记录下注时的信息
            if($eventType == 'in_play_now') {
                $betInfo[0]['home_score']  = $scheduleInfo->sbs_home_score;
                $betInfo[0]['guest_score'] = $scheduleInfo->sbs_guest_score;
                $betInfo[0]['timer']       = $scheduleInfo->sbs_timer;
                $betInfo[0]['quarter']     = $scheduleInfo->sbs_quarter;
            }

            //记录盘口类型
            $betInfo[0]['game_type'] = $gameInfo->sbg_game_type;

            //记录主盘口id
            $betInfo[0]['master_game_id'] = $scheduleInfo->sbs_master_game_id;

            $betInfo[0] = $this->getBetPlayTypeInfo($betInfo[0], $gameInfo);
            if (!$betInfo[0]) {
                $this->errorcode = EC_ORDER_PLAY_TYPE_CANCEL;
                return false;
            }
            $sourceIdsFrom = Config::get('status.order_source_ids_from')['schedule'];
            $sourceIds = $gameInfo->sbg_sbs_id;
        }

        return [
            'bet_info'   => $betInfo,
            'source_ids' => $sourceIds,
            'source_ids_from' => $sourceIdsFrom,
        ];
    }

    /**
     * 获取串关篮球下注的信息
     * @param $betInfo
     * @param $autoOdds
     * @return array|bool
     */
    public function getBetGameInfoParlay($betInfo, $autoOdds) {
        $min = Config::get('common.parlay_count')['min']; //初始化最小串关数
        $max = Config::get('common.parlay_count')['max']; //初始化最大串关数
        $scheduleTime = $scheduleIds = [];
        foreach($betInfo as &$info) {
            //获取盘口信息
            $gameInfo = Loader::model('SportsBasketballGames')->get($info['game_id']);
            if ($gameInfo->sbg_is_show == Config::get('status.basketball_game_is_show')['no']) {
                $this->errorcode = EC_ORDER_GAME_STATUS_CHANGE;
                return false;
            }

            //是否串关盘口
            if ($gameInfo->sbg_parlay != Config::get('status.basketball_game_parlay')['yes']) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //判断赔率变化
            $latestOdds = $this->checkScheduleOdds($gameInfo, $info, $autoOdds, 'parlay');
            if (true !== $latestOdds) {
                return $latestOdds;
            }

            //判断对阵状态
            $scheduleInfo = Loader::model('SportsBasketballSchedules')
                ->where(['sbs_id' => $gameInfo->sbg_sbs_id])
                ->field('sbs_check_status, sbs_begin_time, sbs_master_game_id')
                ->find();
            if ($scheduleInfo->sbs_check_status != Config::get('status.basketball_schedule_check_status')['normal']) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //获取最大串关数和最小串关数
            $gameInfo->sbg_parlay_min > 0 && $min = max($gameInfo->sbg_parlay_min, $min);
            $gameInfo->sbg_parlay_max > 0 && $max = min($gameInfo->sbg_parlay_max, $max);

            //对阵id
            $scheduleIds[] = $gameInfo->sbg_sbs_id;

            //比赛时间
            $scheduleTime[] = strtotime($scheduleInfo->sbs_begin_time);

            //记录盘口类型
            $info['game_type'] = $gameInfo->sbg_game_type;

            //记录主盘口id
            $info['master_game_id'] = $scheduleInfo->sbs_master_game_id;

            $info = $this->getBetPlayTypeInfo($info, $gameInfo, 'parlay');
            if (!$info) {
                $this->errorcode = EC_ORDER_PLAY_TYPE_CANCEL;
                return false;
            }
        }

        //判断最小串关数和最大串关数
        if(count($betInfo) < $min) {
            $this->errorcode = EC_ORDER_PARLAY_MIN_ERROR;
            return false;
        }
        if(count($betInfo) > $max) {
            $this->errorcode = EC_ORDER_PARLAY_MAX_ERROR;
            return false;
        }

        //判断串关时间跨度
        //读取配置
        $siteConfig = Loader::model('SiteConfig')->getConfig('sports', 'common', 'parlay_day_span');

        $minTime = min($scheduleTime);
        $maxTime = max($scheduleTime);
        if (($maxTime - $minTime) / 86400 > $siteConfig['parlay_day_span']) {
            $this->errorcode = EC_ORDER_PARLAY_DAY_SPAN_ERROR;
            return false;
        }

        //同场比赛不能串在一起
        if (count($scheduleIds) != count(array_unique($scheduleIds))) {
            $this->errorcode = EC_ORDER_PARLAY_SAME_GAME_NOT_ALLOW;
            return false;
        }

        //组合对阵id
        $sourceIds = implode(',', $scheduleIds);

        return [
            'bet_info'   => $betInfo,
            'source_ids' => $sourceIds,
            'source_ids_from' => Config::get('status.order_source_ids_from')['schedule'],
        ];
    }

    /**
     * 下注下面几种玩法时需要额外记录一些信息
     * @param $betInfo 下注信息
     * @param $gameInfo 盘口信息
     * @param string $eventType 赛事类型
     * @return array|bool
     */
    public function getBetPlayTypeInfo($betInfo, $gameInfo, $eventType = '') {
        $info = [];
        switch($betInfo['play_type']) {
            //记录让球信息
            case 'handicap':
                //判断玩法是否取消，可能下注时sbg_handicap字段已为空
                if ($eventType == 'parlay') {
                    $playTypeInfo = $gameInfo->sbg_parlay_handicap;
                } else {
                    $playTypeInfo = $gameInfo->sbg_handicap;
                }
                if (empty($playTypeInfo)) {
                    return false;
                }
                $handicap       = json_decode($playTypeInfo, true);
                $info['strong'] = $handicap['strong'];
                $info['ratio']  = $handicap['ratio'];
                break;
            //记录大小球信息
            case 'ou':
                if ($eventType == 'parlay') {
                    $playTypeInfo = $gameInfo->sbg_parlay_ou;
                } else {
                    $playTypeInfo = $gameInfo->sbg_ou;
                }
                if (empty($playTypeInfo)) {
                    return false;
                }
                $ou = json_decode($playTypeInfo, true);
                //主队的位置对应小球，客队对应大球
                if($betInfo['odds_key'] == OU_UNDER) {
                    $info['ratio'] = $ou['ratio_u'];
                } elseif($betInfo['odds_key'] == OU_OVER) {
                    $info['ratio'] = $ou['ratio_o'];
                }
                break;
            //记录大小球信息
            case 'ou_team':
                if ($eventType == 'parlay') {
                    $playTypeInfo = $gameInfo->sbg_parlay_ou_team;
                } else {
                    $playTypeInfo = $gameInfo->sbg_ou_team;
                }
                if (empty($playTypeInfo)) {
                    return false;
                }

                $ou = json_decode($playTypeInfo, true);
                if($betInfo['odds_key'] == OUH_UNDER) {
                    $info['ratio'] = $ou['ratio_ouhu'];
                } elseif($betInfo['odds_key'] == OUH_OVER) {
                    $info['ratio'] = $ou['ratio_ouho'];
                } elseif($betInfo['odds_key'] == OUC_UNDER) {
                    $info['ratio'] = $ou['ratio_oucu'];
                } elseif($betInfo['odds_key'] == OUC_OVER) {
                    $info['ratio'] = $ou['ratio_ouco'];
                }
                break;
        }
        $betInfo = array_merge($betInfo, $info);
        return $betInfo;
    }

    /**
     * 判断对阵赔率是否变化
     * @param $gameInfo 盘口信息
     * @param $betInfo 下注信息
     * @param $autoOdds 是否接受较佳赔率
     * @param  string $eventType 赛事类型
     * @return bool|array
     */
    public function checkScheduleOdds($gameInfo, $betInfo, $autoOdds, $eventType = '') {
        $gameInfo = $gameInfo->toArray();
        $field = Loader::model('Games', 'basketball')->getPlayTypeField($betInfo['play_type'], $eventType);
        if (!$gameInfo[$field]) {
            $this->errorcode = EC_ORDER_GAME_STATUS_CHANGE;
            return false;
        }
        $playTypeInfo = json_decode($gameInfo[$field], true);
        if (!isset($playTypeInfo[$betInfo['odds_key']])) {
            $this->errorcode = EC_ORDER_GAME_STATUS_CHANGE;
            return false;
        }

        //让球玩法需要判断让球数是否变化
        if ($betInfo['play_type'] == 'handicap') {
            if (isset($betInfo['ratio_key']) && !empty($betInfo['ratio_key']) && $betInfo['ratio'] != $playTypeInfo[$betInfo['ratio_key']]) {
                $this->errorcode = EC_ORDER_HANDICAP_RATIO_CHANGE;
                return ['ratio' => $playTypeInfo[$betInfo['ratio_key']], 'game_id' => $betInfo['game_id']];
            }
            if (isset($betInfo['strong']) && !empty($betInfo['strong']) && strtolower($betInfo['strong']) != strtolower($playTypeInfo['strong'])) {
                $this->errorcode = EC_ORDER_HANDICAP_STRONG_CHANGE;
                return ['ratio' => $playTypeInfo[$betInfo['ratio_key']], 'strong' => $playTypeInfo['strong'], 'game_id' => $betInfo['game_id']];
            }
        }

        //大小玩法需要判断球数是否变化
        if ($betInfo['play_type'] == 'ou') {
            if (isset($betInfo['ratio_key']) && !empty($betInfo['ratio_key']) && $betInfo['ratio'] != $playTypeInfo[$betInfo['ratio_key']]) {
                $this->errorcode = EC_ORDER_OU_RATIO_CHANGE;
                return ['ratio' => $playTypeInfo[$betInfo['ratio_key']], 'game_id' => $betInfo['game_id']];
            }
        }

        //球队得分大小玩法需要判断球数是否变化
        if ($betInfo['play_type'] == 'ou_team') {
            if (isset($betInfo['ratio_key']) && !empty($betInfo['ratio_key']) && $betInfo['ratio'] != $playTypeInfo[$betInfo['ratio_key']]) {
                $this->errorcode = EC_ORDER_OU_TEAM_RATIO_CHANGE;
                return ['ratio' => $playTypeInfo[$betInfo['ratio_key']], 'game_id' => $betInfo['game_id']];
            }
        }

        if ($autoOdds == 'no') {
            if ($playTypeInfo[$betInfo['odds_key']] != $betInfo['odds']) {
                $this->errorcode = EC_ORDER_ODDS_CHANGE;
                return ['odds' => $playTypeInfo[$betInfo['odds_key']], 'game_id' => $betInfo['game_id']];
            }
        } elseif ($autoOdds == 'yes') {
            if ($playTypeInfo[$betInfo['odds_key']] < $betInfo['odds']) {
                $this->errorcode = EC_ORDER_ODDS_CHANGE;
                return ['odds' => $playTypeInfo[$betInfo['odds_key']], 'game_id' => $betInfo['game_id']];
            }
            $betInfo['odds'] = $playTypeInfo[$betInfo['odds_key']];
        }
        return true;
    }

    /**
     * 判断篮球冠军盘口赔率是否变化
     * @param $gameInfo
     * @param $betInfo
     * @param $autoOdds
     * @return bool
     */
    public function checkOutrightOdds($gameInfo, $betInfo, $autoOdds) {
        $playTypeInfo = json_decode($gameInfo->sbo_odds, true);
        if ($autoOdds == 'no') {
            if (!isset($playTypeInfo[$betInfo['odds_key']]) || $playTypeInfo[$betInfo['odds_key']]['odds'] != $betInfo['odds']) {
                $this->errorcode = EC_ORDER_ODDS_CHANGE;
                return $playTypeInfo[$betInfo['odds_key']]['odds'];
            }
        } elseif ($autoOdds == 'yes') {
            if (!isset($playTypeInfo[$betInfo['odds_key']]) || $playTypeInfo[$betInfo['odds_key']]['odds'] < $betInfo['odds']) {
                $this->errorcode = EC_ORDER_ODDS_CHANGE;
                return $playTypeInfo[$betInfo['odds_key']]['odds'];
            }
        }
        return true;
    }
}