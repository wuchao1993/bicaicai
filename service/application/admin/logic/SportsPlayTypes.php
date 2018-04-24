<?php
/**
 * 玩法管理
 * @createTime 2017/7/12 14:29
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class SportsPlayTypes extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 每页显示数量
     * @var int
     */
    public $pageSize = 20;

    /**
     * 获取玩法列表
     * @param $params
     * @return array
     */
    public function getList($params) {
        $where = [];
        !$params['page']      && $params['page'] = 1;
        !$params['page_size'] && $params['page_size'] = $this->pageSize;
        $params['sport_id']   && $where['spt_st_id'] = $params['sport_id'];

        //计算总数
        $total = Loader::model('SportsPlayTypes')->where($where)->count();
        if (!$total) {
            return ['total_count' => 0, 'list' => []];
        }

        $ret = Loader::model('SportsPlayTypes')->where($where)
            ->order('spt_id asc')
            ->field('spt_id,spt_name,spt_single_bet_limit_min,spt_eng_name,spt_single_bet_limit_max,spt_single_match_limit_max')
            ->page($params['page'], $params['page_size'])
            ->select();
        if (!$ret) {
            return ['total_count' => 0, 'list' => []];
        }

        //数据处理
        $data = [];
        foreach($ret as $key => $item) {
            $item = $item->toArray();

            //去掉表前缀
            array_walk($item, function($v, $k) use (&$data, $key) {
                $k = str_replace('spt_', '', $k);
                $data[$key][$k] = $v;
            });
        }

        return ['total_count' => $total, 'list' => $data];
    }

    /**
     * 修改限额
     * @param $playTypeId
     * @param $betLimit
     * @param $matchLimit
     * @return bool
     */
    public function updateLimit($playTypeId, $betLimitMax, $matchLimit, $betLimitMin) {
        if (empty($playTypeId)) {
           $this->errorcode = EC_AD_UPDATE_PLAY_TYPE_LIMIT_ERROR;
           return false;
        }
        $update = [
            'spt_single_bet_limit_max'   => $betLimitMax,
            'spt_single_bet_limit_min'   => $betLimitMin,
            'spt_single_match_limit_max' => $matchLimit,
            'spt_modify_time'            => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('SportsPlayTypes')->where(['spt_id' => $playTypeId])->update($update);
        if (false === $ret) {
            $this->errorcode = EC_AD_UPDATE_PLAY_TYPE_LIMIT_ERROR;
        }
        return true;
    }

    /**
     * 批量修改限额
     * @param $playTypeId
     * @param $betLimit
     * @param $matchLimit
     * @return bool
     */
    public function updateAllLimit($betLimitMax, $matchLimit, $betLimitMin, $sportId){
        $update = [
            'spt_single_bet_limit_max'   => $betLimitMax,
            'spt_single_bet_limit_min'   => $betLimitMin,
            'spt_single_match_limit_max' => $matchLimit,
            'spt_modify_time'            => date('Y-m-d H:i:s'),
        ];
        $ret = Loader::model('SportsPlayTypes')->where(['spt_st_id' => $sportId])->update($update); 
        if (false === $ret) {
            $this->errorcode = EC_AD_UPDATE_PLAY_TYPE_LIMIT_ERROR;
        }
        return true;               
    }
}