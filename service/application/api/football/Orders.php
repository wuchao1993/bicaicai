<?php
/**
 * 订单业务逻辑
 * @createTime 2017/8/9 14:29
 */

namespace app\api\football;

use think\Config;
use think\Loader;
use think\Model;

class Orders extends \app\common\football\Orders {
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
     * 获取单关足球下注的信息
     * @param $eventType
     * @param $betInfo 二位数组
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
            $gameInfo = Loader::model('SportsFootballOutright')->get($betInfo[0]['game_id']);
            if ($gameInfo->sfo_is_show == Config::get('status.football_outright_is_show')['no']) {
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
            $gameInfo = Loader::model('SportsFootballGames')->get($betInfo[0]['game_id']);
            if ($gameInfo->sfg_is_show == Config::get('status.football_game_is_show')['no']) {
                $this->errorcode = EC_ORDER_GAME_STATUS_CHANGE;
                return false;
            }

            //判断是否从今日变到滚球
            if ($eventType == 'today' && Config::get('status.football_game_event_type_id')[$gameInfo->sfg_event_type] != $eventType) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //判断对阵状态
            $scheduleInfo = Loader::model('SportsFootballSchedules')->get($gameInfo->sfg_sfs_id);
            if ($scheduleInfo->sfs_check_status != Config::get('status.football_schedule_check_status')['normal']) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //判断赔率变化
            $latestOdds = $this->checkScheduleOdds($gameInfo, $betInfo[0], $autoOdds);
            if (true !== $latestOdds) {
                return $latestOdds;
            }

            //获取对阵信息
            //滚球需要记录下注时的进球信息和红牌数等，用来算奖和判断滚球订单是否有效
            if($eventType == 'in_play_now') {
                $betInfo[0]['home_score']  = $scheduleInfo->sfs_home_score;
                $betInfo[0]['guest_score'] = $scheduleInfo->sfs_guest_score;
                $betInfo[0]['home_red']    = $scheduleInfo->sfs_home_red;
                $betInfo[0]['guest_red']   = $scheduleInfo->sfs_guest_red;
                $betInfo[0]['timer']       = $scheduleInfo->sfs_timer;
            }

            $betInfo[0] = $this->getSingleBetPlayTypeInfo($betInfo[0], $gameInfo);
            if (!$betInfo[0]) {
                $this->errorcode = EC_ORDER_PLAY_TYPE_CANCEL;
                return false;
            }

            //记录主盘口id
            $betInfo[0]['master_game_id'] = $scheduleInfo->sfs_master_game_id;

            $sourceIdsFrom = Config::get('status.order_source_ids_from')['schedule'];
            $sourceIds = $gameInfo->sfg_sfs_id;
        }

