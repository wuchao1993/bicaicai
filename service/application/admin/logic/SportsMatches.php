<?php
/**
 * 联赛管理业务逻辑
 * @createTime 2017/7/01 14:29
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class SportsMatches extends Model {
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
     * 获取对阵列表
     * @param $params
     * @return array
     */
    public function getList($params) {
        switch($params['sport_type']) {
            //足球
            case 'football' :
                return $this->getFootballList($params);
            case 'basketball' :
                return $this->getBasketballList($params);
            case 'tennis' :
                return $this->getTennisList($params);
            default :
                return ['total_count' => 0, 'list' => []];
        }
    }

    /**
     * 获取列表
     * @param $params
     * @return array
     */
    public function getFootballList($params) {
        $where = [];
        !$params['page'] && $params['page'] = 1;
        !$params['page_size'] && $params['page_size'] = $this->pageSize;
                            
        $params['is_hot'] && $where['sfm_is_hot'] = Config::get('status.football_match_is_hot')[$params['is_hot']];
        $where['sfm_name']  = ['like', '%' . $params["search_word"] . '%'];

        //计算总数
        $total = Loader::model('SportsFootballMatches')->where($where)->count();

        if (!$total) {
            return ['total_count' => 0, 'list' => []];
        }

        $ret = Loader::model('SportsFootballMatches')->where($where)
            ->order(['sfm_sort' => 'desc', 'sfm_id' => 'asc'])
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
                $k = str_replace('sfm_', '', $k);
                $data[$key][$k] = $v;
            });

            $data[$key]['is_hot'] = Config::get('status.football_match_is_hot_id')[$item['sfm_is_hot']];
        }

        return ['total_count' => $total, 'list' => $data];
    }

    /**
     * 获取列表
     * @param $params
     * @return array
     */
    public function getBasketballList($params) {
        $where = [];
        !$params['page'] && $params['page'] = 1;
        !$params['page_size'] && $params['page_size'] = $this->pageSize;
        $params['is_hot'] && $where['sbm_is_hot'] = Config::get('status.basketball_match_is_hot')[$params['is_hot']];
        $where['sbm_name']  = ['like', '%' . $params["search_word"] . '%'];

        //计算总数
        $total = Loader::model('SportsBasketballMatches')->where($where)->count();
        if (!$total) {
            return ['total_count' => 0, 'list' => []];
        }

        $ret = Loader::model('SportsBasketballMatches')->where($where)
            ->order(['sbm_sort' => 'desc', 'sbm_id' => 'asc'])
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
                $k = str_replace('sbm_', '', $k);
                $data[$key][$k] = $v;
            });

            $data[$key]['is_hot'] = Config::get('status.basketball_match_is_hot_id')[$item['sbm_is_hot']];
        }

        return ['total_count' => $total, 'list' => $data];
    }



    /**
     * 获取网球联赛列表
     * @param $params
     * @return array
     */
    public function getTennisList($params) {
        $where = [];
        !$params['page'] && $params['page'] = 1;
        !$params['page_size'] && $params['page_size'] = $this->pageSize;
        $params['is_hot'] && $where['stm_is_hot'] = Config::get('status.tennis_match_is_hot')[$params['is_hot']];
        $where['stm_name']  = ['like', '%' . $params["search_word"] . '%'];

        //计算总数
        $total = Loader::model('SportsTennisMatches')->where($where)->count();
        if (!$total) {
            return ['total_count' => 0, 'list' => []];
        }

        $ret = Loader::model('SportsTennisMatches')->where($where)
            ->order(['stm_sort' => 'desc', 'stm_id' => 'asc'])
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
                $k = str_replace('stm_', '', $k);
                $data[$key][$k] = $v;
            });

            $data[$key]['is_hot'] = Config::get('status.tennis_match_is_hot_id')[$item['stm_is_hot']];
        }

        return ['total_count' => $total, 'list' => $data];
    }

    /**
     * 修改排序
     * @param $sportType
     * @param $id
     * @param $sort
     * @return bool
     */
    public function updateSort($sportType, $id, $sort) {
        switch($sportType) {
            case 'football':
                $ret = Loader::model('SportsFootballMatches')->where(['sfm_id' => $id])->update(['sfm_sort' => $sort]);
                if (false === $ret) {
                    $this->errorcode = EC_AD_UPDATE_SORT_ERROR;
                    return false;
                }
                return true;
            case 'basketball':
                $ret = Loader::model('SportsBasketballMatches')->where(['sbm_id' => $id])->update(['sbm_sort' => $sort]);
                if (false === $ret) {
                    $this->errorcode = EC_AD_UPDATE_SORT_ERROR;
                    return false;
                }
                return true;
            case 'tennis':
                $ret = Loader::model('SportsTennisMatches')->where(['stm_id' => $id])->update(['stm_sort' => $sort]);
                if (false === $ret) {
                    $this->errorcode = EC_AD_UPDATE_SORT_ERROR;
                    return false;
                }
                return true;
        }
    }

    /**
     * 修改是否热门
     * @param $sportType
     * @param $id
     * @param $isHot yes or no
     * @return bool
     */
    public function updateHot($sportType, $id, $isHot) {
        switch($sportType) {
            case 'football':
                $ret = Loader::model('SportsFootballMatches')
                    ->where(['sfm_id' => $id])
                    ->update(['sfm_is_hot' => Config::get('status.football_match_is_hot')[$isHot]]);
                if (false === $ret) {
                    $this->errorcode = EC_AD_UPDATE_HOT_ERROR;
                    return false;
                }
                return true;
            case 'basketball':
                $ret = Loader::model('SportsBasketballMatches')
                    ->where(['sbm_id' => $id])
                    ->update(['sbm_is_hot' => Config::get('status.basketball_match_is_hot')[$isHot]]);
                if (false === $ret) {
                    $this->errorcode = EC_AD_UPDATE_HOT_ERROR;
                    return false;
                }
                return true;
            case 'tennis':
                $ret = Loader::model('SportsTennisMatches')
                    ->where(['stm_id' => $id])
                    ->update(['stm_is_hot' => Config::get('status.tennis_match_is_hot')[$isHot]]);
                if (false === $ret) {
                    $this->errorcode = EC_AD_UPDATE_HOT_ERROR;
                    return false;
                }
                return true;
        }
    }
}