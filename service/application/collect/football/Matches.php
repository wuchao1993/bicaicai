<?php
/**
 * 联赛业务
 * @createTime 2017/4/26 10:05
 */

namespace app\collect\football;

use think\Cache;
use think\Config;
use think\Loader;

class Matches extends \app\common\football\Matches {

    public $matchArr = [];

    /**
     * 判断联赛是否存在，不存在则插入
     * @param $name 联赛名称
     * @return bool
     */
    public function checkMatchByName($name) {
        $cacheKey = Config::get('cache_option.prefix')['sports_collect'] . 'football_match_name:' . md5($name);
        if (isset($this->matchArr[$cacheKey])) {
            return $this->matchArr[$cacheKey];
        }

        $cache = Cache::get($cacheKey);
        if ($cache) {
            $this->matchArr[$cacheKey] = $cache;
            return $cache;
        }
        $matchesModel = Loader::model('SportsFootballMatches');
        $info = $matchesModel->where(['sfm_name' => $name])->column('sfm_id');
        if (!$info) {
            $insertData = [
                'sfm_name' => $name,
                'sfm_create_time' => date('Y-m-d H:i:s'),
                'sfm_modify_time' => date('Y-m-d H:i:s'),
            ];
            $matchId = $matchesModel->insertGetId($insertData);
            if (!$matchId) {
                return false;
            }
        } else {
            $matchId = $info[0];
        }

        $this->matchArr[$cacheKey] = $matchId;
        Cache::set($cacheKey, $matchId);
        return $matchId;
    }
}