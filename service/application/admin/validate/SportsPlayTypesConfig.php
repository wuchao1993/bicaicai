<?php
/**
 * 比赛玩法限额验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Validate;

class SportsPlayTypesConfig extends Validate
{

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'ulId' => 'require|number',
        'configInfo' => 'require|checkSportTypeConfigs',
        'sportId' => 'require|number',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'ulId.require' => '用户层级id不能为空',
        'ulId.number' => '用户层级id必须是数字',
        'sportId.require' => '球类id不能为空',
        'sportId.number' => '球类id必须是数字',
        'configInfo.require' => '玩法配置信息不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'editSportsPlayTypesConfig' => ['ulId', 'sportId'],
        'saveSportsPlayTypesConfig' => ['ulId', 'configInfo'],
    ];

    /**
     * 检查配置信息
     * @param $param
     * @return bool|string
     */
    public function checkSportTypeConfigs($param)
    {
        foreach ($param as $key => $value) {
            if ($value['sportId'] == '') {
                return "玩法类型id不能为空";
            } elseif ($value['singleBetLimitMax'] == '') {
                return "单注最高限制不能为空";
            } elseif ($value['singleBetLimitMin'] == '') {
                return "单注最低限制不能为空";
            } elseif ($value['singleMatchLimitMax'] == '') {
                return "单场最高限制不能为空";
            }
        }
        return true;
    }

}