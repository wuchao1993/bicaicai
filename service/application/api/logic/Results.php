<?php
/**
 * 赛果业务逻辑
 * @createTime 2017/4/25 14:46
 */

namespace app\api\logic;

use think\Loader;
use think\Model;

class Results extends \app\common\logic\Results {
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
     * 赛果列表
     * @param $params
     * @return array|bool
     */
    public function getList($params) {
        if (!in_array($params['sport'], ['football', 'basketball', 'tennis'])) {
            return [];
        }
        if ($params['type'] == 'match') {
            return Loader::model('Results', $params['sport'])->getResultsMatch($params['date'], $params['page']);
        } elseif ($params['type'] == 'outright') {
            return Loader::model('Results', $params['sport'])->getResultsOutright($params['date'], $params['page']);
        }
        return [];
    }
}