        return [
            'bet_info'   => $betInfo,
            'source_ids' => $sourceIds,
            'source_ids_from' => $sourceIdsFrom,
        ];
    }

    /**
     * 获取串关足球下注的信息
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
            $gameInfo = Loader::model('SportsFootballGames')->get($info['game_id']);
            if ($gameInfo->sfg_is_show == Config::get('status.football_game_is_show')['no']) {
                $this->errorcode = EC_ORDER_GAME_STATUS_CHANGE;
                return false;
            }

            //是否串关盘口
            if ($gameInfo->sfg_parlay != Config::get('status.football_game_parlay')['yes']) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //判断赔率变化
            $latestOdds = $this->checkScheduleOdds($gameInfo, $info, $autoOdds, 'parlay');
            if (true !== $latestOdds) {
                return $latestOdds;
            }

            //判断对阵状态
            $scheduleInfo = Loader::model('SportsFootballSchedules')
                ->where(['sfs_id' => $gameInfo->sfg_sfs_id])
                ->field('sfs_check_status, sfs_begin_time, sfs_master_game_id')
                ->find();
            if ($scheduleInfo->sfs_check_status != Config::get('status.football_schedule_check_status')['normal']) {
                $this->errorcode = EC_ORDER_SCHEDULE_STATUS_CHANGE;
                return false;
            }

            //记录主盘口id
            $info['master_game_id'] = $scheduleInfo->sfs_master_game_id;

            //获取最大串关数和最小串关数
            $gameInfo->sfg_parlay_min > 0 && $min = max($gameInfo->sfg_parlay_min, $min);
            $gameInfo->sfg_parlay_max > 0 && $max = min($gameInfo->sfg_parlay_max, $max);

            //对阵id
            $scheduleIds[] = $gameInfo->sfg_sfs_id;

            //比赛时间
            $scheduleTime[] = strtotime($scheduleInfo->sfs_begin_time);

            $info = $this->getParlayBetPlayTypeInfo($info, $gameInfo);
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
     * @return array|bool
     */
    public function getSingleBetPlayTypeInfo($betInfo, $gameInfo) {
        $info = [];
        switch($betInfo['play_type']) {
            //记录让球信息
            case 'ft_handicap':
                //判断玩法是否取消，可能下注时sfg_ft_handicap字段已为空
                if (!$gameInfo->sfg_ft_handicap) {
                    return false;
                }
                $ftHandicap     = json_decode($gameInfo->sfg_ft_handicap, true);
                $info['strong'] = $ftHandicap['strong'];
                $info['ratio']  = $ftHandicap['ratio'];
                break;
            //记录大小球信息
            case 'ft_ou':
                if (!$gameInfo->sfg_ft_ou) {
                    return false;
                }
                $ftOu = json_decode($gameInfo->sfg_ft_ou, true);
                //主队的位置对应小球，客队对应大球
                if($betInfo['odds_key'] == OU_UNDER) {
                    $info['ratio'] = $ftOu['ratio_u'];
                } elseif($betInfo['odds_key'] == OU_OVER) {
                    $info['ratio'] = $ftOu['ratio_o'];
                }
                break;
            //记录半场让球信息
            case '1h_handicap':
                if (!$gameInfo->sfg_1h_handicap) {
                    return false;
                }
                $ftHandicap     = json_decode($gameInfo->sfg_1h_handicap, true);
                $info['strong'] = $ftHandicap['hstrong'];
                $info['ratio']  = $ftHandicap['hratio'];
                break;
            //记录半场大小球信息
            case '1h_ou':
                if (!$gameInfo->sfg_1h_ou) {
                    return false;
                }
                $ftOu = json_decode($gameInfo->sfg_1h_ou, true);
                if($betInfo['odds_key'] == OU_1H_UNDER) {
                    $info['ratio'] = $ftOu['hratio_u'];
                } elseif($betInfo['odds_key'] == OU_1H_OVER) {
                    $info['ratio'] = $ftOu['hratio_o'];
                }
                break;
        }
        $betInfo = array_merge($betInfo, $info);
        return $betInfo;
    }

    /**
     * 下注下面几种玩法时需要额外记录一些信息
     * @param $betInfo 下注信息
     * @param $gameInfo 盘口信息
     * @return array|bool
     */
    public function getParlayBetPlayTypeInfo($betInfo, $gameInfo) {
        $info = [];
        switch($betInfo['play_type']) {
            //记录让球信息
            case 'ft_handicap':
                //判断玩法是否取消，可能下注时sfg_ft_handicap字段已为空
                if (!$gameInfo->sfg_parlay_ft_handicap) {
                    return false;
                }
                $ftHandicap     = json_decode($gameInfo->sfg_parlay_ft_handicap, true);
                $info['strong'] = $ftHandicap['strong'];
                $info['ratio']  = $ftHandicap['ratio'];
                break;
            //记录大小球信息
            case 'ft_ou':
                if (!$gameInfo->sfg_parlay_ft_ou) {
                    return false;
                }
                $ftOu = json_decode($gameInfo->sfg_parlay_ft_ou, true);
                //主队的位置对应小球，客队对应大球
                if($betInfo['odds_key'] == OU_UNDER) {
                    $info['ratio'] = $ftOu['ratio_u'];
                } elseif($betInfo['odds_key'] == OU_OVER) {
                    $info['ratio'] = $ftOu['ratio_o'];
                }
                break;
            //记录半场让球信息
            case '1h_handicap':
                if (!$gameInfo->sfg_parlay_1h_handicap) {
                    return false;
                }
                $ftHandicap     = json_decode($gameInfo->sfg_parlay_1h_handicap, true);
                $info['strong'] = $ftHandicap['hstrong'];
                $info['ratio']  = $ftHandicap['hratio'];
                break;
            //记录半场大小球信息
            case '1h_ou':
                if (!$gameInfo->sfg_parlay_1h_ou) {
                    return false;
                }
                $ftOu = json_decode($gameInfo->sfg_parlay_1h_ou, true);
                if($betInfo['odds_key'] == OU_1H_UNDER) {
                    $info['ratio'] = $ftOu['hratio_u'];
                } elseif($betInfo['odds_key'] == OU_1H_OVER) {
                    $info['ratio'] = $ftOu['hratio_o'];
                }
                break;
        }
        $betInfo = array_merge($betInfo, $info);
        return $betInfo;
    }

    /**
     * 判断对阵赔率是否变化
     *
     * @param $gameInfo 盘口信息
     * @param $betInfo 下注信息
     * @param $autoOdds 是否接受较佳赔率
     * @param  string $eventType 赛事类型
     * @return bool|array
     */
    public function checkScheduleOdds($gameInfo, &$betInfo, $autoOdds, $eventType = '') {
        $gameInfo = $gameInfo->toArray();
        $field = Loader::model('Games', 'football')->getPlayTypeField($betInfo['play_type'], $eventType);
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
        if (in_array($betInfo['play_type'], ['ft_handicap', '1h_handicap'])) {
            if (isset($betInfo['ratio_key']) && !empty($betInfo['ratio_key']) && $betInfo['ratio'] != $playTypeInfo[$betInfo['ratio_key']]) {
                $this->errorcode = EC_ORDER_HANDICAP_RATIO_CHANGE;
                return ['ratio' => $playTypeInfo[$betInfo['ratio_key']], 'game_id' => $betInfo['game_id']];
            }
            if ($betInfo['play_type'] == 'ft_handicap') {
                if (isset($betInfo['strong']) && !empty($betInfo['strong']) && strtolower($betInfo['strong']) != strtolower($playTypeInfo['strong'])) {
                    $this->errorcode = EC_ORDER_HANDICAP_STRONG_CHANGE;
                    return ['ratio' => $playTypeInfo[$betInfo['ratio_key']], 'strong' => $playTypeInfo['strong'], 'game_id' => $betInfo['game_id']];
                }
            }
            if ($betInfo['play_type'] == '1h_handicap') {
                if (isset($betInfo['strong']) && !empty($betInfo['strong']) && strtolower($betInfo['strong']) != strtolower($playTypeInfo['hstrong'])) {
                    $this->errorcode = EC_ORDER_HANDICAP_STRONG_CHANGE;
                    return ['ratio' => $playTypeInfo[$betInfo['ratio_key']], 'strong' => $playTypeInfo['hstrong'], 'game_id' => $betInfo['game_id']];
                }
            }
        }

        //大小玩法需要判断球数是否变化
        if (in_array($betInfo['play_type'], ['ft_ou', '1h_ou'])) {
            if (isset($betInfo['ratio_key']) && !empty($betInfo['ratio_key']) && $betInfo['ratio'] != $playTypeInfo[$betInfo['ratio_key']]) {
                $this->errorcode = EC_ORDER_OU_RATIO_CHANGE;
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
     * 判断足球冠军盘口赔率是否变化
     * @param $gameInfo
     * @param $betInfo
     * @param $autoOdds
     * @return bool
     */
    public function checkOutrightOdds($gameInfo, &$betInfo, $autoOdds) {
        $playTypeInfo = json_decode($gameInfo->sfo_odds, true);

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
        $betInfo['odds'] = $playTypeInfo[$betInfo['odds_key']]['odds'];
        return true;
    }
}