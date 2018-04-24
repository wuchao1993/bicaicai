<?php
/**
 * 公告管理
 * @createTime 2017/7/20 14:29
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Model;
use think\Cache;

class SportsNotice extends Model {
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
     * 获取足球对阵列表
     * @param $params
     * @return array
     */
    public function getCollected($params) {
        $where = [];
        !$params['page']      && $params['page'] = 1;
        !$params['page_size'] && $params['page_size'] = $this->pageSize;
        $params['date']       && $where['scn_date'] = $params['date'];

        //计算总数
        $total = Loader::model('SportsCollectNotice')->where($where)->count();
        if (!$total) {
            return ['total_count' => 0, 'list' => []];
        }

        $ret = Loader::model('SportsCollectNotice')->where($where)
            ->order('scn_create_time desc')
            ->field('scn_content,scn_date')
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
                $k = str_replace('scn_', '', $k);
                $data[$key][$k] = $v;
            });
        }

        return ['total_count' => $total, 'list' => $data];
    }
}