<?php
/**
 * 体育项目
 * @createTime 2017/5/10 14:17
 */

namespace app\common\logic;

use think\Loader;
use think\Model;
use think\Cache;
use think\Config;

class SportsTypes extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 获取详情
     * @param $id
     * @return mixed
     */
    public function getInfoById($id) {
        $info = Loader::model('SportsTypes')->get($id);
        if (!$info) {
            return false;
        }
        return $info->toArray();
    }

    /**
     * 获取详情
     * @param $engName
     * @return mixed
     */
    public function getInfoByEngName($engName) {
        $info = Loader::model('SportsTypes')->where(['st_eng_name' => $engName])->find();
        if (!$info) {
            return false;
        }
        return $info->toArray();
    }

    /**
     * 返回列表
     * @return bool|mixed
     */
    public function getList() {
        $cacheKey = Config::get('cache_option.prefix')['sports_api'] . 'sports_types_list';
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        $field = 'st_id AS id,st_name AS name,st_eng_name AS eng_name,st_icon AS icon';
        $data = Loader::model('SportsTypes')->order('st_sort asc')->column($field);
        if (!$data) {
            return false;
        }
        $data = array_values($data);

        //计算每种体育赛事的比赛数量
        foreach($data as $key => $item) {
            $data[$key]['icon'] = Config::get('oss_sports_url') . $item['icon'];
        }

        Cache::set($cacheKey, $data, Config::get('common.cache_time')['sports_type']);
        return $data;
    }
}