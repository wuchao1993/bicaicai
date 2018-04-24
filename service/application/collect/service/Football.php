<?php
/**
 * 足球数据采集接口
 * @createTime 2017/4/25 15:41
 */

namespace app\collect\service;

use curl\Curlrequest;
use think\Config;
use think\Log;

class Football {

    /**
     * 滚球
     * @return mixed
     */
    public function collectInPlayNow() {
        $url = Config::get('collect_url')['football_in_play_now'];
        $data = json_decode(Curlrequest::post($url), true);
        if (!$data || $data['errorcode'] != EC_SUCCESS) {
            return [];
        }

        $this->adjustOdds([], $data['data']);
        return $data['data'];
    }

    /**
     * 今日
     * @return mixed
     */
    public function collectToday() {
        $url = Config::get('collect_url')['football_today'];
        $data = json_decode(Curlrequest::post($url), true);
        if (!$data || $data['errorcode'] != EC_SUCCESS) {
            return [];
        }

        $this->adjustOdds([], $data['data']);
        return $data['data'];
    }

    /**
     * 早盘
     * @return mixed
     */
    public function collectEarly() {
        $url = Config::get('collect_url')['football_early'];
        $data = json_decode(Curlrequest::post($url), true);
        if (!$data || $data['errorcode'] != EC_SUCCESS) {
            return [];
        }

        $this->adjustOdds([], $data['data']);
        return $data['data'];
    }

    /**
     * 冠军
     * @return mixed
     */
    public function collectOutright() {
        $url = Config::get('collect_url')['football_outright'];
        $data = json_decode(Curlrequest::post($url), true);
        if (!$data || $data['errorcode'] != EC_SUCCESS) {
            return [];
        }
        return $data['data'];
    }

    /**
     * 赛果
     * @param string $date 2017-05-01 日期
     * @return mixed
     */
    public function collectResults($date = '') {
        if ($date && date('Y-m-d', strtotime($date)) !== $date) {
            return false;
        }
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $url = Config::get('collect_url')['football_result'];

        $data = json_decode(Curlrequest::post($url, ['datetime' => $date]), true);
        if (!$data || $data['errorcode'] != EC_SUCCESS) {
            return [];
        }
        return $data['data'];
    }

    /**
     * 冠军赛果
     * @param string $date 2017-05-01 日期
     * @return mixed
     */
    public function collectResultsOutright($date = '') {
        if ($date && date('Y-m-d', strtotime($date)) !== $date) {
            return false;
        }
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $url = Config::get('collect_url')['football_outright_result'];

        $data = json_decode(Curlrequest::post($url, ['datetime' => $date]), true);
        if (!$data || $data['errorcode'] != EC_SUCCESS) {
            return [];
        }
        return $data['data'];
    }

    /**
     * 调整赔率，把指定玩法的赔率进行调整
     * @param $playTypeArr
     * @param $data
     */
    public function adjustOdds($playTypeArr, &$data) {
        bcscale(2);
        foreach($data as &$datum) {
            if ((empty($playTypeArr) || in_array('sfg_ft_handicap', $playTypeArr)) && !empty($datum['sfg_ft_handicap'])) {
                $this->ftHandicap($datum['sfg_ft_handicap']);
            }
            if ((empty($playTypeArr) || in_array('sfg_1h_handicap', $playTypeArr)) && !empty($datum['sfg_1h_handicap'])) {
                $this->htHandicap($datum['sfg_1h_handicap']);
            }
            if ((empty($playTypeArr) || in_array('sfg_ft_ou', $playTypeArr)) && !empty($datum['sfg_ft_ou'])) {
                $this->ftOu($datum['sfg_ft_ou']);
            }
            if ((empty($playTypeArr) || in_array('sfg_1h_ou', $playTypeArr)) && !empty($datum['sfg_1h_ou'])) {
                $this->htOu($datum['sfg_1h_ou']);
            }
            if ((empty($playTypeArr) || in_array('sfg_ft_oe', $playTypeArr)) && !empty($datum['sfg_ft_oe'])) {
                $this->ftOe($datum['sfg_ft_oe']);
            }
            if ((empty($playTypeArr) || in_array('sfg_parlay_ft_handicap', $playTypeArr)) && !empty($datum['sfg_parlay_ft_handicap'])) {
                $this->parlayFtHandicap($datum['sfg_parlay_ft_handicap']);
            }
            if ((empty($playTypeArr) || in_array('sfg_parlay_ft_ou', $playTypeArr)) && !empty($datum['sfg_parlay_ft_ou'])) {
                $this->parlayFtOu($datum['sfg_parlay_ft_ou']);
            }
            if ((empty($playTypeArr) || in_array('sfg_parlay_ft_oe', $playTypeArr)) && !empty($datum['sfg_parlay_ft_oe'])) {
                $this->parlayFtOe($datum['sfg_parlay_ft_oe']);
            }
            if ((empty($playTypeArr) || in_array('sfg_parlay_1h_handicap', $playTypeArr)) && !empty($datum['sfg_parlay_1h_handicap'])) {
                $this->parlay1hHandicap($datum['sfg_parlay_1h_handicap']);
            }
            if ((empty($playTypeArr) || in_array('sfg_parlay_1h_ou', $playTypeArr)) && !empty($datum['sfg_parlay_1h_ou'])) {
                $this->parlay1hOu($datum['sfg_parlay_1h_ou']);
            }
        }
    }

