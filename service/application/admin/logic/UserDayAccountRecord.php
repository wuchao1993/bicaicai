<?php

/**
 * 用户账户每日统计相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Loader;
use think\Model;

class UserDayAccountRecord extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取代理报表列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getReportList($params) {

        $returnArr = [
            'totalCount' => 0,
            'list'       => [],
            'subTotal'   => 0,
            'total'      => 0,
        ];

        $userDayAccountRecordModel = Loader::model('UserDayAccountRecord');

        $condition = [];
        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition['usda_day'] = [
                'between',
                [
                    $params ['start_date'],
                    $params ['end_date'],
                ],
            ];
            $between               = [
                'between',
                [
                    date('Y-m-d H:i:s', strtotime($params ['start_date'])),
                    date('Y-m-d H:i:s', strtotime($params ['end_date'] . ' 23:59:59')),
                ],
            ];
        }

        if(isset($params ['user_name'])) {
            $userIds = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            $condition['user_id'] = [
                'IN',
                $userIds
            ];
        } elseif(isset($params ['user_id'])) {
            $condition['user_pid'] = $params ['user_id'];
        } elseif(isset($params ['new_user_id'])) {
            $nextUsers            = Loader::model('User')->where(array(
                'user_pid'        => $params ['new_user_id'],
                'user_createtime' => $between
            ))->column('user_id');
            $condition['user_id'] = [
                'IN',
                $nextUsers,
            ];
        } else {
            $condition['user_grade'] = 0;
        }

        // 获取总条数
        $count = $userDayAccountRecordModel->where($condition)->count('distinct user_id');
        if($count == 0) {
            if(isset($params ['excel'])){
                $newList = array();
                $fileName = 'agent_list_report_'.$params['start_date'].'-'.$params['end_date'];
                $title = ['账号','总充值','总提现','总扣除','总下注额','总投注笔数','提现次数','充值次数','销售返点','中奖金额','代理返点','活动','团队盈亏','派彩(损益)','平台实际盈亏','注册时间','代理数	','代理下投注人数','会员数'];
                return $this->_exportExcel($newList, $title, $fileName);
            }else{
                return $returnArr;
            }
        }

        if($params['start_date'] == $params['end_date']) {
            $list = $userDayAccountRecordModel->where($condition)->field('user_id,usda_id,' . $this->_statField())->group('user_id')->order('usda_day asc, user_id asc')->limit($params ['num'])->page($params ['page'])->select();
        } else {
            $list = $userDayAccountRecordModel->where($condition)->field('user_id,usda_id,' . $this->_statField())->group('user_id')->limit($params ['num'])->page($params ['page'])->select();
        }

        //批量获取用户名称
        $userIds  = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where([
            'user_id' => [
                'IN',
                $userIds
            ]
        ])->column('user_id,user_name,user_createtime');

        if(!empty($list)) {
            foreach($list as $key => &$val) {

                if($val['user_id'] <= 0) {
                    unset($list[$key]);
                    continue;
                }

                //新注册代理个数、会员个数
                $userId = $val ['user_id'];

                $userCondition   = [];
                $userCondition[] = [
                    'exp',
                    'FIND_IN_SET(' . $userId . ',user_all_pid)'
                ];

                if($between) {
                    $userCondition['user_createtime'] = $between;
                }

                $countInfo = Loader::model('User')->field('IFNULL(count(user_id),0) user_count, IFNULL(count(IF(user_is_agent=1, (1) , NULL)), 0) agent_count')->where($userCondition)->find();

                $val ['agentCount'] = $countInfo ['agent_count'];
                $val ['userCount']  = $countInfo ['user_count'];

                //投注人数
                if(isset($condition['user_grade'])&&$condition['user_grade']==0){

                    $pcondition = [];
                    $pcondition[] = ['exp','FIND_IN_SET(' . $userId . ',user_all_pid)'];
                    $pcondition['usda_day'] = $condition['usda_day'];
                    $pcondition['usda_bet'] = ['gt',0];

                    $betPeople = $userDayAccountRecordModel->alias('udar')->join('__USER__ u','u.user_id = udar.user_id')->where($pcondition)->field('count(DISTINCT udar.user_id) bet_people')->find();

                    $val['betPeople']  = $betPeople['bet_people'];
                }

                $val['username'] = $userList[$userId]['user_name'];
                $val['usercreatetime'] = $userList[$userId]['user_createtime'];
            }
        }
        if(isset($params ['excel'])){
            $list =  $list ? collection($list)->toArray() : [];
            $newList = array();
            foreach ($list as $key => $value){
                $newList[$key]['username']          = $value['username'];
                $newList[$key]['recharge']          = $value['recharge'];
                $newList[$key]['withdraw']          = $value['withdraw'];
                $newList[$key]['deduction']         = $value['deduction'];
                $newList[$key]['bet']               = $value['bet'];
                $newList[$key]['betCount']          = $value['betCount'];
                $newList[$key]['withdrawNum']       = $value['withdrawNum'];
                $newList[$key]['rechargeNum']       = $value['rechargeNum'];
                $newList[$key]['rebate']            = $value['rebate'];
                $newList[$key]['bonus']             = $value['bonus'];
                $newList[$key]['agentRebate']       = $value['agentRebate'];
                $newList[$key]['discount']          = $value['discount'];
                $newList[$key]['teamProfit']        = $value['teamProfit'];
                $newList[$key]['platformProfit']    = $value['platformProfit'];
                $newList[$key]['platformActualProfit'] = $value['platformActualProfit'];
                $newList[$key]['regTime']           = $value['usercreatetime'];
                $newList[$key]['agentCount']        = $value['agentCount'];
                $newList[$key]['betPeople']         = !empty($value['betPeople'])?$value['betPeople']:0;
                $newList[$key]['userCount']          = $value['userCount'];
            }
            $fileName = 'agent_list_report_'.$params['start_date'].'-'.$params['end_date'];
            $title = ['账号','总充值','总提现','总扣除','总下注额','总投注笔数','提现次数','充值次数','销售返点','中奖金额','代理返点','活动','团队盈亏','派彩(损益)','平台实际盈亏','注册时间','代理数	','代理下投注人数','会员数'];
            return $this->_exportExcel($newList, $title, $fileName);
        }

        $subFields = [
            'recharge',
            'rechargeNum',
            'withdraw',
            'withdrawNum',
            'deduction',
            'bet',
            'betCount',
            'bonus',
            'rebate',
            'agentRebate',
            'discount',
            'teamProfit',
            'platformProfit',
            'platformActualProfit',
            'agentCount',
            'betPeople',
            'userCount',
        ];

        //小计
        $subtotals = array_subtotal($list, $subFields);
        if(isset($subtotals['betPeople'])){
            $subtotals['betPeople'] = (int)$subtotals['betPeople'];
        }

        // 总计
        $totals = $userDayAccountRecordModel->where($condition)->field($this->_statField())->find()->toArray();

        $userStatInfo = Loader::model('User')->field('IFNULL(count(user_id),0) user_count, IFNULL(count(IF(user_is_agent=1, (1), NULL)), 0) agent_count')->where([
            'user_createtime' => $between,
        ])->find();

        $totals['agentCount'] = $userStatInfo['agent_count'];
        $totals['userCount']  = $userStatInfo['user_count'];


        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
            'subTotal'   => $subtotals,
            'total'      => $totals,
        ];

        return $returnArr;
    }

    /**
     * 获取体彩代理报表列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getSportReportList($params) {

        $returnArr = [
            'totalCount' => 0,
            'list'       => [],
            'subTotal'   => 0,
            'total'      => 0,
        ];

        $AgentDayStatisticsModel = Loader::model('AgentDayStatistics');

        $condition = [];
        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition['ads_date'] = [
                'between',
                [
                    $params ['start_date'],
                    $params ['end_date'],
                ],
            ];
            $between               = [
                'between',
                [
                    date('Y-m-d H:i:s', strtotime($params ['start_date'])),
                    date('Y-m-d H:i:s', strtotime($params ['end_date'] . ' 23:59:59')),
                ],
            ];
        }

        if(isset($params ['user_name'])) {
            $userInfo = Loader::model('User', 'logic')->getInfoByUserName($params['user_name'], true);
            if($userInfo['user_is_agent'] == 1) {
                $condition['user_id'] = $userInfo['user_id'];
            }else {
                return $this->getSportAgentUserList($params);
            }
        } elseif(isset($params ['user_id'])) {
            return $this->getSportAgentUserList($params);
        } else {
            $condition['user_grade'] = 0;
        }

        // 获取总条数
        $count = $AgentDayStatisticsModel->where($condition)->count('distinct user_id');

        if($count == 0) {
            return $returnArr;
        }

        if($params['start_date'] == $params['end_date']) {
            $list = $AgentDayStatisticsModel->where($condition)->field('user_id,ads_id as id,' . $this->_statSportField())->group('user_id')->order('ads_date asc, user_id asc')->limit($params ['num'])->page($params ['page'])->select();
        } else {
            $list = $AgentDayStatisticsModel->where($condition)->field('user_id,ads_id as id,' . $this->_statSportField())->group('user_id')->limit($params ['num'])->page($params ['page'])->select();
        }

        //批量获取用户名称
        $userIds  = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where([
            'user_id' => [
                'IN',
                $userIds
            ]
        ])->column('user_id,user_name,user_createtime');

        if(!empty($list)) {
            foreach($list as $key => &$val) {

                if($val['user_id'] <= 0) {
                    unset($list[$key]);
                    continue;
                }

                //新注册代理个数、会员个数
                $userId = $val ['user_id'];

                $userCondition   = [];
                $userCondition[] = [
                    'exp',
                    'FIND_IN_SET(' . $userId . ',user_all_pid)'
                ];

                if($between) {
                    $userCondition['user_createtime'] = $between;
                }

                $countInfo = Loader::model('User')->field('IFNULL(count(user_id),0) user_count, IFNULL(count(IF(user_is_agent=1, (1) , NULL)), 0) agent_count')->where($userCondition)->find();

                $val ['agentCount'] = $countInfo ['agent_count'];
                $val ['userCount']  = $countInfo ['user_count'];

                //投注人数
                if(isset($condition['user_grade'])&&$condition['user_grade']==0){

                    $pcondition = [];
                    $pcondition[] = ['exp','FIND_IN_SET(' . $userId . ',user_all_pid)'];
                    $pcondition['ads_date'] = $condition['ads_date'];
                    $pcondition['ads_bet'] = ['gt',0];

                    $betPeople = $AgentDayStatisticsModel->alias('ads')->join('__USER__ u','u.user_id = ads.user_id')->where($pcondition)->field('count(DISTINCT ads.user_id) bet_people')->find();

                    $val['betPeople']  = $betPeople['bet_people'];
                }

                $val['username'] = $userList[$userId]['user_name'];
                $val['usercreatetime'] = $userList[$userId]['user_createtime'];
            }
        }
        if(isset($params ['excel'])){
            $list =  $list ? collection($list)->toArray() : [];
            $newList = array();
            foreach ($list as $key => $value){
                $newList[$key]['username']          = $value['username'];
                $newList[$key]['recharge']          = $value['recharge'];
                $newList[$key]['withdraw']          = $value['withdraw'];
                $newList[$key]['deduction']         = $value['deduction'];
                $newList[$key]['bet']               = $value['bet'];
                $newList[$key]['betCount']          = $value['betCount'];
                $newList[$key]['withdrawNum']       = $value['withdrawNum'];
                $newList[$key]['rechargeNum']       = $value['rechargeNum'];
                $newList[$key]['rebate']            = $value['rebate'];
                $newList[$key]['bonus']             = $value['bonus'];
                $newList[$key]['agentRebate']       = $value['agentRebate'];
                $newList[$key]['discount']          = $value['discount'];
                $newList[$key]['teamProfit']        = $value['teamProfit'];
                $newList[$key]['platformProfit']    = $value['platformProfit'];
                $newList[$key]['platformActualProfit'] = $value['platformActualProfit'];
                $newList[$key]['regTime']           = $value['usercreatetime'];
                $newList[$key]['agentCount']        = $value['agentCount'];
                $newList[$key]['betPeople']         = !empty($value['betPeople'])?$value['betPeople']:0;
                $newList[$key]['userCount']          = $value['userCount'];
            }
            $fileName = 'sport_agent_list_report_'.$params['start_date'].'-'.$params['end_date'];
            $title = ['账号','总充值','总提现','总扣除','总下注额','总投注笔数','提现次数','充值次数','销售返点','中奖金额','代理返点','活动','团队盈亏','派彩(损益)','平台实际盈亏','注册时间','代理数	','代理下投注人数','会员数'];
            return $this->_exportExcel($newList, $title, $fileName);
        }

        $subFields = [
            'recharge',
            'rechargeNum',
            'withdraw',
            'withdrawNum',
            'deduction',
            'bet',
            'betCount',
            'bonus',
            'rebate',
            'agentRebate',
            'discount',
            'teamProfit',
            'platformProfit',
            'platformActualProfit',
            'agentCount',
            'userCount',
        ];

        //小计
        $subtotals = array_subtotal($list, $subFields);

        // 总计
        $totals = $AgentDayStatisticsModel->where($condition)->field($this->_statSportField())->find()->toArray();

        $userStatInfo = Loader::model('User')->field('IFNULL(count(user_id),0) user_count, IFNULL(count(IF(user_is_agent=1, (1), NULL)), 0) agent_count')->where([
            'user_createtime' => $between,
        ])->find();

        $totals['agentCount'] = $userStatInfo['agent_count'];
        $totals['userCount']  = $userStatInfo['user_count'];


        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
            'subTotal'   => $subtotals,
            'total'      => $totals,
        ];

        return $returnArr;
    }

    public function getSportAgentUserList($params) {

        $returnArr = [
            'totalCount' => 0,
            'list'       => [],
            'subTotal'   => 0,
            'total'      => 0,
        ];

        $userDayStatisticsModel = Loader::model('common/UserDayStatistics');

        if(isset($params ['user_name'])) {
            $userInfo = Loader::model('User', 'logic')->getInfoByUserName($params['user_name'], true);
            $condition['user_id'] = $userInfo['user_id'];
        }else {
            $condition['user_pid'] = $params ['user_id'];
        }

        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition['uds_date'] = [
                'between',
                [
                    $params ['start_date'],
                    $params ['end_date'],
                ],
            ];
            $between               = [
                'between',
                [
                    date('Y-m-d H:i:s', strtotime($params ['start_date'])),
                    date('Y-m-d H:i:s', strtotime($params ['end_date'] . ' 23:59:59')),
                ],
            ];
        }

        // 获取总条数
        $count = $userDayStatisticsModel->where($condition)->count('distinct user_id');

        if($count == 0) {
            return $returnArr;
        }

        if($params['start_date'] == $params['end_date']) {
            $list = $userDayStatisticsModel->where($condition)->field('user_id,uds_id as id,' . $this->_statSportUserField())->group('user_id')->order('uds_date asc, user_id asc')->limit($params ['num'])->page($params ['page'])->select();
        } else {
            $list = $userDayStatisticsModel->where($condition)->field('user_id,uds_id as id,' . $this->_statSportUserField())->group('user_id')->limit($params ['num'])->page($params ['page'])->select();
        }

        //批量获取用户名称
        $userIds  = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where([
            'user_id' => [
                'IN',
                $userIds
            ]
        ])->column('user_id,user_name,user_createtime');

        if(!empty($list)) {
            foreach($list as $key => &$val) {

                if($val['user_id'] <= 0) {
                    unset($list[$key]);
                    continue;
                }

                //新注册代理个数、会员个数
                $userId = $val ['user_id'];

                $userCondition   = [];
                $userCondition[] = [
                    'exp',
                    'FIND_IN_SET(' . $userId . ',user_all_pid)'
                ];

                if($between) {
                    $userCondition['user_createtime'] = $between;
                }

                $countInfo = Loader::model('User')->field('IFNULL(count(user_id),0) user_count, IFNULL(count(IF(user_is_agent=1, (1) , NULL)), 0) agent_count')->where($userCondition)->find();

                $val ['agentCount'] = $countInfo ['agent_count'];
                $val ['userCount']  = $countInfo ['user_count'];

                //投注人数
                if(isset($condition['user_grade'])&&$condition['user_grade']==0){

                    $pcondition = [];
                    $pcondition[] = ['exp','FIND_IN_SET(' . $userId . ',user_all_pid)'];
                    $pcondition['uds_date'] = $condition['uds_date'];
                    $pcondition['uds_bet'] = ['gt',0];

                    $betPeople = $userDayStatisticsModel->alias('uds')->join('__USER__ u','u.user_id = uds.user_id')->where($pcondition)->field('count(DISTINCT uds.user_id) bet_people')->find();

                    $val['betPeople']  = $betPeople['bet_people'];
                }

                $val['username'] = $userList[$userId]['user_name'];
                $val['usercreatetime'] = $userList[$userId]['user_createtime'];
            }
        }
        if(isset($params ['excel'])){
            $list =  $list ? collection($list)->toArray() : [];
            $newList = array();
            foreach ($list as $key => $value){
                $newList[$key]['username']          = $value['username'];
                $newList[$key]['recharge']          = $value['recharge'];
                $newList[$key]['withdraw']          = $value['withdraw'];
                $newList[$key]['deduction']         = $value['deduction'];
                $newList[$key]['bet']               = $value['bet'];
                $newList[$key]['betCount']          = $value['betCount'];
                $newList[$key]['withdrawNum']       = $value['withdrawNum'];
                $newList[$key]['rechargeNum']       = $value['rechargeNum'];
                $newList[$key]['rebate']            = $value['rebate'];
                $newList[$key]['bonus']             = $value['bonus'];
                $newList[$key]['agentRebate']       = $value['agentRebate'];
                $newList[$key]['discount']          = $value['discount'];
                $newList[$key]['teamProfit']        = $value['teamProfit'];
                $newList[$key]['platformProfit']    = $value['platformProfit'];
                $newList[$key]['platformActualProfit'] = $value['platformActualProfit'];
                $newList[$key]['regTime']           = $value['usercreatetime'];
                $newList[$key]['agentCount']        = $value['agentCount'];
                $newList[$key]['betPeople']         = !empty($value['betPeople'])?$value['betPeople']:0;
                $newList[$key]['userCount']          = $value['userCount'];
            }
            $fileName = 'sport_agent_list_report_'.$params['start_date'].'-'.$params['end_date'];
            $title = ['账号','总充值','总提现','总扣除','总下注额','总投注笔数','提现次数','充值次数','销售返点','中奖金额','代理返点','活动','团队盈亏','派彩(损益)','平台实际盈亏','注册时间','代理数	','代理下投注人数','会员数'];
            return $this->_exportExcel($newList, $title, $fileName);
        }

        $subFields = [
            'recharge',
            'rechargeNum',
            'withdraw',
            'withdrawNum',
            'deduction',
            'bet',
            'betCount',
            'bonus',
            'rebate',
            'agentRebate',
            'discount',
            'teamProfit',
            'platformProfit',
            'platformActualProfit',
            'agentCount',
            'userCount',
        ];

        //小计
        $subtotals = array_subtotal($list, $subFields);

        // 总计
        $totals = $userDayStatisticsModel->where($condition)->field($this->_statSportUserField())->find()->toArray();

        $userStatInfo = Loader::model('User')->field('IFNULL(count(user_id),0) user_count, IFNULL(count(IF(user_is_agent=1, (1), NULL)), 0) agent_count')->where([
            'user_createtime' => $between,
        ])->find();

        $totals['agentCount'] = $userStatInfo['agent_count'];
        $totals['userCount']  = $userStatInfo['user_count'];


        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
            'subTotal'   => $subtotals,
            'total'      => $totals,
        ];

        return $returnArr;
    }

    private function _statField() {
        return 'sum(usda_recharge) as recharge,sum(usda_withdraw) as withdraw,sum(usda_deduction) as deduction,
        sum(usda_bet) as bet,sum(usda_bet_count) as betCount,sum(usda_rebate) as rebate,sum(usda_bonus) as bonus,
        sum(usda_agent_rebate) as agentRebate,sum(usda_discount) as discount,sum(usda_team_profit) as teamProfit,
        sum(usda_platform_profit) as platformProfit,sum(usda_platform_actual_profit) as platformActualProfit,
        sum(usda_recharge_num) as rechargeNum, sum(usda_withdraw_num) as withdrawNum';
    }

    private function _statSportField() {
        return 'sum(ads_recharge) as recharge,sum(ads_withdraw) as withdraw,sum(ads_deduction) as deduction,
        sum(ads_bet) as bet,sum(ads_bet_times) as betCount,sum(ads_rebate) as rebate,sum(ads_bonus) as bonus,
        sum(ads_rebate) as agentRebate,sum(ads_discount) as discount,sum(ads_team_profit) as teamProfit,
        sum(ads_platform_profit) as platformProfit,sum(ads_platform_actual_profit) as platformActualProfit,
        sum(ads_recharge_times) as rechargeNum, sum(ads_withdraw_times) as withdrawNum';
    }

    private function _statSportUserField() {
        return 'sum(uds_recharge) as recharge,sum(uds_withdraw) as withdraw,sum(uds_deduction) as deduction,
        sum(uds_bet) as bet,sum(uds_bet_times) as betCount,sum(uds_rebate) as rebate,sum(uds_bonus) as bonus,
        sum(uds_rebate) as agentRebate,sum(uds_discount) as discount,sum(uds_team_profit) as teamProfit,
        sum(uds_platform_profit) as platformProfit,sum(uds_platform_actual_profit) as platformActualProfit,
        sum(uds_recharge_times) as rechargeNum, sum(uds_withdraw_times) as withdrawNum';
    }

    private function _statSportAmountField() {
        return [
            'user_id',
            'sum(uds_recharge)' => 'recharge',
            'sum(uds_withdraw)' => 'withdraw',
            'sum(uds_bet_valid)' => 'bet',
            'sum(uds_discount)' => 'discount',
        ];
    }

    /**
     * excel导出代理报表
     *
     * @param $list array 数据列表
     * @param $title string excel字段名
     * @param $fileName string excel文件名
     * @return $ossFileName string 文件路径
     */
    private function _exportExcel($list, $title, $fileName) {
        $localFilePath  = 'uploads' . DS . $fileName;
        Loader::model('ReportExcel', 'logic')->ExportList($list, $title, $localFilePath);
        $ossFileName = $localFilePath.'.xls';

        return $ossFileName;

        $ossClient = Oss::getInstance();
        $bucket    = Oss::getBucketName();
        $data      = $ossClient->uploadFile($bucket, $fileName.'.xls', ROOT_PATH . 'public' . DS .$ossFileName);
        if($data){
            $ossFileUrl = $data['info']['url'];
            unlink(ROOT_PATH . 'public' . DS .$ossFileName);
            return $ossFileUrl;
        }else{
            $this->errorcode = EC_AD_REPORT_EXCEL_FAIL;
            return false;
        }
    }

    /**
     * 用户有效投注，充值，提款
     * @param
     *         $params
     * @return array
     */
    public function userOrderAmountList($params) {

        $userDayStatisticsModel = Loader::model('common/UserDayStatistics');

        $condition = [];
        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition['uds_date'] = [
                'between',
                [
                    $params ['start_date'],
                    $params ['end_date'],
                ],
            ];
        }

        if(isset($params ['user_name'])) {
            $userIds = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            $condition['user_id'] = [
                'IN',
                $userIds
            ];
        }

        if($params['start_date'] == $params['end_date']) {
            $list = $userDayStatisticsModel->where($condition)->field($this->_statSportAmountField())->group('user_id')
                                            ->order('uds_date asc, user_id asc')
                                            ->limit(($params['page'] -1) * $params['num'], $params['num'])
                                            ->select();
            $count = $userDayStatisticsModel->where($condition)->group('user_id')->order('uds_date asc, user_id asc')->count();
        } else {
            $list = $userDayStatisticsModel->where($condition)->field($this->_statSportAmountField())->group('user_id')
                                           ->limit(($params['page'] -1) * $params['num'], $params['num'])
                                           ->select();
            $count = $userDayStatisticsModel->where($condition)->group('user_id')->count();
        }

        //批量获取用户名称
        $userIds  = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where([
            'user_id' => [
                'IN',
                $userIds
            ]
        ])->column('user_id,user_name,user_realname,user_createtime');

        if(!empty($list)) {
            foreach($list as $key => &$val) {
                if($val['user_id'] <= 0) {
                    unset($list[$key]);
                    continue;
                }
                $userId = $val ['user_id'];
                $val['username'] = $userList[$userId]['user_name'];
                $val['realname'] = $userList[$userId]['user_realname'];
            }
        }

        if(isset($params ['excel'])){
            $list =  $list ? collection($list)->toArray() : [];
            $newList = array();
            foreach ($list as $key => $value){
                $newList[$key]['username']          = $value['username'];
                $newList[$key]['realname']          = $value['realname'];
                $newList[$key]['recharge']          = $value['recharge'];
                $newList[$key]['withdraw']          = $value['withdraw'];
                $newList[$key]['bet']               = $value['bet'];
            }
            $fileName = 'sport_user_report_'.$params['start_date'].'-'.$params['end_date'];
            $title = ['账号','真实姓名','充值','提款','有效投注'];
            return $this->_exportExcel($newList, $title, $fileName);
        }

        $returnArr = [
            'list'       => $list,
            'totalCount' => $count,
        ];

        return $returnArr;
    }
}