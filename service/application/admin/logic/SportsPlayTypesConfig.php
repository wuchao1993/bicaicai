<?php
/**
 * 玩法管理
 * @createTime 2017/7/12 14:29
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;

class SportsPlayTypesConfig extends Model
{
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;


    /**
     * 获取玩法列表
     * @param $stId 球类id
     * @param $ulId 层级id
     * @return mixed
     */
    public function getSportsPlayTypesConfig($sportId, $ulId)
    {
        //获取指定类型球类玩法
        $typesWhere['spt_st_id'] = $sportId;
        $playTypeField = ['spt_id'=>'sportId','spt_name'=>'sportName'];
        $playTypes = Loader::model("common/SportsPlayTypes")->field($playTypeField)->where($typesWhere)->select();

        //获取玩法在当前层级的配置
        $playTypeConfigsField = [
            'sptc_spt_id' => 'spt_id',
            'sptc_single_bet_limit_min' => 'single_bet_limit_min',
            'sptc_single_bet_limit_max' => 'single_bet_limit_max',
            'sptc_single_match_limit_max' => 'single_match_limit_max',
        ];
        $sptIds = extract_array($playTypes, 'sportId');
        $condition['sptc_ul_id'] = $ulId;
        $condition['sptc_spt_id'] = ['in', $sptIds];
        $result = Loader::model("common/SportsPlayTypesConfig")->field($playTypeConfigsField)->where($condition)->select();

        //调整返水数据结构信息
        foreach ($playTypes as $key => &$value) {
            if ($result) {
                foreach ($result as $v) {
                    if ($value['sportId'] == $v['spt_id']) {
                        $value['single_bet_limit_max'] = $v['single_bet_limit_max'];
                        $value['single_bet_limit_min'] = $v['single_bet_limit_min'];
                        $value['single_match_limit_max'] = $v['single_match_limit_max'];
                    }
                }
            }

            empty($value['single_bet_limit_max']) && $value['single_bet_limit_max'] = '';
            empty($value['single_bet_limit_min']) && $value['single_bet_limit_min'] = '';
            empty($value['single_match_limit_max']) && $value['single_match_limit_max'] = '';
        }
        return $playTypes;
    }

    /**
     * 批量添加配置信息
     * @param $ulId 用户层级id
     * @param $configInfo 玩法配置信息
     * @return bool|void
     */
    public function saveSportsPlayTypesConfig($ulId, $configInfo)
    {
        //获取玩法id
        $sptIds = extract_array($configInfo, 'sportId');
        $typeConfigs = Loader::model('common/SportsPlayTypesConfig')->getConfigs($ulId, $sptIds);

        //批量更新旧的数据
        $oldSptIds = extract_array($typeConfigs, 'sptID');
        $updateSportTypeConfigs = Loader::model('common/SportsPlayTypesConfig')
            ->getUpdateConfigData($typeConfigs, $configInfo, $oldSptIds, $ulId);
        $flag = Loader::model('common/SportsPlayTypesConfig')->saveAll($updateSportTypeConfigs);
        if ($flag === false) {
            $this->errorcode = EC_UPDATE_ALL_SPORTS_PLAY_TYPES_CONFIG_ERROR;
            return;
        }

        //批量添加新的数据
        $newSptIds = array_diff($sptIds, $oldSptIds);
        if (count($newSptIds) > 0) {
            $newSportTypeConfigs = Loader::model('common/SportsPlayTypesConfig')
                ->getNewConfigData($configInfo, $newSptIds, $ulId);
            $flag = Loader::model('common/SportsPlayTypesConfig')->saveAll($newSportTypeConfigs);
            if ($flag === false) {
                $this->errorcode = EC_INSERT_ALL_SPORTS_PLAY_TYPES_CONFIG_ERROR;
                return;
            }
        }
        return true;
    }
}