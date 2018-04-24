<?php
/**
 * 每种玩法匹配赛果计算
 * 共有6种状态：输 lose，赢 win，输一半 lose_half，赢一半 win_half，全退 back，比赛异常无效 abnormal
 * @createTime 2017/5/11 16:51
 */

namespace app\common\football;

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
            case 'ft1x2':
                return $this->calculate1x2($game, $result);
            case '1h1x2':
                return $this->calculate1x21H($game, $result);
            case 'ft_handicap':
            case '1h_handicap':
                return $this->calculateHandicap($game, $result, $eventType);
            case 'ft_ou':
            case '1h_ou':
                return $this->calculateOu($game, $result);
            case 'ft_oe':
            case '1h_oe':
                return $this->calculateOe($game, $result);
            case 'ft_correct_score':
            case '1h_correct_score':
                return $this->calculateCorrectScore($game, $result);
                break;
            case 'ft_total_goals':
            case '1h_total_goals':
                return $this->calculateTotalGoals($game, $result);
            case 'ht_ft':
                return $this->calculateHtFt($game, $result);
                break;
            case 'outright':
                return $this->calculateOutright($game, $result);
                break;
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
        //空，还没出赛果
        if ($result['sfr_home_score'] === '' || $result['sfr_guest_score'] === '') {
            return false;
        }

        if (!is_numeric($result['sfr_home_score']) || !is_numeric($result['sfr_guest_score'])) {
            return RESULT_ABNORMAL;
        }
        switch($game['odds_key']) {
            //买主队赢
            case CAPOT_HOME_WIN:
                if ($result['sfr_home_score'] > $result['sfr_guest_score']) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            //买客队赢
            case CAPOT_GUEST_WIN:
                if ($result['sfr_home_score'] < $result['sfr_guest_score']) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            //买和局
            case CAPOT_TIE:
                if ($result['sfr_home_score'] == $result['sfr_guest_score']) {
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
     * 计算半场独赢玩法的赛果
     * @param $game 下注盘口信息
     * @param $result 赛果
     * @return bool|string
     */
    public function calculate1x21H($game, $result) {
        //空，还没出赛果
        if ($result['sfr_home_score_1h'] === '' || $result['sfr_guest_score_1h'] === '') {
            return false;
        }

        //不为空，但是不是数字类型，可能是'赛事延时'等状态
        if (!is_numeric($result['sfr_home_score_1h']) || !is_numeric($result['sfr_guest_score_1h'])) {
            return RESULT_ABNORMAL;
        }
        switch($game['odds_key']) {
            //半场买主队赢
            case CAPOT_1H_HOME_WIN:
                if ($result['sfr_home_score_1h'] > $result['sfr_guest_score_1h']) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            //半场买客队赢
            case CAPOT_1H_GUEST_WIN:
                if ($result['sfr_home_score_1h'] < $result['sfr_guest_score_1h']) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            //半场买和局
            case CAPOT_1H_TIE:
                if ($result['sfr_home_score_1h'] == $result['sfr_guest_score_1h']) {
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
     * @param $eventType
     * @return bool|string
     */
    public function calculateHandicap($game, $result, $eventType) {
        if ($game['play_type'] == 'ft_handicap') {
            //空，还没出赛果
            if ($result['sfr_home_score'] === '' || $result['sfr_guest_score'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score']) || !is_numeric($result['sfr_guest_score'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score'];
            $result['guest_score'] = $result['sfr_guest_score'];
        }
        if ($game['play_type'] == '1h_handicap') {
            //空，还没出赛果
            if ($result['sfr_home_score_1h'] === '' || $result['sfr_guest_score_1h'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score_1h']) || !is_numeric($result['sfr_guest_score_1h'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score_1h'];
            $result['guest_score'] = $result['sfr_guest_score_1h'];
        }

        //滚球注单从下注时的比分开始算
        if ($eventType == Config::get('status.order_event_type')['in_play_now']) {
            $result['home_score'] -= $game['home_score'];
            $result['guest_score'] -= $game['guest_score'];
        }

        $guestScore = $result['guest_score'];
        $homeScore  = $result['home_score'];

        //让球分为单式和双式，带'/'的是双式
        //单式让球计算方法
        if (false === strpos($game['ratio'], '/')) {
            //比赛结果加上让球数
            if ($game['strong'] == 'H') {
                $result['guest_score'] += $game['ratio'];
            } elseif ($game['strong'] == 'C') {
                $result['home_score'] += $game['ratio'];
            }

            return $this->handicapScoreCompare($game, $result);
        } else {
            //双式让球的计算方法
            list($r1, $r2) = explode('/', $game['ratio']);

            //第一个让球结果
            if ($game['strong'] == 'H') { //主队让球
                $result['guest_score'] = $guestScore + $r1;
            } elseif ($game['strong'] == 'C') { //客队让球
                $result['home_score'] = $homeScore + $r1;
            }
            $r1Result = $this->handicapScoreCompare($game, $result);

            //第二个让球结果
            if ($game['strong'] == 'H') { //主队让球
                $result['guest_score'] = $guestScore + $r2;
            } elseif ($game['strong'] == 'C') {
                $result['home_score'] = $homeScore + $r2;
            }
            $r2Result = $this->handicapScoreCompare($game, $result);

            $resultArr = [$r1Result, $r2Result];

            //判断输一半，赢一半的情况
            if ($r1Result == $r2Result) {
                return $r1Result; // win, lose, back
            } elseif (in_array(RESULT_BACK, $resultArr) && in_array(RESULT_WIN, $resultArr)) {
                return RESULT_WIN_HALF;
            } elseif (in_array(RESULT_BACK, $resultArr) && in_array(RESULT_LOSE, $resultArr)) {
                return RESULT_LOSE_HALF;
            } else {
                //没有一个win，一个lose的情况
                return false;
            }
        }
    }

    /**
     * 计算让球玩法输赢结果
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function handicapScoreCompare($game, $result) {
        switch($game['odds_key']) {
            //买主队赢
            case HANDICAP_HOME_WIN:
            case HANDICAP_1H_HOME_WIN:
                if ($result['home_score'] == $result['guest_score']) {
                    return RESULT_BACK;
                } elseif ($result['home_score'] > $result['guest_score']) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
                break;
            //买客队赢
            case HANDICAP_GUEST_WIN:
            case HANDICAP_1H_GUEST_WIN:
                if ($result['home_score'] == $result['guest_score']) {
                    return RESULT_BACK;
                } elseif ($result['home_score'] < $result['guest_score']) {
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
        if ($game['play_type'] == 'ft_ou') {
            //空，还没出赛果
            if ($result['sfr_home_score'] === '' || $result['sfr_guest_score'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score']) || !is_numeric($result['sfr_guest_score'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score'];
            $result['guest_score'] = $result['sfr_guest_score'];
        }
        if ($game['play_type'] == '1h_ou') {
            //空，还没出赛果
            if ($result['sfr_home_score_1h'] === '' || $result['sfr_guest_score_1h'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score_1h']) || !is_numeric($result['sfr_guest_score_1h'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score_1h'];
            $result['guest_score'] = $result['sfr_guest_score_1h'];
        }

        //大小球分为单式和双式，带'/'的是双式
        if (false === strpos($game['ratio'], '/')) {
            return $this->ouScoreCompare($game, $result);
        } else {
            //双式大小球的计算方法
            list($r1, $r2) = explode('/', $game['ratio']);
            $game['ratio'] = $r1;
            $r1Result = $this->ouScoreCompare($game, $result);

            $game['ratio'] = $r2;
            $r2Result = $this->ouScoreCompare($game, $result);

            $resultArr = [$r1Result, $r2Result];

            //判断输，赢，输一半，赢一半的情况
            if ($r1Result == $r2Result) {
                return $r1Result; // win, lose
            } elseif (in_array(RESULT_BACK, $resultArr) && in_array(RESULT_WIN, $resultArr)) {
                return RESULT_WIN_HALF;
            } elseif (in_array(RESULT_BACK, $resultArr) && in_array(RESULT_LOSE, $resultArr)) {
                return RESULT_LOSE_HALF;
            } else {
                //没有一个win，一个lose的情况
                return false;
            }
        }
    }

    /**
     * 大小球玩法赛果比分比较
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function ouScoreCompare($game, $result) {
        $totalScore = $result['home_score'] + $result['guest_score'];
        $ratio = $game['ratio'];
        switch($game['odds_key']) {
            //买大球
            case OU_OVER:
            case OU_1H_OVER:
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
            case OU_1H_UNDER:
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
     * 单双玩法赛果计算
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function calculateOe($game, $result) {
        if ($game['play_type'] == 'ft_oe') {
            //空，还没出赛果
            if ($result['sfr_home_score'] === '' || $result['sfr_guest_score'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score']) || !is_numeric($result['sfr_guest_score'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score'];
            $result['guest_score'] = $result['sfr_guest_score'];
        }
        if ($game['play_type'] == '1h_oe') {
            //空，还没出赛果
            if ($result['sfr_home_score_1h'] === '' || $result['sfr_guest_score_1h'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score_1h']) || !is_numeric($result['sfr_guest_score_1h'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score_1h'];
            $result['guest_score'] = $result['sfr_guest_score_1h'];
        }

        $totalScore = $result['home_score'] + $result['guest_score'];
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
            //TODO 半场单双
            default:
                return false;
                break;
        }
    }

    /**
     * 波胆玩法的赛果计算
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function calculateCorrectScore($game, $result) {
        if ($game['play_type'] == 'ft_correct_score') {
            //空，还没出赛果
            if ($result['sfr_home_score'] === '' || $result['sfr_guest_score'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score']) || !is_numeric($result['sfr_guest_score'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score'];
            $result['guest_score'] = $result['sfr_guest_score'];
        }
        if ($game['play_type'] == '1h_correct_score') {
            //空，还没出赛果
            if ($result['sfr_home_score_1h'] === '' || $result['sfr_guest_score_1h'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score_1h']) || !is_numeric($result['sfr_guest_score_1h'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score_1h'];
            $result['guest_score'] = $result['sfr_guest_score_1h'];
        }

        //其他比分
        if ($game['odds_key'] == CORRECT_SCORE_OVH || $game['odds_key'] == CORRECT_SCORE_OVC) {
            $score = $result['home_score'] . '-' . $result['guest_score'];
            if ($game['play_type'] == 'ft_correct_score') {
                if (!in_array($score, Config::get('common.ft_correct_score'))) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            } elseif ($game['play_type'] == '1h_correct_score') {
                if (!in_array($score, Config::get('common.1h_correct_score'))) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            }
        } else {
            if(preg_match_all('/ior_h([\d]{1})c([\d]{1})/i', $game['odds_key'], $matches)) {
                $homeScore  = $matches[1][0];
                $guestScore = $matches[2][0];
                if ($result['home_score'] == $homeScore && $result['guest_score'] == $guestScore) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            } else {
                return false;
            }
        }
    }

    /**
     * 总进球玩法赛果计算
     * @param $game
     * @param $result
     * @return string
     */
    public function calculateTotalGoals($game, $result) {
        if ($game['play_type'] == 'ft_total_goals') {
            //空，还没出赛果
            if ($result['sfr_home_score'] === '' || $result['sfr_guest_score'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score']) || !is_numeric($result['sfr_guest_score'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score'];
            $result['guest_score'] = $result['sfr_guest_score'];
        }
        if ($game['play_type'] == '1h_total_goals') {
            //空，还没出赛果
            if ($result['sfr_home_score_1h'] === '' || $result['sfr_guest_score_1h'] === '') {
                return false;
            }

            //赛果出异常的情况
            if (!is_numeric($result['sfr_home_score_1h']) || !is_numeric($result['sfr_guest_score_1h'])) {
                return RESULT_ABNORMAL;
            }
            $result['home_score'] = $result['sfr_home_score_1h'];
            $result['guest_score'] = $result['sfr_guest_score_1h'];
        }

        $totalScore = $result['home_score'] + $result['guest_score'];
        if ($game['odds_key'] == TOTAL_GOALS_OVER) {
            if ($totalScore >= 7) {
                return RESULT_WIN;
            }
            return RESULT_LOSE;
        } elseif($game['odds_key'] == TOTAL_GOALS_1H_OVER) {
            if ($totalScore >= 3) {
                return RESULT_WIN;
            }
            return RESULT_LOSE;
        } elseif(preg_match('/ior_t([\d]{2})/i', $game['odds_key'], $matches)) {
            if ($totalScore >= $matches[1][0] && $totalScore <= $matches[1][1]) {
                return RESULT_WIN;
            }
            return RESULT_LOSE;
        } elseif(preg_match('/ior_ht([\d]{1})/i', $game['odds_key'], $matches)) {
            if ($matches[1] == $totalScore) {
                return RESULT_WIN;
            }
            return RESULT_LOSE;
        }
    }

    /**
     * 半场/全场的赛果计算
     * @param $game
     * @param $result
     * @return bool|string
     */
    public function calculateHtFt($game, $result) {
        //空，还没出赛果
        if ($result['sfr_home_score'] === '' || $result['sfr_guest_score'] === '' ||
            $result['sfr_home_score_1h'] === '' || $result['sfr_guest_score_1h'] === '') {
            return false;
        }

        //赛果出异常的情况
        if (!is_numeric($result['sfr_home_score']) || !is_numeric($result['sfr_guest_score']) ||
            !is_numeric($result['sfr_home_score_1h']) || !is_numeric($result['sfr_guest_score_1h'])) {
            return RESULT_ABNORMAL;
        }

        switch($game['odds_key']) {
            //主/主
            case HT_FT_HOME_HOME:
                if ($result['sfr_home_score_1h'] > $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] > $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            //主/和
            case HT_FT_HOME_TIE:
                if ($result['sfr_home_score_1h'] > $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] == $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            //主/客
            case HT_FT_HOME_GUEST:
                if ($result['sfr_home_score_1h'] > $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] < $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            //和/主
            case HT_FT_TIE_HOME:
                if ($result['sfr_home_score_1h'] == $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] > $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            //和/和
            case HT_FT_TIE_TIE:
                if ($result['sfr_home_score_1h'] == $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] == $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            //和/客
            case HT_FT_TIE_GUEST:
                if ($result['sfr_home_score_1h'] == $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] < $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            //客/主
            case HT_FT_GUEST_HOME:
                if ($result['sfr_home_score_1h'] < $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] > $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            //客/和
            case HT_FT_GUEST_TIE:
                if ($result['sfr_home_score_1h'] < $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] == $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            //客/客
            case HT_FT_GUEST_GUEST:
                if ($result['sfr_home_score_1h'] < $result['sfr_guest_score_1h'] &&
                    $result['sfr_home_score'] < $result['sfr_guest_score']
                ) {
                    return RESULT_WIN;
                }
                return RESULT_LOSE;
            default:
                return false;
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