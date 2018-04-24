<?php
/**
 * 网球数据采集接口
 * @createTime 2017/9/26 15:41
 */

namespace app\collect\service;

use curl\Curlrequest;
use think\Config;

class Tennis {

    /**
     * 滚球
     * @return mixed
     */
    public function collectInPlayNow() {
        $url = Config::get('collect_url')['tennis_in_play_now'];
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
        $url = Config::get('collect_url')['tennis_today'];
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
        $url = Config::get('collect_url')['tennis_early'];
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
        $url = Config::get('collect_url')['tennis_outright'];
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
        $url = Config::get('collect_url')['tennis_result'];

        $data = json_decode(Curlrequest::post($url, ['datetime' => $date]), true);
        if (!$data || $data['errorcode'] != EC_SUCCESS) {
            return [];
        }
        return $data['data'];
    }

    /**
     * 冠军赛果
     * @param string $date
     * @return array|bool
     */
    public function collectResultsOutright($date = '') {
        if ($date && date('Y-m-d', strtotime($date)) !== $date) {
            return false;
        }
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        $url = Config::get('collect_url')['tennis_outright_result'];

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
            if ((empty($playTypeArr) || in_array('stg_handicap', $playTypeArr)) && !empty($datum['stg_handicap'])) {
                $this->handicap($datum['stg_handicap']);
            }
            if ((empty($playTypeArr) || in_array('stg_ou', $playTypeArr)) && !empty($datum['stg_ou'])) {
                $this->ou($datum['stg_ou']);
            }
            if ((empty($playTypeArr) || in_array('stg_gm_ou', $playTypeArr)) && !empty($datum['stg_gm_ou'])) {
                $this->gmOu($datum['stg_gm_ou']);
            }
            if ((empty($playTypeArr) || in_array('stg_parlay_handicap', $playTypeArr)) && !empty($datum['stg_parlay_handicap'])) {
                $this->parlayHandicap($datum['stg_parlay_handicap']);
            }
            if ((empty($playTypeArr) || in_array('stg_parlay_ou', $playTypeArr)) && !empty($datum['stg_parlay_ou'])) {
                $this->parlayOu($datum['stg_parlay_ou']);
            }
            if ((empty($playTypeArr) || in_array('stg_parlay_gm_ou', $playTypeArr)) && !empty($datum['stg_parlay_gm_ou'])) {
                $this->parlayGmOu($datum['stg_parlay_gm_ou']);
            }
        }
    }

    public function handicap(&$data) {
        !empty($data['ior_rh']) && $data['ior_rh'] = bcadd($data['ior_rh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_rc']) && $data['ior_rc'] = bcadd($data['ior_rc'], Config::get('common.adjust_odds_value'));
    }

    public function ou(&$data) {
        !empty($data['ior_ouh']) && $data['ior_ouh'] = bcadd($data['ior_ouh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_ouc']) && $data['ior_ouc'] = bcadd($data['ior_ouc'], Config::get('common.adjust_odds_value'));
    }

    public function gmOu(&$data) {
        !empty($data['ior_ouho']) && $data['ior_ouho'] = bcadd($data['ior_ouho'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_ouhu']) && $data['ior_ouhu'] = bcadd($data['ior_ouhu'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_ouco']) && $data['ior_ouco'] = bcadd($data['ior_ouco'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_oucu']) && $data['ior_oucu'] = bcadd($data['ior_oucu'], Config::get('common.adjust_odds_value'));
    }

    public function parlayHandicap(&$data) {
        !empty($data['ior_rh']) && $data['ior_rh'] = bcadd($data['ior_rh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_rc']) && $data['ior_rc'] = bcadd($data['ior_rc'], Config::get('common.adjust_odds_value'));
    }

    public function parlayOu(&$data) {
        !empty($data['ior_ouh']) && $data['ior_ouh'] = bcadd($data['ior_ouh'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_ouc']) && $data['ior_ouc'] = bcadd($data['ior_ouc'], Config::get('common.adjust_odds_value'));
    }

    public function parlayGmOu(&$data) {
        !empty($data['ior_ouho']) && $data['ior_ouho'] = bcadd($data['ior_ouho'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_ouhu']) && $data['ior_ouhu'] = bcadd($data['ior_ouhu'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_ouco']) && $data['ior_ouco'] = bcadd($data['ior_ouco'], Config::get('common.adjust_odds_value'));
        !empty($data['ior_oucu']) && $data['ior_oucu'] = bcadd($data['ior_oucu'], Config::get('common.adjust_odds_value'));
    }
}