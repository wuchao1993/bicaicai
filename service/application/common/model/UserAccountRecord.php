<?php
namespace app\common\model;

use think\Db;
use think\Model;
use think\Config;

class UserAccountRecord extends Model{

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'uar_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'uar_id'               => '主键',
        'user_id'              => '用户ID',
        'uar_source_id'        => '来源ID',
        'uar_source_type'      => '来源类型',
        'uar_transaction_type' => '金流类型',
        'uar_action_type'      => '金流方向',
        'uar_amount'           => '交易金额',
        'uar_before_balance'   => '操作前账户余额',
        'uar_after_balance'    => '操作后账户余额',
        'uar_createtime'       => '创建时间',
        'uar_finishtime'       => '完成时间',
        'uar_status'           => '状态',
        'uar_remark'           => '备注',
    ];

    public function setStatusEnd($sourceId, $sourceType, $transactionType) {
        $condition = [];
        if(is_array($sourceId)) {
            $condition['uar_source_id'] = [
                'in',
                $sourceId,
            ];
        } else {
            $condition['uar_source_id'] = $sourceId;
        }

        $condition['uar_source_type'] = $sourceType;
        $condition['uar_transaction_type'] = $transactionType;
        $data = [];
        $data['uar_status'] = Config::get('status.account_record_status')['yes'];
        $data['uar_finishtime'] = current_datetime();

        return $this->where($condition)->update($data);
    }

    /**
     * 获取金流记录
     * @param $sourceId
     * @param $sourceType
     * @param string $transactionType
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfoBySource($sourceId, $sourceType, $transactionType = '') {
        $condition = [
            'uar_source_id' => $sourceId,
            'uar_source_type' => $sourceType,
        ];
        if ($transactionType) {
            $condition['uar_transaction_type'] = $transactionType;
        }
        $result = $this->where($condition)->find();

        return $result ? $result->toArray() : false;
    }

    public function getSportsStatistics($startDate, $endDate){
        $sourceTypes = [
            Config::get('status.user_account_record_source_type')['sports_order'],
            Config::get('status.user_account_record_source_type')['recharge'],
            Config::get('status.user_account_record_source_type')['withdraw'],
        ];

        $condition = [
            "uar_finishtime"  => ['between', [$startDate, $endDate]],
            "uar_status"      => Config::get('status.account_record_status')['yes'],
            "uar_source_type" => ['in', $sourceTypes]
        ];
        $accountRecordTransactionTypes = Config::get('status.account_record_transaction_type');
        $fields = [
            'user_id',
            "IFNULL(sum(if(uar_transaction_type=" . $accountRecordTransactionTypes['artificial_out'] .", `uar_amount`, 0)), 0)" =>  "uds_deduction",
            'IFNULL(sum(if(uar_transaction_type=' . $accountRecordTransactionTypes['bet'] .', `uar_amount`, 0)), 0)' => 'uds_bet',
            'IFNULL(count(DISTINCT COALESCE(if(uar_transaction_type=' . $accountRecordTransactionTypes['bet'] .', uar_source_id, NULL), NULL)), 0)' => 'uds_bet_times',
            'IFNULL(sum(if(uar_transaction_type=' . $accountRecordTransactionTypes['bonus'] .', uar_amount, 0)), 0)' => 'uds_bonus',
            'IFNULL(sum(if(uar_transaction_type=' . $accountRecordTransactionTypes['sports_rebate'] .', uar_amount, 0)), 0)' => 'uds_rebate',
            'IFNULL(sum(if(uar_transaction_type=' . $accountRecordTransactionTypes['agent_rebate'] .', uar_amount, 0)), 0)' => 'uds_agent_rebate',
            'IFNULL(sum(if(uar_transaction_type=' . $accountRecordTransactionTypes['discount'] .', uar_amount, 0)), 0)' => 'uds_discount',
            'IFNULL(sum(if(uar_transaction_type=' . $accountRecordTransactionTypes['cancel_order'] .', uar_amount, 0)), 0)' => 'uds_cancel_order',
            'IFNULL(count(DISTINCT COALESCE(if(uar_transaction_type=' . $accountRecordTransactionTypes['cancel_order'] .', uar_source_id, NULL), NULL)), 0)' => 'uds_cancel_order_times'
        ];
        $uarSql = $this->force('uar_finishtime')->where($condition)->field($fields)->group('user_id')->order('user_id asc')->buildSql();
        $result = Db::table('ds_user')->alias('u')->join([$uarSql => 'r'], 'u.user_id = r.user_id')->field('user_pid,user_grade,user_name,user_nickname,user_is_agent,r.*')->select();
        return $result ? collection($result)->toArray() : false;
    }


}