<?php
/**
 * 球队业务
 * @createTime 2017/4/26 9:30
 */

namespace app\collect\basketball;

use think\Loader;
use think\Cache;
use think\Config;

class Teams extends \app\common\basketball\Teams {

    public $teamArr = [];

    /**
     * 判断球队是否存在，不存在则插入
     * @param $name 球队名称
     * @return bool
     */
    public function checkTeamByName($name) {
        $cacheKey = Config::get('cache_option.prefix')['sports_collect'] . 'basketball_team_name:' . md5(trim($name));
        if (isset($this->teamArr[$cacheKey])) {
            return $this->teamArr[$cacheKey];
        }

        $cache = Cache::get($cacheKey);
        if ($cache) {
            $this->teamArr[$cacheKey] = $cache;
            return $cache;
        }
        $teamsModel = Loader::model('SportsBasketballTeams');
        $info = $teamsModel->where(['sbt_name' => $name])->column('sbt_id');
        if (!$info) {
            $insertData = [
                'sbt_name' => $name,
                'sbt_create_time' => date('Y-m-d H:i:s'),
                'sbt_modify_time' => date('Y-m-d H:i:s'),
            ];
            $teamId = $teamsModel->insertGetId($insertData);
            if (!$teamId) {
                return false;
            }
        } else {
            $teamId = $info[0];
        }

        $this->teamArr[$cacheKey] = $teamId;
        Cache::set($cacheKey, $teamId);
        return $teamId;
    }
}