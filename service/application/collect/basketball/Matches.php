<?php
/**
 * 联赛业务
 * @createTime 2017/4/26 10:05
 */

namespace app\collect\basketball;

use think\Cache;
use think\Loader;
use think\Config;

class Matches extends \app\common\basketball\Matches {

    public $matchArr = [];

    /**
     * 判断联赛是否存在，不存在则插入
     * @param $name 联赛名称
     * @return bool
     */
    public function checkMatchByName($name) {
        $cacheKey = Config::get('cache_option.prefix')['sports_collect'] . 'basketball_match_name:' . md5($name);
        if (isset($this->matchArr[$cacheKey])) {
            return $this->matchArr[$cacheKey];
        }

        $cache = Cache::get($cacheKey);
        if ($cache) {
            $this->matchArr[$cacheKey] = $cache;
            return $cache;
        }
        $matchesModel = Loader::model('SportsBasketballMatches');
        $info = $matchesModel->where(['sbm_name' => $name])->column('sbm_id');
        if (!$info) {
            $insertData = [
                'sbm_name' => $name,
                'sbm_create_time' => date('Y-m-d H:i:s'),
                'sbm_modify_time' => date('Y-m-d H:i:s'),
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