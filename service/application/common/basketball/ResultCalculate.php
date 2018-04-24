<?php
/**
 * 每种玩法匹配赛果计算
 * 共有6种状态：输 lose，赢 win，输一半 lose_half，赢一半 win_half，全退 back，比赛异常无效 abnormal
 * @createTime 2017/5/11 16:51
 */

namespace app\common\basketball;

use think\Config;
use think\Model;

class ResultCalculate extends Model {

    /**
     * 计算每种玩法的赛果
     * @param array $game 下注盘口信息 sports_orders表的so_bet_info字段
     * @param array $result 赛果
     * @param int $eventType 赛事类型
     * @return bool|string
     */
    public function calculate($game, $result, $eventType) {
        switch($game['play_type']) {
            case '1x2':
                return $this->calculate1x2($game, $result);
            case 'handicap':
                return $this->calculateHandicap($game, $result);
            case 'ou':
                return $this->calculateOu($game, $result);
            case 'ou_team':
                return $this->calculateOuTeam($game, $result);
            case 'oe':
                return $this->calculateOe($game, $result);
            case 'outright':
                return $this->calculateOutright($game, $result);
            default:
                return false;
        }
    }

    /**
     * 计算全场独赢玩法的赛果
     * @param $game 下注盘口信息
     * @param $result 赛果
     * @return bool|string
     */
    public function calculate1x2($game, $result) {
        if (!empty($game['game_type'])) {
            $homeScore = $result['sbr_home_score_' . $game['game_type']];
            $guestScore = $result['sbr_guest_score_' . $game['game_type']];
        } else {
            $homeScore = $result['sbr_home_score'];
            $guestScore = $result['sbr_guest_score'];
        }

        //空，还没出赛果
        if ($homeScore === '' || $guestScore === '') {
            return false;
        }

        //非数字类型，异常
        if (!is_numeric($homeScore) || !is_numeric($guestScore)) {
            return RESULT_ABNORMAL;
        }
        switch($game['odds_key']) {
            //买主队赢
            case CAPOT_HOME_WIN:
                if ($homeScore > $guestScore) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            //买客队赢
            case CAPOT_GUEST_WIN:
                if ($homeScore < $guestScore) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 单式让球和双式让球的赛果计算
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function calculateHandicap($game, $result) {
        if (!empty($game['game_type'])) {
            $homeScore = $result['sbr_home_score_' . $game['game_type']];
            $guestScore = $result['sbr_guest_score_' . $game['game_type']];
        } else {
            $homeScore = $result['sbr_home_score'];
            $guestScore = $result['sbr_guest_score'];
        }

        //空，还没出赛果
        if ($homeScore === '' || $guestScore === '') {
            return false;
        }

        //非数字类型，异常
        if (!is_numeric($homeScore) || !is_numeric($guestScore)) {
            return RESULT_ABNORMAL;
        }

        //比赛结果加上让球数
        if ($game['strong'] == 'H') {
            $guestScore += $game['ratio'];
        } elseif ($game['strong'] == 'C') {
            $homeScore += $game['ratio'];
        }

        switch($game['odds_key']) {
            //买主队赢
            case HANDICAP_HOME_WIN:
                if ($homeScore == $guestScore) {
                    return RESULT_BACK;
                } elseif ($homeScore > $guestScore) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            //买客队赢
            case HANDICAP_GUEST_WIN:
                if ($homeScore == $guestScore) {
                    return RESULT_BACK;
                } elseif ($homeScore < $guestScore) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 大小球玩法赛果计算
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function calculateOu($game, $result) {
        if (!empty($game['game_type'])) {
            $homeScore = $result['sbr_home_score_' . $game['game_type']];
            $guestScore = $result['sbr_guest_score_' . $game['game_type']];
        } else {
            $homeScore = $result['sbr_home_score'];
            $guestScore = $result['sbr_guest_score'];
        }

        //空，还没出赛果
        if ($homeScore === '' || $guestScore === '') {
            return false;
        }

        //非数字类型，异常
        if (!is_numeric($homeScore) || !is_numeric($guestScore)) {
            return RESULT_ABNORMAL;
        }

        $totalScore = $homeScore + $guestScore;
        $ratio = $game['ratio'];
        switch($game['odds_key']) {
            //买大球
            case OU_OVER:
                if ($totalScore > $ratio) {
                    return RESULT_WIN;
                } elseif ($totalScore < $ratio) {
                    return RESULT_LOSE;
                } elseif ($totalScore == $ratio) {
                    return RESULT_BACK;
                }
                break;
            //买小球
            case OU_UNDER:
                if ($totalScore < $ratio) {
                    return RESULT_WIN;
                } elseif ($totalScore > $ratio) {
                    return RESULT_LOSE;
                } elseif ($totalScore == $ratio) {
                    return RESULT_BACK;
                }
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 球队得分大小玩法赛果计算
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function calculateOuTeam($game, $result) {
        if (!empty($game['game_type'])) {
            $homeScore = $result['sbr_home_score_' . $game['game_type']];
            $guestScore = $result['sbr_guest_score_' . $game['game_type']];
        } else {
            $homeScore = $result['sbr_home_score'];
            $guestScore = $result['sbr_guest_score'];
        }

        //空，还没出赛果
        if ($homeScore === '' || $guestScore === '') {
            return false;
        }

        //非数字类型，异常
        if (!is_numeric($homeScore) || !is_numeric($guestScore)) {
            return RESULT_ABNORMAL;
        }

        $ratio = $game['ratio'];
        switch($game['odds_key']) {
            //主队大
            case OUH_OVER:
                if ($homeScore <= $ratio) {
                    return RESULT_LOSE;
                }
                return RESULT_WIN;
                break;
            //主队小
            case OUH_UNDER:
                if ($homeScore >= $ratio) {
                    return RESULT_LOSE;
                }
                return RESULT_WIN;
                break;
            //客队大
            case OUC_OVER:
                if ($guestScore <= $ratio) {
                    return RESULT_LOSE;
                }
                return RESULT_WIN;
                break;
            //客队小
            case OUC_UNDER:
                if ($guestScore >= $ratio) {
                    return RESULT_LOSE;
                }
                return RESULT_WIN;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 单双玩法赛果计算
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function calculateOe($game, $result) {
        if (!empty($game['game_type'])) {
            $homeScore = $result['sbr_home_score_' . $game['game_type']];
            $guestScore = $result['sbr_guest_score_' . $game['game_type']];
        } else {
            $homeScore = $result['sbr_home_score'];
            $guestScore = $result['sbr_guest_score'];
        }

        //空，还没出赛果
        if ($homeScore === '' || $guestScore === '') {
            return false;
        }

        //非数字类型，异常
        if (!is_numeric($homeScore) || !is_numeric($guestScore)) {
            return RESULT_ABNORMAL;
        }

        $totalScore = $homeScore + $guestScore;
        switch($game['odds_key']) {
            //全场买单
            case OE_ODD:
                //偶数输
                if ($totalScore % 2 == 0) {
                    return RESULT_LOSE;
                }
                return RESULT_WIN;
                break;
            //全场买双
            case OE_EVEN:
                //偶数赢
                if ($totalScore % 2 == 0) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 冠军赛果计算
     * @param $game
     * @param $result
     * @return string
     */
    public function calculateOutright($game, $result) {
        $result = json_decode($result, true);
        if (in_array($game['team'], $result)) {
            return RESULT_WIN;
        }
        return RESULT_LOSE;
    }
}