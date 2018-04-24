<?php
/**
 * 数字彩验证器
 * @author paulli
 */

namespace app\admin\validate;

use think\Validate;

class Lottery extends Validate {

    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'id'               => 'require',
        'page'             => 'number',
        'num'              => 'number',
        'help'             => 'require',
        'example'          => 'require',
        'tips'             => 'require',
        'name'             => 'require',
        'introduction'     => 'require',
        'displayId'        => 'require',
        'hot'              => 'require',
        'status'           => 'require',
        'configIds'        => 'require',
        'lotteryId'        => 'require',
        'defaultIds'       => 'require',
        'sortIds'          => 'require',
        'categoryId'       => 'require',
        'categoryName'     => 'require',
        'categoryModel'    => 'require',
        'defaultRebate'    => 'require',
        'categorySort'     => 'require',
        'issuePrizeNum'    => 'require',
        'issueNo'          => 'require',
        'issueStartTime'   => 'require',
        'issueEndTime'     => 'require',
        'issuePrizeStatus' => 'require',
        'issueStatus'      => 'require',
        'orderId'          => 'require',
        'configIds'        => 'require|checkConfigIds',
        'defaultIds'       => 'require|checkDefaultIds',
        'sortIds'          => 'require|checkSortIds',
        'headIds'          => 'require|checkHeadIds',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'id.require'               => 'ID不能为空',
        'page'                     => '页码格式不合法',
        'num'                      => '分页数量不合法',
        'help.require'             => '玩法帮助不能为空',
        'example.require'          => '玩法示例不能为空',
        'tips.require'             => '玩法tips不能为空',
        'name.require'             => '名称不能为空',
        'introduction.require'     => '简介不能为空',
        'displayId.require'        => '展示分类不能为空',
        'hot.require'              => '是否热门不能为空',
        'status.require'           => '是否弃用不能为空',
        'configIds.require'        => 'configIds配置不能为空',
        'lotteryId.require'        => '彩种ID不能为空',
        'defaultIds.require'       => 'defaultIds配置不能为空',
        'sortIds.require'          => 'sortIds配置不能为空',
        'categoryId.require'       => '分类ID不能为空',
        'categoryName.require'     => '分类名不能为空',
        'categoryModel.require'    => '赔率模式不能为空',
        'defaultRebate.require'    => '会员注册默认返点不能为空',
        'categorySort.require'     => '排序不能为空',
        'issuePrizeNum.require'    => '开奖号码不能为空',
        'issueNo.require'          => '期号不能为空',
        'issueStartTime.require'   => '开盘时间不能为空',
        'issueEndTime.require'     => '封盘时间不能为空',
        'issuePrizeStatus.require' => '开奖状态不能为空',
        'issueStatus.require'      => '是否启用不能为空',
        'orderId.require'          => '订单ID不能为空',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'editPlay'              => ['id', 'help', 'example', 'tips'],
        'addGame'               => ['name', 'introduction', 'displayId', 'hot', 'status'],
        'editGame'              => ['id', 'name', 'introduction', 'displayId', 'hot', 'status'],
        'getPlayConfigList'     => ['categoryId'],
        'getTypeConfigList'     => ['categoryId'],
        'editCategoryDisplay'   => ['id', 'name', 'hot'],
        'editCategory'          => ['id', 'categoryName', 'categoryModel', 'defaultRebate', 'categorySort'],
        'editIssue'             => ['id', 'issuePrizeNum'],
        'editLhcIssue'          => ['id', 'issueNo', 'issueStartTime', 'issueEndTime', 'issuePrizeNum', 'issuePrizeStatus', 'issueStatus'],
        'getPrizeNum'           => ['lotteryId', 'issueNo'],
        'cancelOrder'           => ['orderId'],
        'refreshOrder'          => ['orderId'],
    ];

    public function checkConfigIds($value){
        if(empty($value) || !is_array($value)) {
            return 'checkConfigIds类型不合法';
        }
        if(empty($value['lid'])){
            return 'lid不能为空';
        }
        if(empty($value['tid'])){
            return 'tid不能为空';
        }
        if(empty($value['tid'])){
            return 'checked状态不能为空';
        }
        return true;
    }

    public function checkDefaultIds($value){
        if(empty($value) || !is_array($value)) {
            return 'checkDefaultIds类型不合法';
        }
        if(empty($value['tid'])){
            return 'tid不能为空';
        }
        if(empty($value['df'])){
            return 'df不能为空';
        }
        return true;
    }

    public function checkSortIds($value){
        if(empty($value) || !is_array($value)) {
            return 'checkSortIds类型不合法';
        }
        if(empty($value['lid'])){
            return 'checkSortIds lid不能为空';
        }
        if(empty($value['tid'])){
            return 'checkSortIds tid不能为空';
        }
        if(empty($value['s'])){
            return 'checkSortIds的排序s状态不能为空';
        }
        return true;
    }

    public function checkHeadIds($value){
        if(empty($value) || !is_array($value)) {
            return 'checkHeadIds类型不合法';
        }
        if(empty($value['lid'])){
            return 'headIds lid不能为空';
        }
        if(empty($value['tid'])){
            return 'headIds checked状态不能为空';
        }
        return true;
    }

}