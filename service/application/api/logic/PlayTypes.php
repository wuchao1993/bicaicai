<?php
/**
 * 玩法业务逻辑
 * @createTime 2017/5/24 15:30
 */

namespace app\api\logic;

use think\Cache;
use think\Config;
use think\Model;
use think\Loader;

class PlayTypes extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    public $message;

    /**
     * 判断下注金额限制
     * @param $params
     * @param $toWin 可赢金额
     * @return bool
     */
    public function checkBetLimit($params, $toWin) {
        if ($params['event_type'] == 'in_play_now') {
            $playType = $params['bet_info'][0]['play_type'] . '_in';
        } elseif ($params['event_type'] == 'parlay') {
            $playType = 'parlay';
        } else {
            $playType = $params['bet_info'][0]['play_type'];
        }

        //获取玩法id
        $where = [
            'spt_eng_name' => $playType,
            'spt_st_id' => $params['sport_id']
        ];
        $playTypeId = Loader::model('SportsPlayTypes')
            ->where($where)
            ->field('spt_id')->find();
        if (!$playTypeId->spt_id) {
            return false;
        }

        //获取用户层级
        $info = Loader::model('User')->field('ul_id')->where(['user_id' => USER_ID])->find();

        //用户所属层级该玩法的限额限制
        $where = [
            'sptc_ul_id'  => $info->ul_id,
            'sptc_spt_id' => $playTypeId->spt_id
        ];
        $betLimit = Loader::model('SportsPlayTypesConfig')
            ->where($where)
            ->field('sptc_single_bet_limit_max,sptc_single_bet_limit_min,sptc_single_match_limit_max')->find();
        if (!$betLimit) {
            return true;
        }

        //判断单注最低投注限制
        if (bccomp($params['bet_amount'], $betLimit->sptc_single_bet_limit_min) == -1) {
            $this->message = '单注最低投注限额为' . $betLimit->sptc_single_bet_limit_min . '元';
            return false;
        }

        //判断单注最高投注限制
        if (bccomp($params['bet_amount'], $betLimit->sptc_single_bet_limit_max) == 1) {
            $this->message = '单注最高投注限额为' . $betLimit->sptc_single_bet_limit_max . '元';
            return false;
        }

        //综合过关的sptc_single_match_limit_max表示最大可赢金额；其他的判断单场最高投注限制
        if ($params['event_type'] == 'parlay') {
            if (bccomp($toWin, $betLimit->sptc_single_match_limit_max) == 1) {
                $this->message = '综合过关最大可赢金额为' . $betLimit->sptc_single_match_limit_max . '元';
                return false;
            }
        } else {
            if ($params['play_type'] == 'outright') {
                $where = [
                    'so_source_ids_from' => Config::get('status.order_source_ids_from')['outright'],
                ];
            } else {
                $where = [
                    'so_source_ids_from' => Config::get('status.order_source_ids_from')['schedule'],
                ];
            }
            $where = array_merge($where, [
                'so_user_id'    => USER_ID,
                'so_st_id'      => $params['sport_id'],
                'so_source_ids' => $params['source_ids'],
            ]);

            $betAmountSum = Loader::model('SportsOrders')->where($where)->sum('so_bet_amount');
            if (bccomp(bcadd($betAmountSum, $params['bet_amount']), $betLimit->sptc_single_match_limit_max) == 1) {
                $this->message = '单场最高投注限额为' . $betLimit->sptc_single_match_limit_max . '元';
                return false;
            }
        }

        return true;
    }

    /**
     * 下注限额设置列表
     * @param $sportId 球类id
     * @return array
     */
    public function getBetLimitSetting($sportId) {
        //缓存获取数据
        $cacheSuffix = 'bet_limit_setting_' . USER_ID . '_' . $sportId;
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . $cacheSuffix;
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        //获取用户层级
        $info = Loader::model('User')->field('ul_id')->where(['user_id' => USER_ID])->find();

        $join = [
            ['sports_play_types t', 'c.sptc_spt_id=t.spt_id', 'LEFT']
        ];

        $field = [
            'spt_name AS name',
            'spt_eng_name AS engName',
            'sptc_single_bet_limit_max AS singleBetLimitMax',
            'sptc_single_bet_limit_min AS singleBetLimitMin',
            'sptc_single_match_limit_max AS singleMatchLimitMax'
        ];
        $where = [
            'spt_st_id'  => $sportId,
            'sptc_ul_id' => $info->ul_id
        ];

        $data = Loader::model('SportsPlayTypesConfig')
            ->alias('c')
            ->field($field)
            ->where($where)
            ->join($join)
            ->select();

        Cache::set($cacheKey, $data, Config::get('common.cache_time')['bet_limit_setting']);
        return !$data ? [] : $data;
    }

    /**
     * 获取用户正在下注时的限额设置列表
     * @param $sportId 球类id
     * @param $eventType 赛事类型
     * @return array
     */
    public function getBettingLimitSetting($sportId, $eventType) {
        //缓存获取数据
        $cacheSuffix = 'betting_limit_setting_' . USER_ID . '_' . $sportId . '_' . $eventType;
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . $cacheSuffix;
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        //获取用户层级
        $info = Loader::model('User')->field('ul_id')->where(['user_id' => USER_ID])->find();

        $join = [
            ['sports_play_types t', 'c.sptc_spt_id=t.spt_id', 'LEFT']
        ];

        $field = [
            'spt_name AS name',
            'spt_eng_name AS engName',
            'sptc_single_bet_limit_max AS singleBetLimitMax',
            'sptc_single_bet_limit_min AS singleBetLimitMin',
            'sptc_single_match_limit_max AS singleMatchLimitMax'
        ];
        $where = [
            'spt_st_id'  => $sportId,
            'sptc_ul_id' => $info->ul_id,
        ];
        if ($eventType == 'in_play_now') {
            $where['spt_eng_name'] = ['LIKE', '%_in'];
        } elseif ($eventType == 'parlay') {
            $where['spt_eng_name'] = 'parlay';
        } else {
            $where['spt_eng_name'] = ['NOT LIKE', '%_in'];
        }

        $results = Loader::model('SportsPlayTypesConfig')
            ->alias('c')
            ->field($field)
            ->where($where)
            ->join($join)
            ->select();

        $data = [];
        foreach($results as $key => $val) {
            if ($eventType == 'in_play_now') {
                $val->engName = str_replace('_in', '', $val->engName);
                $data[$val->engName] = $val;
            } else {
                $data[$val->engName] = $val;
            }
        }

        Cache::set($cacheKey, $data, Config::get('common.cache_time')['betting_limit_setting']);
        return !$data ? [] : $data;
    }
}