    public function ftHandicap(&$data) {
        !empty($data['ior_rh']) && $data['ior_rh'] = bcadd($data['ior_rh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_rc']) && $data['ior_rc'] = bcadd($data['ior_rc'], Config::get('common.adjust_odds_value'));
    }

    public function htHandicap(&$data) {
        !empty($data['ior_hrh']) && $data['ior_hrh'] = bcadd($data['ior_hrh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_hrc']) && $data['ior_hrc'] = bcadd($data['ior_hrc'], Config::get('common.adjust_odds_value'));
    }

    public function ftOu(&$data) {
        !empty($data['ior_ouh']) && $data['ior_ouh'] = bcadd($data['ior_ouh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_ouc']) && $data['ior_ouc'] = bcadd($data['ior_ouc'], Config::get('common.adjust_odds_value'));
    }

    public function htOu(&$data) {
        !empty($data['ior_houh']) && $data['ior_houh'] = bcadd($data['ior_houh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_houc']) && $data['ior_houc'] = bcadd($data['ior_houc'], Config::get('common.adjust_odds_value'));
    }

    public function ftOe(&$data) {
        !empty($data['ior_eoo']) && $data['ior_eoo'] = bcadd($data['ior_eoo'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_eoe']) && $data['ior_eoe'] = bcadd($data['ior_eoe'], Config::get('common.adjust_odds_value'));
    }

    public function parlayFtHandicap(&$data) {
        !empty($data['ior_rh']) && $data['ior_rh'] = bcadd($data['ior_rh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_rc']) && $data['ior_rc'] = bcadd($data['ior_rc'], Config::get('common.adjust_odds_value'));
    }

    public function parlayFtOu(&$data) {
        !empty($data['ior_ouh']) && $data['ior_ouh'] = bcadd($data['ior_ouh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_ouc']) && $data['ior_ouc'] = bcadd($data['ior_ouc'], Config::get('common.adjust_odds_value'));
    }

    public function parlayFtOe(&$data) {
        !empty($data['ior_eoo']) && $data['ior_eoo'] = bcadd($data['ior_eoo'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_eoe']) && $data['ior_eoe'] = bcadd($data['ior_eoe'], Config::get('common.adjust_odds_value'));
    }

    public function parlay1hHandicap(&$data) {
        !empty($data['ior_hrh']) && $data['ior_hrh'] = bcadd($data['ior_hrh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_hrc']) && $data['ior_hrc'] = bcadd($data['ior_hrc'], Config::get('common.adjust_odds_value'));
    }

    public function parlay1hOu(&$data) {
        !empty($data['ior_houh']) && $data['ior_houh'] = bcadd($data['ior_houh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_houc']) && $data['ior_houc'] = bcadd($data['ior_houc'], Config::get('common.adjust_odds_value'));
    }
}