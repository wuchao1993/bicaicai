<?php
/**
 * 验证器
 * @createTime 2017/5/29 14:36
 */

namespace app\api\validate;

use think\Validate;
use think\Config;


class SportsSchedule extends Validate
{

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'sport' => 'require|alpha|checkSportName',
        'scheduleId' => 'require|number',
        'operate' => 'require|number|between:1,2',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'scheduleId.number' => '联赛id必须是数字',
        'scheduleId.require' => '联赛id不能为空',
        'operate.require' => '收藏状态不能为空',
        'operate.number' => '收藏状态必须是数字',
        'operate.between' => '收藏状态在1和2之间',
        'sport.require' => '球类名称不能为空',
        'sport.alpha'   => '球类名称只能是字母',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'keepSchedule' => ['sport','scheduleId','operate'],
        'getKeepInfo'  => ['sport'],
    ];

    /***
     * @desc 检测球类名称是否存在
     * @param $value
     * @return bool|string
     */
    public function checkSportName($value){
        $arr = array_keys(Config::get("sports.sport_types"));
        if(!in_array($value,$arr)){
            return '球类名称不存在';
        }
        return true;
    }

}