<?php
/**
 * 玩法模型
 * @createTime 2017/4/13 14:34
 */

namespace app\common\model;

use think\Model;

class SportsPlayTypesConfig extends Model
{
    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'sptc_id';


    /**
     * @desc 判断玩法配置 信息是否存在
     * @param $ulId  用户层级id
     * @param $sptIds 玩法ids
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getConfigs($ulId, $sptIds)
    {
        $configWhere['sptc_spt_id'] = ['in', $sptIds];
        $configWhere['sptc_ul_id'] = $ulId;
        $field = ['sptc_id' => 'id', 'sptc_spt_id' => 'sptID'];
        $typeConfigs = $this->where($configWhere)->field($field)->select();
        return $typeConfigs;
    }

    /**
     * @desc 获取不存在的玩法批量添加数据
     * @param $sportTypeConfigs 玩法配置信息
     * @param $newSptIds 未添加玩法配置信息
     * @param $ulId 用户层级id
     * @return array
     */
    public function getNewConfigData($sportTypeConfigs, $newSptIds, $ulId)
    {
        $newSportTypeConfigs = [];
        foreach ($sportTypeConfigs as $key => $value) {
            if (count($newSptIds) > 0) {
                if (in_array($value['sportId'], $newSptIds)) {
                    $newSportTypeConfigs[] = [
                        'sptc_ul_id' => $ulId,
                        'sptc_spt_id' => $value['sportId'],
                        'sptc_single_bet_limit_max' => $value['singleBetLimitMax'],
                        'sptc_single_bet_limit_min' => $value['singleBetLimitMin'],
                        'sptc_single_match_limit_max' => $value['singleMatchLimitMax'],
                        'sptc_create_time' => current_datetime(),
                        'sptc_modify_time' => current_datetime(),
                    ];
                }
            }
        }
        return $newSportTypeConfigs;
    }

    /**
     * @desc 获取不存在的玩法批量更新数据
     * @param $typeConfigs 玩法配置信息
     * @param $sportTypeConfigs 玩法配置信息
     * @param $oldSptIds 已添加玩法ids
     * @param $ulId 用户层级id
     * @return array
     */
    public function getUpdateConfigData($typeConfigs, $sportTypeConfigs, $oldSptIds, $ulId)
    {
        $updateSportTypeConfigs = [];
        foreach ($typeConfigs as $k => $v) {
            foreach ($sportTypeConfigs as $key => $value) {
                if (in_array($value['sportId'], $oldSptIds)) {
                    if ($value['sportId'] == $v['sptID']) {
                        $updateSportTypeConfigs[] = [
                            'sptc_id' => $v['id'],
                            'sptc_ul_id' => $ulId,
                            'sptc_spt_id' => $value['sportId'],
                            'sptc_single_bet_limit_max' => $value['singleBetLimitMax'],
                            'sptc_single_bet_limit_min' => $value['singleBetLimitMin'],
                            'sptc_single_match_limit_max' => $value['singleMatchLimitMax'],
                            'sptc_modify_time' => current_datetime(),
                        ];
                    }
                }
            }
        }
        return $updateSportTypeConfigs;
    }
}