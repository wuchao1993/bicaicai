<?php
/**
 * 赛果业务逻辑
 * @createTime 2017/4/25 14:46
 */

namespace app\common\logic;

use think\Loader;
use think\Model;

class Results extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 赛果详情
     * @param $sport
     * @param $type 'match' or 'outright'
     * @param $gameId
     * @return array|bool
     */
    public function getInfo($sport, $type, $gameId) {
        switch($sport) {
            case 'football' :
                return $this->getFootballResultInfo($type, $gameId);
                break;
            case 'basketball' :
                return $this->getBasketballResultInfo($type, $gameId);
                break;
            case 'tennis' :
                return $this->getTennisResultInfo($type, $gameId);
                break;
            default :
                return false;
                break;
        }
    }

    /**
     * 获取足球赛果详情
     * @param $type match 联赛，outright 冠军
     * @param $gameId
     * @return array
     */
    public function getFootballResultInfo($type, $gameId) {
        $info = [];
        if ($type == 'match') {
            $data = Loader::model('Results', 'football')->getInfoByGameId($gameId);
            if ($data) {
                $info['game_id']        = $data['sfr_game_id'];
                $info['match_id']       = $data['sfr_sfm_id'];
                $info['begin_time']     = $data['sfr_begin_time'];
                $info['game_type']      = $data['sfr_game_type'];
                $matchInfo              = Loader::model('Matches', 'football')->getInfoById($data['sfr_sfm_id']);
                $info['match_name']     = isset($matchInfo['sfm_name']) ? $matchInfo['sfm_name'] : '';
                $teamInfo               = Loader::model('Teams', 'football')->getInfoById($data['sfr_home_id']);
                $info['home_name']      = isset($teamInfo['sft_name']) ? $teamInfo['sft_name'] : '';
                $teamInfo               = Loader::model('Teams', 'football')->getInfoById($data['sfr_guest_id']);
                $info['guest_name']     = isset($teamInfo['sft_name']) ? $teamInfo['sft_name'] : '';
                $info['result']         = [
                    'home_score'     => $data['sfr_home_score'],
                    'guest_score'    => $data['sfr_guest_score'],
                    'home_score_1h'  => $data['sfr_home_score_1h'],
                    'guest_score_1h' => $data['sfr_guest_score_1h'],
                ];
            }
        } elseif ($type == 'outright') {
            $data = Loader::model('Outright', 'football')->getInfoByGameId($gameId);
            if ($data) {
                $matchInfo = Loader::model('Matches', 'football')->getInfoById($data['sfo_sfm_id']);
                $info['game_id']    = $gameId;
                $info['match_name'] = isset($matchInfo['sfm_name']) ? $matchInfo['sfm_name'] : '';
                $info['game_type']  = $data['sfo_game_type'];
                $info['end_time']   = $data['sfo_end_time'];
                $info['result']     = json_decode($data['sfo_result'], true);

                //处理完整的赛果
                $odds = json_decode($data['sfo_odds'], true);
                foreach($odds as $odd) {
                    if (in_array($odd['team'], $info['result'])) {
                        $info['result_whole'][] = ['team' => $odd['team'], 'win' => true];
                    } else {
                        $info['result_whole'][] = ['team' => $odd['team'], 'win' => false];
                    }
                }
            }
        }
        return $info;
    }

    /**
     * 获取篮球赛果详情
     * @param $type match 联赛，outright 冠军
     * @param $gameId
     * @return array
     */
    public function getBasketballResultInfo($type, $gameId) {
        $info = [];
        if ($type == 'match') {
            $data = Loader::model('Results', 'basketball')->getInfoByGameId($gameId);
            if ($data) {
                $info['game_id']    = $data['sbr_game_id'];
                $info['match_id']   = $data['sbr_sbm_id'];
                $info['begin_time'] = $data['sbr_begin_time'];
                $info['game_type']  = $data['sbr_game_type'];
                $matchInfo          = Loader::model('Matches', 'basketball')->getInfoById($data['sbr_sbm_id']);
                $info['match_name'] = isset($matchInfo['sbm_name']) ? $matchInfo['sbm_name'] : '';
                $teamInfo           = Loader::model('Teams', 'basketball')->getInfoById($data['sbr_home_id']);
                $info['home_name']  = isset($teamInfo['sbt_name']) ? $teamInfo['sbt_name'] : '';
                $teamInfo           = Loader::model('Teams', 'basketball')->getInfoById($data['sbr_guest_id']);
                $info['guest_name'] = isset($teamInfo['sbt_name']) ? $teamInfo['sbt_name'] : '';
                $info['result']     = [
                    'home_score'     => $data['sbr_home_score'],
                    'guest_score'    => $data['sbr_guest_score'],
                    'home_score_1h'  => $data['sbr_home_score_1h'],
                    'guest_score_1h' => $data['sbr_guest_score_1h'],
                    'home_score_2h'  => $data['sbr_home_score_2h'],
                    'guest_score_2h' => $data['sbr_guest_score_2h'],
                    'home_score_ot'  => $data['sbr_home_score_ot'],
                    'guest_score_ot' => $data['sbr_guest_score_ot'],
                    'home_score_1q'  => $data['sbr_home_score_1q'],
                    'guest_score_1q' => $data['sbr_guest_score_1q'],
                    'home_score_2q'  => $data['sbr_home_score_2q'],
                    'guest_score_2q' => $data['sbr_guest_score_2q'],
                    'home_score_3q'  => $data['sbr_home_score_3q'],
                    'guest_score_3q' => $data['sbr_guest_score_3q'],
                    'home_score_4q'  => $data['sbr_home_score_4q'],
                    'guest_score_4q' => $data['sbr_guest_score_4q'],
                ];
            }
        } elseif ($type == 'outright') {
            $data = Loader::model('Outright', 'basketball')->getInfoByGameId($gameId);
            if ($data) {
                $matchInfo = Loader::model('Matches', 'basketball')->getInfoById($data['sbo_sbm_id']);
                $info['game_id']    = $gameId;
                $info['match_name'] = isset($matchInfo['sbm_name']) ? $matchInfo['sbm_name'] : '';
                $info['game_type']  = $data['sbo_game_type'];
                $info['end_time']   = $data['sbo_end_time'];
                $info['result']     = json_decode($data['sbo_result'], true);

                //处理完整的赛果
                $odds = json_decode($data['sbo_odds'], true);
                foreach($odds as $odd) {
                    if (in_array($odd['team'], $info['result'])) {
                        $info['result_whole'][] = ['team' => $odd['team'], 'win' => true];
                    } else {
                        $info['result_whole'][] = ['team' => $odd['team'], 'win' => false];
                    }
                }
            }
        }
        return $info;
    }

    /**
     * 获取网球赛果详情
     * @param $type match 联赛，outright 冠军
     * @param $gameId
     * @return array
     */
    public function getTennisResultInfo($type, $gameId) {
        $info = [];
        if ($type == 'match') {
            $data = Loader::model('Results', 'tennis')->getInfoByGameId($gameId);
            if ($data) {
                $info['game_id']    = $data['str_game_id'];
                $info['match_id']   = $data['str_stm_id'];
                $info['begin_time'] = $data['str_begin_time'];
                $info['game_type']  = $data['str_game_type'];
                $matchInfo          = Loader::model('Matches', 'tennis')->getInfoById($data['str_stm_id']);
                $info['match_name'] = isset($matchInfo['stm_name']) ? $matchInfo['stm_name'] : '';
                $teamInfo           = Loader::model('Teams', 'tennis')->getInfoById($data['str_home_id']);
                $info['home_name']  = isset($teamInfo['stt_name']) ? $teamInfo['stt_name'] : '';
                $teamInfo           = Loader::model('Teams', 'tennis')->getInfoById($data['str_guest_id']);
                $info['guest_name'] = isset($teamInfo['stt_name']) ? $teamInfo['stt_name'] : '';
                $info['result']     = [
                    'home_score'           => $data['str_home_score'],
                    'guest_score'          => $data['str_guest_score'],
                    'home_score_1st'       => $data['str_home_score_1st'],
                    'guest_score_1st'      => $data['str_guest_score_1st'],
                    'home_score_2nd'       => $data['str_home_score_2nd'],
                    'guest_score_2nd'      => $data['str_guest_score_2nd'],
                    'home_score_3rd'       => $data['str_home_score_3rd'],
                    'guest_score_3rd'      => $data['str_guest_score_3rd'],
                    'home_score_4th'       => $data['str_home_score_4th'],
                    'guest_score_4th'      => $data['str_guest_score_4th'],
                    'home_score_5th'       => $data['str_home_score_5th'],
                    'guest_score_5th'      => $data['str_guest_score_5th'],
                    'home_score_handicap'  => $data['str_home_score_handicap'],
                    'guest_score_handicap' => $data['str_guest_score_handicap'],
                    'home_score_ou'        => $data['str_home_score_ou'],
                    'guest_score_ou'       => $data['str_guest_score_ou'],
                ];
            }
        } elseif ($type == 'outright') {
            $data = Loader::model('Outright', 'tennis')->getInfoByGameId($gameId);
            if ($data) {
                $matchInfo = Loader::model('Matches', 'tennis')->getInfoById($data['sto_stm_id']);
                $info['game_id']    = $gameId;
                $info['match_name'] = isset($matchInfo['stm_name']) ? $matchInfo['stm_name'] : '';
                $info['game_type']  = $data['sto_game_type'];
                $info['end_time']   = $data['sto_end_time'];
                $info['result']     = json_decode($data['sto_result'], true);

                //处理完整的赛果
                $odds = json_decode($data['sto_odds'], true);
                foreach($odds as $odd) {
                    if (in_array($odd['team'], $info['result'])) {
                        $info['result_whole'][] = ['team' => $odd['team'], 'win' => true];
                    } else {
                        $info['result_whole'][] = ['team' => $odd['team'], 'win' => false];
                    }
                }
            }
        }
        return $info;
    }
}