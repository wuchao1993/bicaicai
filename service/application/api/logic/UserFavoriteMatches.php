<?php
/**
 * 收藏联赛
 * @createTime 2017/4/4 10:20
 */

namespace app\api\logic;

use think\Loader;
use think\Model;

class UserFavoriteMatches extends Model {

    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 添加收藏
     */
    public function addFavorite($params) {
        if($this->isFavorite($params)) {
            $this->errorcode = EC_USER_FAVORITE_MATCHES_ADD_ERROR;

            return false;
        }
        $data = [
            'scm_uid'        => USER_ID,
            'scm_sport_id'   => $params['sport_id'],
            'scm_matches_id' => $params['matches_id'],
            'scm_creat_time' => date('Y-m-d H:i:s'),
        ];
        $userFavoriteMatches = Loader::model('sportsFavoriteMatches');
        $result = $userFavoriteMatches->save($data);
        if(!$result) {
            $this->errorcode = EC_USER_FAVORITE_MATCHES_ADD_ERROR;
            return false;
        } else {
            return true;
        }
    }

    private function isFavorite($params) {
        $condition                   = [];
        $condition['scm_uid']        = USER_ID;
        $condition['scm_sport_id']   = $params['sport_id'];
        $condition['scm_matches_id'] = $params['matches_id'];
        $userFavoriteMatches = Loader::model('sportsFavoriteMatches');
        $result = $userFavoriteMatches->where($condition)->find();
        if($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取消收藏
     */
    public function cancelFavorite($params) {
        $condition                   = [];
        $condition['scm_sport_id']   = $params['sport_id'];
        $condition['scm_matches_id'] = $params['matche_id'];
        $condition['scm_uid']        = USER_ID;
        $userFavoriteMatches = Loader::model('sportsFavoriteMatches');
        $result = $userFavoriteMatches->where($condition)->delete();
        if(!$result) {
            $this->errorcode = EC_USER_FAVORITE_MATCHES_CANCEL_ERROR;
            return false;
        } else {
            return true;
        }
    }

    public function getFavoriteMatchesList() {
        $condition['scm_uid'] = USER_ID;
        $userFavoriteMatches  = Loader::model('sportsFavoriteMatches');
        $result = $userFavoriteMatches->where($condition)->select();
        $data = array();
        foreach($result as $k => $v) {
            $data[$k]['matches_name'] = $this->getMatchesNameById($v['scm_matches_id'], $v['scm_sport_id']);
            $data[$k]['matches_id']   = $v['scm_matches_id'];
            $data[$k]['sport_id']     = $v['scm_sport_id'];
        }

        return $data;
    }

    private function getMatchesNameById($id, $sportId) {
        $condition['st_id'] = $sportId;
        $sportInfo = Loader::model('SportsTypes')->where($condition)->find();
        switch($sportInfo['st_eng_name']) {
            case 'football':
                $name = Loader::model('Matches', $sportInfo['st_eng_name'])->getInfoById($id, $field = 'sfm_name');
                return $name['sfm_name'];
                break;
            case 'basketball':
                $name = Loader::model('Matches', $sportInfo['st_eng_name'])->getInfoById($id, $field = 'sbm_name');
                return $name['sbm_name'];
                break;
            case 'tennis':
                $name = Loader::model('Matches', $sportInfo['st_eng_name'])->getInfoById($id, $field = 'stm_name');
                return $name['stm_name'];
                break;
            default:
                return false;
        }
    }
}