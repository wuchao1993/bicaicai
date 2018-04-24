<?php
/**
 * 算奖逻辑
 * @createTime 2017/5/8 14:56
 */

namespace app\common\tennis;

use think\Config;
use think\Loader;
use think\Log;
use think\Model;

class BonusCalculate extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 计算篮球综合过关的奖金
     * @param $uid 用户id
     * @param $games 下注盘口信息
     * @param $betAmount 下注金额
     * @param $eventType 赛事类型
     * @return array|bool
     */
    public function calculateParlay($uid, $games, $betAmount, $eventType) {
        $parlayOdds = 1;
        $haveResult = 0; //有几个盘口已经出了赛果
        $abnormalResult = 0; //有几个盘口异常
        bcscale(10); //计算精度
        foreach($games as &$game) {
            //获取赛果
            if(!isset($game['calculate_result']) || !$game['calculate_result']) {
                $gameRet = $this->getCalculateResult($game, $eventType);
                if(!$gameRet || !$gameRet['calculate_result']) {
                    continue;
                }
                $game = $gameRet;
            }

            switch($game['calculate_result']) {
                //赢
                case RESULT_WIN:
                    $parlayOdds = bcmul($parlayOdds, $game['odds']);
                    break;
                //赢一半
                case RESULT_WIN_HALF:
                    //(1 + ($game['odds'] - 1) / 2)
                    $parlayOdds = bcmul($parlayOdds, bcadd(1, bcdiv(bcsub($game['odds'], 1), 2)));
                    break;
                //本金全输，输了也要继续计算剩下的比赛，不要中断。
                case RESULT_LOSE:
                    $parlayOdds = 0;
                    break;
                //退还一半本金
                case RESULT_LOSE_HALF:
                    $parlayOdds = bcmul($parlayOdds, 0.5);
                    break;
                //退还本金
                case RESULT_BACK:
                    $parlayOdds = bcmul($parlayOdds, 1);
                    break;
                //异常
                case RESULT_ABNORMAL:
                    $parlayOdds = bcmul($parlayOdds, 1);
                    $abnormalResult++;
                    break;
                default:
                    //跳过当前foreach循环
                    continue;
                    break;
            }
            $haveResult++;
        }

        //都未出赛果返回false
        if($haveResult == 0) {
            return false;
        }

        //获取用户层级返水比例
        $rebateInfo = Loader::model('User', 'logic')->getRebateByUid($uid);

        //综合过关订单里面的所有盘口都出赛果了才计算奖金
        bcscale(3); //计算精度
        $bonus = $betStatus = $rebateAmount = $rebateRatio = 0;
        $status = Config::get('status.order_status')['wait'];
        if(count($games) == $haveResult) {
            $bonus = bcmul($betAmount, $parlayOdds);
            if(bccomp($bonus, $betAmount) == 0) {
                $betStatus = Config::get('status.order_bet_status')['back'];
                if ($rebateInfo) {
                    $rebateAmount = bcmul($betAmount, $rebateInfo[RESULT_BACK]);
                    $rebateRatio  = $rebateInfo[RESULT_BACK];
                }
            } elseif(bccomp($bonus, 0) > 0) {
                $betStatus = Config::get('status.order_bet_status')['win'];
                if ($rebateInfo) {
                    $rebateAmount = bcmul($betAmount, $rebateInfo[RESULT_WIN]);
                    $rebateRatio  = $rebateInfo[RESULT_WIN];
                }
            } elseif(bccomp($bonus, 0) <= 0) {
                $betStatus = Config::get('status.order_bet_status')['lose'];
                if ($rebateInfo) {
                    $rebateAmount = bcmul($betAmount, $rebateInfo[RESULT_LOSE]);
                    $rebateRatio  = $rebateInfo[RESULT_LOSE];
                }
            }
            $status = Config::get('status.order_status')['clearing'];
        }

        //如果订单全部是 RESULT_ABNORMAL 则订单更改为ABNORMAL状态
        if (count($games) == $abnormalResult) {
            $bonus = $betStatus = $rebateAmount = $rebateRatio = 0;
            $status = Config::get('status.order_status')['result_abnormal'];
        }

        return [
            'bonus'         => $bonus,
            'rebate_amount' => $rebateAmount,
            'rebate_ratio'  => $rebateRatio,
            'bet_status'    => $betStatus,
            'status'        => $status,
            'bet_info'      => $games
        ];
    }

    /**
     * 计算篮球单关的奖金
     * @param $uid 用户id
     * @param $game 下注盘口信息
     * @param $betAmount 下注金额
     * @param $eventType 赛事类型
     * @return array|bool
     */
    public function calculateSingle($uid, $game, $betAmount, $eventType) {
        //获取赛果
        $game = $this->getCalculateResult($game, $eventType);
        if(!$game || !$game['calculate_result']) {
            return false;
        }

        //单关以下这几种玩法的赔率都没有加上本金
        $noPrincipal = Config::get('common.no_principal_play_type');

        //获取用户层级返水比例
        $rebateInfo = Loader::model('User', 'logic')->getRebateByUid($uid);
        $rebateAmount = $rebateRatio = 0;

        switch($game['calculate_result']) {
                //赢
            case RESULT_WIN:
                if(in_array($game['play_type'], $noPrincipal)) {
                    $bonus = bcadd($betAmount, bcmul($betAmount, $game['odds']));
                } else {
                    $bonus = bcmul($betAmount, $game['odds']);
                }

                if ($rebateInfo) {
                    $rebateAmount = bcmul($betAmount, $rebateInfo[RESULT_WIN]);
                    $rebateRatio  = $rebateInfo[RESULT_WIN];
                }
                break;
                //赢一半
            case RESULT_WIN_HALF:
                if(in_array($game['play_type'], $noPrincipal)) {
                    $bonus = bcmul(bcadd(bcdiv($game['odds'], 2), 1), $betAmount);
                } else {
                    $bonus = bcmul(bcadd(bcdiv(bcsub($game['odds'], 1), 2), 1), $betAmount);
                }

                if ($rebateInfo) {
                    $rebateAmount = bcmul($betAmount, $rebateInfo[RESULT_WIN_HALF]);
                    $rebateRatio  = $rebateInfo[RESULT_WIN_HALF];
                }
                break;
            case RESULT_LOSE:
                //本金全输
                $bonus = 0;
                if ($rebateInfo) {
                    $rebateAmount = bcmul($betAmount, $rebateInfo[RESULT_LOSE]);
                    $rebateRatio  = $rebateInfo[RESULT_LOSE];
                }
                break;
            case RESULT_LOSE_HALF:
                //退还一半本金
                $bonus = bcdiv($betAmount, 2);
                if ($rebateInfo) {
                    $rebateAmount = bcmul($betAmount, $rebateInfo[RESULT_LOSE_HALF]);
                    $rebateRatio  = $rebateInfo[RESULT_LOSE_HALF];
                }
                break;
            case RESULT_BACK:
                //退还本金
                $bonus = $betAmount;
                if ($rebateInfo) {
                    $rebateAmount = bcmul($betAmount, $rebateInfo[RESULT_BACK]);
                    $rebateRatio  = $rebateInfo[RESULT_BACK];
                }
                break;
            default:
                return false;
        }

        //注单输赢状态
        if ($game['calculate_result'] == RESULT_ABNORMAL) {
            $bonus = $betStatus = $rebateAmount = $rebateRatio = 0;
            $status = Config::get('status.order_status')['result_abnormal'];
        } else {
            $betStatus = Config::get('status.order_bet_status')[$game['calculate_result']];
            $status = Config::get('status.order_status')['clearing'];
        }

        //转成二维数组入库
        $betInfo[] = $game;

        return [
            'bonus'         => $bonus,
            'rebate_amount' => $rebateAmount,
            'rebate_ratio'  => $rebateRatio,
            'bet_status'    => $betStatus,
            'status'        => $status,
            'bet_info'      => $betInfo
        ];
    }

    /**
     * 计算赛果
     * @param $game 下注盘口信息
     * @param $eventType 赛事类型
     * @return bool|array
     */
    public function getCalculateResult($game, $eventType) {
        //获取赛果
        if($game['play_type'] == 'outright') {
            $outrightInfo = Loader::model('SportsTennisOutright')->find($game['game_id']);
            if(!$outrightInfo['sto_result']) {
                return false;
            }
            $result       = $outrightInfo['sto_result'];
            $odds         = json_decode($outrightInfo['sto_odds'], true);
            $game['team'] = $odds[$game['odds_key']]['team'];
        } else {
            //获取主盘口id
            if (!$game['master_game_id']) {
                $masterGameId = Loader::model('Games', 'tennis')->getMasterGameIdByGameId($game['game_id']);
                if(!$masterGameId) {
                    return false;
                }
                $game['master_game_id'] = $masterGameId;
            } else {
                $masterGameId = $game['master_game_id'];
            }

            //获取每个盘口的赛果,两种方式获取盘口的赛果
            //1. 通过下注的盘口id获取主盘口id，再根据主盘口id获取赛果
            //2. 通过对阵id和盘口类型获取赛果
            $result = Loader::model('SportsTennisResults')->find($masterGameId);
            if(!$result) {
                return false;
            }
        }

        //计算赛果
        $game['calculate_result'] = Loader::model('ResultCalculate', 'tennis')->calculate($game, $result, $eventType);

        //写日志
        if (!$game['calculate_result']) {
            Log::record(__METHOD__ .  ' gameInfo: ' . var_export($game, true) . ' result: ' . var_export($result, true), APP_LOG_TYPE);
        }
        return $game;
    }
}