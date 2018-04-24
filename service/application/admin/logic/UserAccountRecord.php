<?php

/**
 * 用户账户相关业务逻辑
 * @author paulli
 */

namespace app\admin\logic;

use think\Config;
use think\Loader;
use think\Log;
use think\Model;
use think\Db;
use think\db\Query;

class UserAccountRecord extends Model {

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 冷数据间隔天数
     */
    protected $intervalDay = COLD_DATA_INTERVAL_DAY;


    /**
     * 获取金流管理列表,支持查询冷数据（new）
     * 注：冷数据查询时间，只支持“创建时间”
     * @param $params
     * @return array
     * @author jesse
     */
    public function getAccountListColdData($params) {

        $returnArr = array(
            'totalCount' => 0,
            'subAmount'  => 0,
            'allAmount'  => 0,
            'list'       => []
        );


        $condition = [];

        $tableYm  =  [];

        //是否主表查询
        $master = true;

        //强制索引
        $forceIndex = '';

        //使用uar_createtime拆分数据
        if($params ['date_type'] == 1) {

            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {

                //昨天的数据被分离出去
                $splitEnd = date("Y-m-d 23:59:59",strtotime("-{$this->intervalDay} day"));

                //等于$splitTime的数据在主表
                $this->intervalDay --;
                $splitTime = date("Y-m-d 00:00:00",strtotime("-{$this->intervalDay} day"));

                if($params ['end_date']<$splitTime){
                    //全部在冷表里查 //冷表是一月一表

                    $tableYm = get_cold_data_ym($params ['start_date'],$params ['end_date']);

                    //用于判断主查询是否有数据，没数据直接用子查询1，替代主查询
                    $master = false;

                }elseif($params ['start_date']<$splitTime){
                    //$params ['end_date']>=$splitTime//冷表：s--sp，主表：sp--e （sp=e）

                    //冷表年月、查询区间
                    $tableYm = get_cold_data_ym($params ['start_date'],$splitEnd);

                    //主表查询条件
                    $condition ['uar_createtime'] = [['EGT', $splitTime], ['ELT', $params ['end_date']]];

                }else{
                    //主表：start_date>=$splitTime
                    //主表查询条件
                    $condition ['uar_createtime'] = [['EGT', $params ['start_date']], ['ELT', $params ['end_date']]];
                }

                $forceIndex = "uar_createtime";
            }else {
                return $returnArr;
            }
        } else {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['uar_finishtime'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
            } else {
                return $returnArr;
            }
        }

        if (isset ($params ['user_id']) ) {
            $condition['user_id'] = $params ['user_id'];
            $forceIndex = 'user_id';
        }elseif(isset ($params ['user_name'])) {
            $user_id           = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            $condition['user_id'] = $user_id;
            $forceIndex = 'user_id';
        }

        //用户角度转客户角度（盈利、亏损）
        $types = Config::get('status.account_record_transaction_type');
        $profitTypes = [$types['recharge'],$types['bet'],$types['artificial_in'],$types['artificial_out'],$types['recharge_company'],$types['withdraw_deduct'],$types['withdraw_cancel']];
        $deficitTypes = [$types['discount'],$types['rebate'],$types['bonus'],$types['withdraw'],$types['cancel_order'],$types['agent_rebate']];

        //直接查金流类型
        if(isset ($params ['uar_transaction_type']) && !empty ($params ['uar_transaction_type'])) {
            $searchTypes = $params ['uar_transaction_type'];
            //提现成功状态
            $widthdrawComplete = Config::get('status.account_record_transaction_type')['withdraw_complete'];
            if(in_array($widthdrawComplete,$searchTypes)) {
                $condition['uar_status'] = Config::get('status.account_record_status') ['yes'];
                array_push($searchTypes,Config::get('status.account_record_transaction_type')['withdraw']); 
            }
            $condition ['uar_transaction_type'] = ['IN', $searchTypes];  
        }else{
            //查金流方向
            if (isset ($params ['uar_action_type']) && !empty ($params ['uar_action_type']) ) {
                $condition ['uar_action_type'] = $params ['uar_action_type'];
            }else{
                $searchActions = array_values( Config::get('status.account_record_action_type') );
                $condition ['uar_action_type'] = ['IN', $searchActions];
            }
        }

        $searchProfitTypes = !empty($searchTypes)?array_intersect($searchTypes,$profitTypes):$profitTypes;
        $searchDeficitTypes = !empty($searchTypes)?array_intersect($searchTypes,$deficitTypes):$deficitTypes;

        //----组装查询 begin----
        $originTableName = "ds_user_account_record";
        $orderBy = 'uar_createtime desc';

        $multiTableCount = [];
        $multiDepositAmount = [];
        $multiFetchAmount   = [];
        $union = [];
        $tableIsExist = false;
        $replaceMaster = [];

        if($tableYm){
            $n = count($tableYm);
            $first  = 0;
            foreach ($tableYm as $key=>$ym){
                    $newTable = '';
                    $newTable = $originTableName."_".$ym['ym'];

                    if(!$tableIsExist){
                        if(table_is_exist($newTable)){
                            $tableIsExist = true;
                        }else{
                            $first++;
                            continue;
                        }
                    }

                    $lcondition = [];
                    $lcondition = $condition;
                    $lcondition['uar_createtime'] = [['EGT', $ym['sdate']], ['ELT', $ym['edate']]];

                    if($master==false && $key==$first){
                        $replaceMaster['table']      = $newTable;
                        $replaceMaster['condition']  = $lcondition;
                    }else{
                        if($n == ($key+1)){
                            //tp5 bug ,联合查询最后一条加全局条件
                            $union[] = Db::table($newTable)->where($lcondition)->fetchSql(true)->order($orderBy)->limit($params ['num'])->page($params ['page'])->select();
                        }else{
                            $union[] = Db::table($newTable)->where($lcondition)->fetchSql(true)->select();
                        }
                    }

                    //统计
                    $accountCount = $this->accountCount($newTable,$lcondition,$searchProfitTypes,$searchDeficitTypes);
                    $multiTableCount[]       = $accountCount['multiTableCount'];
                    $multiDepositAmount[]    = $accountCount['multiDepositAmount'];
                    $multiFetchAmount[]      = $accountCount['multiFetchAmount'];
            }
        }
        $query = new Query();

        if(!$master){
            //无主查询，且无子查询替换
            if(empty($replaceMaster)){
                return $returnArr;
            }
            //子查询1，替换主查询
            $query->setTable($replaceMaster['table']);
            $query->where($replaceMaster['condition']);
        }else{
            //主表查询
            $query->setTable($originTableName);
            $query->where($condition);

            //统计
            $accountCount = $this->accountCount($originTableName,$condition,$searchProfitTypes,$searchDeficitTypes);
            $multiTableCount[]       = $accountCount['multiTableCount'];
            $multiDepositAmount[]    = $accountCount['multiDepositAmount'];
            $multiFetchAmount[]      = $accountCount['multiFetchAmount'];
        }
        if($forceIndex){
            $query->force($forceIndex);
        }
        if(!empty($union)){
            $query->union($union);
        }else{
            $query->order($orderBy)->limit($params ['num'])->page($params ['page']);
        }

        $list =  Db::select($query);
        //----组装查询 end----


        //批量获取用户名称
        $userIds  = extract_array($list, 'user_id');
        $userIds  = array_unique($userIds);
        $userList = Loader::model('User')->where([
            'user_id' => [
                'IN',
                $userIds,
            ],
        ])->column('user_name,ul_id', 'user_id');

        // 小计
        $subAmount = 0;

        if(!empty($list)) {
            foreach($list as &$val) {
                if(in_array($val ['uar_transaction_type'],$profitTypes)) {
                    $subAmount += $val ['uar_amount'];
                } else {
                    $subAmount -= $val ['uar_amount'];
                }

                $val['user_name'] = isset($userList[$val['user_id']])?$userList[$val['user_id']]['user_name']:'';
            }
        }

        // 总计
        $depositAmount = array_sum($multiDepositAmount);
        $fetchAmount   = array_sum($multiFetchAmount);

        if(isset($params ['uar_action_type']) && $params ['uar_action_type'] == Config::get('status.account_record_action_type') ['deposit']) {
            $allAmount = $depositAmount;
        }elseif(isset($params ['uar_action_type']) && $params ['uar_action_type'] == Config::get('status.account_record_action_type') ['fetch']) {
            $allAmount = -$fetchAmount;
        }else {
            $allAmount = $depositAmount - $fetchAmount;
        }


        $count = array_sum($multiTableCount);

        $returnArr = [
            'totalCount' => $count,
            'subAmount'  => round($subAmount,3),
            'allAmount'  => round($allAmount,3),
            'list'       => $list,
        ];

        return $returnArr;
    }

    protected function accountCount($tableName,$condition,$searchProfitTypes,$searchDeficitTypes){

        $multiTableCount = Db::table($tableName)->where($condition)->count();

        $condition ['uar_transaction_type'] = ['IN', $searchProfitTypes];
        $multiDepositAmount          = Db::table($tableName)->where($condition)->sum('uar_amount');

        $condition ['uar_transaction_type'] = ['IN', $searchDeficitTypes];
        $multiFetchAmount            = Db::table($tableName)->where($condition)->sum('uar_amount');

        return compact("multiTableCount","multiDepositAmount","multiFetchAmount");
    }


    /**
     * 获取金流管理列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getAccountList($params) {

        $returnArr = array(
            'totalCount' => 0,
            'subAmount'  => 0,
            'allAmount'  => 0,
            'list'       => []
        );

        $userAccountRecordModel = Loader::model('UserAccountRecord');

        $condition = [];

        if($params ['date_type'] == 1) {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['uar_createtime'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
            }else {
                return $returnArr;
            }
        } else {
            if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
                $condition ['uar_finishtime'] = [
                    [
                        'EGT',
                        $params ['start_date'],
                    ],
                    [
                        'ELT',
                        $params ['end_date'],
                    ],
                ];
            } else {
                return $returnArr;
            }
        }
        if(isset ($params ['user_name'])) {
            $user_id           = Loader::model('User', 'logic')->getUserIdByUsername($params ['user_name']);
            $condition['user_id'] = $user_id;
            $forceIndex = 'user_id';
        }
        if(isset ($params ['uar_action_type'])) {
            $condition ['uar_action_type'] = $params ['uar_action_type'];
        }

        if(isset ($params ['uar_transaction_type']) && !empty ($params ['uar_transaction_type'])) {
            $transactionTypeStr                   = implode(',', $params ['uar_transaction_type']);
            $condition ['uar_transaction_type'] = [
                'IN',
                $transactionTypeStr,
            ];
        }


        if($forceIndex){
            $count = $userAccountRecordModel->force($forceIndex)->where($condition)->count();

            $list = $userAccountRecordModel->force($forceIndex)->where($condition)->order('uar_id desc')->limit($params ['num'])->page($params ['page'])->select();
        }else{
            $count = $userAccountRecordModel->where($condition)->count();

            $list = $userAccountRecordModel->where($condition)->order('uar_id desc')->limit($params ['num'])->page($params ['page'])->select();
        }

        //批量获取用户名称
        $userIds  = extract_array($list, 'user_id');
        $userList = Loader::model('User')->where([
            'user_id' => [
                'IN',
                $userIds,
            ],
        ])->column('user_name,ul_id', 'user_id');

        // 小计
        $subAmount = 0;

        if(!empty($list)) {
            foreach($list as $val) {
                if($val ['uar_action_type'] == Config::get('status.account_record_action_type') ['deposit']) {
                    $subAmount += $val ['uar_amount'];
                } else {
                    $subAmount -= $val ['uar_amount'];
                }

                $val['user_name'] = $userList[$val['user_id']]['user_name'];
            }
        }

        // 总计
        $condition ['uar_action_type'] = Config::get('status.account_record_action_type') ['deposit'];
        $depositAmount                 = $userAccountRecordModel->where($condition)->sum('uar_amount');

        $condition ['uar_action_type'] = Config::get('status.account_record_action_type') ['fetch'];
        $fetchAmount                   = $userAccountRecordModel->where($condition)->sum('uar_amount');

        if($params ['uar_action_type'] == Config::get('status.account_record_action_type') ['deposit']) {
            $allAmount = $depositAmount;
        }elseif($params ['uar_action_type'] == Config::get('status.account_record_action_type') ['fetch']) {
            $allAmount = -$fetchAmount;
        }else {
            $allAmount = $depositAmount - $fetchAmount;
        }

        $returnArr = [
            'totalCount' => $count,
            'subAmount'  => round($subAmount,3),
            'allAmount'  => round($allAmount,3),
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 更新状态
     *
     */
    public function setStatusEnd($sourceId, $sourceType, $transactionType) {
        $condition = [];
        if(is_array($sourceId)) {
            $condition ['uar_source_id'] = [
                'in',
                $sourceId,
            ];
        } else {
            $condition ['uar_source_id'] = $sourceId;
        }

        $condition ['uar_source_type']      = $sourceType;
        $condition ['uar_transaction_type'] = $transactionType;
        $data                               = [];
        $data ['uar_status']                = Config::get('status.account_record_status') ['yes'];
        $data ['uar_finishtime']            = date('Y-m-d H:i:s');

        return Loader::model('UserAccountRecord')->where($condition)->update($data);
    }


    /**
     * 出入账目汇总详情
     */
    public function detail($params) {

        if($params['type'] == Config::get('status.account_record_transaction_type') ['recharge']) {

            return $this->_getOnlineRecharges($params);

        } elseif($params['type'] == Config::get('status.account_record_transaction_type') ['recharge_company']) {

            return $this->_getCompanyRecharges($params);

        } else if($params ['type'] == Config::get('status.account_record_transaction_type') ['withdraw']) { //提现

            $response = $this->_getWithdrawDetails($params);

        } else if($params ['type'] == Config::get('status.account_record_transaction_type') ['artificial_in']) {

            $response = $this->_getArtificialIns($params);

        } else if($params ['type'] == "FIRST_WITHDRAW"){

            $response = $this->_getFirstWithdraw($params);

        } else if($params ['type'] == "FIRST_RECHARGE"){

            $response = $this->_getFirstRecharge($params);

        } else {

            $condition = [];

            if ($params['account']) {
                $condition['user_name'] = [
                    'like',
                    [
                        '%'.$params['account'].'%'
                    ],
                ];
            }

            if($params ['user_id']) {
                $userId = $params ['user_id'];
                $count = Loader::model('User')->where($condition)->where('user_id','IN',function ($query) use($userId){
                    $query->table('ds_user')->where('','exp',"FIND_IN_SET('".$userId."',user_all_pid)")->whereOr('user_id','=',$userId)->field('user_id');
                })->count();
            } else {
                $count = Loader::model('User')->where($condition)->where('user_id','IN',function ($query){
                    $query->table('ds_user')->where('user_grade=0')->field('user_id');
                })->count();
            }

            if($count>0){
                $fields = 'user_id as uid, user_name as username,user_realname';
                //人工存入需要获取上级用户名
                if ($params ['type'] == Config::get('status.account_record_transaction_type') ['artificial_out'] ) {
                    $fields = $fields.',user_pid';
                }
                if($params ['user_id']) {
                    $list  = Loader::model('User')->where($condition)->where('user_id','IN',function ($query) use($userId){
                        $query->table('ds_user')->where('','exp',"FIND_IN_SET('".$userId."',user_all_pid)")->whereOr('user_id','=',$userId)->field('user_id');
                    })->field($fields)->page($params['page'].','.$params['num'])->order('user_id asc')->select();
                }else{
                    $list  = Loader::model('User')->where($condition)->where('user_id','IN',function ($query){
                        $query->table('ds_user')->where('user_grade=0')->field('user_id');
                    })->field($fields)->page($params['page'].','.$params['num'])->order('user_id asc')->select();
                }

                if ($params ['type'] == Config::get('status.account_record_transaction_type') ['artificial_out'] ) {
                    //批量获取用户上级名称
                    $userPIds = array_unique(extract_array($list, 'user_pid') );
                    $userPList = Loader::model('User')->where(['user_id'=>['IN', $userPIds]])->column('user_name', 'user_id');
                }
              
                if(!empty ($list)) {
                    foreach($list as $k => $v) {
                        $condition = $this->rowWhere($params);
                        $uid = $v['uid'];
                        //人工存入需要获取上级用户名
                        if ($params ['type'] == Config::get('status.account_record_transaction_type') ['artificial_out'] ) {
                            $list[$k]['parent_user_name'] = $userPList[ $v['user_pid'] ] ? $userPList[ $v['user_pid'] ] : '';
                            unset($list[$k]['user_pid']);
                        }
                        if($uid == Config::get('common.user_administrator')) {
                            $userGrade = Config::get('common.user_grade')['level_zero'];
                            $row = Loader::model('UserAccountRecord')->where($condition)->where('user_id','IN',function ($query) use($uid,$userGrade){
                                $query->table('ds_user')->where('user_grade','neq',$userGrade)->where('','exp',"FIND_IN_SET('".$uid."',user_all_pid)")->whereOr('user_id','=',$uid)->field('user_id');
                            })->field('IFNULL(sum(uar_amount), 0) amount')->find()->toArray();
                        } else {
                            $row = Loader::model('UserAccountRecord')->where($condition)->where('user_id','IN',function ($query) use($uid){
                                $query->table('ds_user')->where('','exp',"FIND_IN_SET('".$uid."',user_all_pid)")->whereOr('user_id','=',$uid)->field('user_id');
                            })->field('IFNULL(sum(uar_amount), 0) amount')->find()->toArray();
                        }
                        $list[$k]['amount'] = $row['amount'];
                    }
                }
            }

            $order = ($params['sort'] == 'desc') ? 2 : 1;

            $list = array_sort_bykey($list, 'amount',$order);

            $response = [
                'totalCount' => $count,
                'list'       => array_values($list),
            ];

        }

        return $response;
    }


    public function _getFirstWithdraw($params){
        $startDate = $params['start_date'];
        $endDate = $params['end_date'];
        $page = $params['page'];
        $num = $params['num'];
        $sortField =  $params['sortField'];
        $sort = $params['sort'];

        $sortField = $sortField ? $sortField : 'complete_time';

        $condition['uwr_confirmtime'] = ['between', [$startDate, $endDate]];
        $condition['uwr_is_first']    = Config::get('status.withdraw_is_first') ['yes'];
        $condition['uwr_type']        = Config::get('status.user_withdraw_type') ['online'];
        if ($params['account']) {
           $condition['user_name'] = [
               'like',
               [
               '%'. $params['account'] .'%'
               ],
           ];
        }

        $userWithdrawRecordModel = Loader::model('UserWithdrawRecord');

        $count = $userWithdrawRecordModel->alias('uwr')->join('__USER__ u', 'u.user_id = uwr.user_id', 'left')->where($condition)->count();

        $fields = "ul_name, user_name,user_realname, uwr_apply_amount as amount,ifnull(uwr_account_balance,0)+ifnull(uwr_apply_amount,0) as before_balance,uwr_account_balance as after_balance, uwr_confirmtime as complete_time,uwr_remark as remark";

        //下载
        if(empty($page)&&empty($num)){
            if($count>0)
                $list = $userWithdrawRecordModel->alias('uwr')->join('__USER__ u', 'u.user_id = uwr.user_id', 'left')
                                            ->join('__USER_LEVEL__ ul', 'u.ul_id = ul.ul_id', 'left')
                                            ->where($condition)->field($fields)->order("$sortField $sort")->select();
                return $list ? collection($list)->toArray() : [];
        }else{

            if($count>0)
                $list   = $userWithdrawRecordModel->alias('uwr')->join('__USER__ u', 'u.user_id = uwr.user_id', 'left')
                                                ->join('__USER_LEVEL__ ul', 'u.ul_id = ul.ul_id', 'left')
                                                ->where($condition)->field($fields)->order("$sortField $sort")
                                                ->limit($num)->page($page)->select();

            return [
                'list'       => $list ? collection($list)->toArray() : [],
                'totalCount' => $count,
            ];
        }

    }

    public function _getFirstRecharge($params){
        $startDate = $params['start_date'];
        $endDate = $params['end_date'];
        $page = $params['page'];
        $num = $params['num'];
        $sortField =  $params['sortField'];
        $sort = $params['sort'];

        $sortField = $sortField ? $sortField : 'complete_time';

        $condition['urr_status']       = Config::get('status.recharge_status')['success'];
        $condition['urr_confirm_time'] = ['between', [$startDate, $endDate]];
        $condition['urr_is_first']     = Config::get('status.recharge_is_first')['yes'];
        if ($params['account']) {
            $condition['user_name'] = [
                'like',
                [
                '%'. $params['account'] .'%'
                ],
            ];

        }

        $model = Loader::model('UserRechargeRecord');

        $count = $model->alias('uwr')->where($condition)->join('__USER__ user', 'uwr.user_id=user.user_id', 'left')->count();

        $fields = 'ul_name, user_name,user_realname, urr_amount as amount,urr_recharge_discount,urr_total_amount,urr_confirm_time as complete_time, urr_remark as remark,urr_type';

        //下载
        if(empty($page)&&empty($num)){

            if($count > 0) {
                $list = $model->alias('uwr')->join('__USER__ user', 'uwr.user_id=user.user_id', 'left')
                                            ->join('__USER_LEVEL__ ul', 'user.ul_id=ul.ul_id', 'left')
                                            ->where($condition)->field($fields)->order("$sortField $sort")->select();
            }

            return $this->_firstRechargeList($list);

        }else{
            if($count > 0) {
                $list = $model->alias('uwr')->join('__USER__ user', 'uwr.user_id=user.user_id', 'left')
                                            ->join('__USER_LEVEL__ ul', 'user.ul_id=ul.ul_id', 'left')
                                            ->where($condition)->field($fields)->order("$sortField $sort")
                                            ->limit($num)->page($page)->select();
            }

            return [
                'list'       => $this->_firstRechargeList($list),
                'totalCount' => $count,
            ];
        }

    }

    private function _firstRechargeList($list){

        $list = $list ? collection($list)->toArray() : [];

        if(!empty($list)){
            $type_name = Config::get('status.user_recharge_type_name');

            foreach($list as &$item){
                $item['urr_type'] = $type_name[$item['urr_type']];
            }
        }

        return $list;
    }


    private function _getWithdrawDetails($params) {
        $startDate = $params['start_date'];
        $endDate = $params['end_date'];
        $page = $params['page'];
        $num = $params['num'];
        $sortField =  $params['sortField'];
        $sort = $params['sort'];

        $sortField = $sortField ? $sortField : 'complete_time';
        $condition['uwr_confirmtime'] = [
            'between',
            [
                $startDate,
                $endDate,
            ],
        ];
        if ($params['account']) {
            $condition['user_name'] = [
                'like',
                [
                '%'.$params['account'].'%'
                ],
            ];
        }

        $condition['uwr_status']      = Config::get('status.withdraw_status')['confirm'];
        $condition['uwr_type']        = Config::get('status.user_withdraw_type')['online'];
        $userWithdrawRecordModel = Loader::model('UserWithdrawRecord');

        $count = $userWithdrawRecordModel->alias('uwr')->join('__USER__ u', 'u.user_id = uwr.user_id', 'left')->where($condition)->count();
        Log::write(print_r($userWithdrawRecordModel->getLastSql(), true));
        $fields = "ul_name, user_name,user_realname, uwr_apply_amount as amount,ifnull(uwr_account_balance,0)+ifnull(uwr_apply_amount,0) as before_balance,uwr_account_balance as after_balance, uwr_confirmtime as complete_time,uwr_remark as remark";
        $list   = $userWithdrawRecordModel->alias('uwr')->join('__USER__ u', 'u.user_id = uwr.user_id', 'left')->join('__USER_LEVEL__ ul', 'u.ul_id = ul.ul_id', 'left')->where($condition)->field($fields)->order("$sortField $sort")->limit($num)->page($page)->select();
        Log::write(print_r($userWithdrawRecordModel->getLastSql(), true));

        return [
            'list'       => $list ? collection($list)->toArray() : [],
            'totalCount' => $count,
        ];
    }


    /**
     * 人工存入
     * @param $startDate
     * @param $endDate
     * @param $page
     * @param $num
     * @return array
     */
    private function _getArtificialIns($params) {
        $startDate = $params['start_date'];
        $endDate = $params['end_date'];
        $page = $params['page'];
        $num = $params['num'];
        $sortField =  $params['sortField'];
        $sort = $params['sort'];

        $sortField = $sortField ? $sortField : 'complete_time';

        $condition['urr_confirm_time'] = [
            'between',
            [
                $startDate,
                $endDate,
            ],
        ];
        if ($params['account']) {
            $condition['user_name'] = [
                'like',
                [
                '%' . $params['account'] . '%'
                ],
            ];
        }

        $condition['urr_type']         = Config::get('status.user_recharge_type')['system'];
        $condition['urr_status']       = Config::get('status.recharge_status')['success'];
        $condition['urr_amount']       = ['gt',0];

        $count = Loader::model('UserRechargeRecord')->alias('uwr')->join('__USER__ user', 'uwr.user_id=user.user_id', 'left')->where($condition)->count();

        $fields = 'ul_name, user.user_name,user.user_realname, urr_amount as amount,urr_recharge_discount,urr_total_amount,urr_confirm_time as complete_time, urr_remark as remark, user2.user_name as parent_user_name';
        if($count > 0) {
            $list = Loader::model('UserRechargeRecord')->alias('uwr')->join('__USER__ user', 'uwr.user_id=user.user_id', 'left')->join('__USER_LEVEL__ ul', 'user.ul_id=ul.ul_id', 'left')->where($condition)->join('__USER__ user2', 'user2.user_id=user.user_pid', 'left')->field($fields)->order("$sortField $sort")->limit($num)->page($page)->select();
        }

        return [
            'list'       => $list ? collection($list)->toArray() : [],
            'totalCount' => $count,
        ];
    }


    /**
     * 获取在线充值信息，按充值平台分类
     * @param $params
     * @return array|void
     */
    private function _getOnlineRecharges($params) {

        $returnArr = [
            'totalCount' => 0,
            'list'       => [],
        ];


        $condition                      = [];
        $condition ['urr_status']       = Config::get('status.recharge_status') ['success'];
        $condition ['urr_type']         = Config::get('status.user_recharge_type') ['online'];
        $condition ['urr_confirm_time'] = [
            'between',
            [
                $params ['start_date'],
                $params ['end_date'],
            ],
        ];

        if(!empty($params ['account'])){
            $payPlatformIds = Loader::model('PayPlatform')->getIdsByAccount($params ['account']);
            if(!empty($payPlatformIds)){
                $condition ['urr_recharge_account_id']  = ['IN',$payPlatformIds];
            }
        }

        $count = Loader::model('UserRechargeRecord')->where($condition)->group('urr_recharge_account_id')->count();

        if(empty($count)) {
            return $returnArr;
        }
        //计算总笔数
        $totalRechargeRecord = Loader::model('UserRechargeRecord')->where($condition)->count();

        $list = Loader::model('UserRechargeRecord')->where($condition)->field('urr_recharge_account_id, sum(urr_amount) amount,count(urr_id) recharge_account_count, urr_type')->order('urr_recharge_account_id asc')->group('urr_recharge_account_id')->limit($params ['num'])->page($params ['page'])->select();
        if(!empty ($list)) {
            $ppIds            = extract_array($list, 'urr_recharge_account_id');

            $payCenterRechargeAccountList = Loader::model('UserRechargeRecord','logic')->getPayChannelMerchant($ppIds);
            $payCenterPayTypeIds = extract_array($payCenterRechargeAccountList, 'pay_channel_id');
            $payCenterPayTypeList = Loader::model('UserRechargeRecord','logic')->getPayChannelName($payCenterPayTypeIds);
            $payCenterPayTypeMap = Loader::model('common/PayCenter', 'logic')->getPayCenterPayTypeMap();
            $rechargeTypeIdCodeMap = Loader::model('common/RechargeType')->getRechargeTypeIdCodeMap();

            foreach($list as $k => $v) {
                //单个第三方的笔数
                $params['urr_recharge_account_id'] = $v['urr_recharge_account_id'];

                $payTypeId             = !empty($payCenterRechargeAccountList[$v ['urr_recharge_account_id']]['pay_type_id'])?$payCenterRechargeAccountList[$v ['urr_recharge_account_id']]['pay_type_id']:'';
                if ( empty($payTypeId) ) {
                    unset($list[$k]);
                    continue;
                }

                $code                  = $payCenterPayTypeMap[$payCenterRechargeAccountList[$v['urr_recharge_account_id']]['pay_type_id']];
                $account               = $payCenterRechargeAccountList[$v['urr_recharge_account_id']]['account'];
                $payName               = $payCenterPayTypeList[$payCenterRechargeAccountList[$v['urr_recharge_account_id']]['pay_channel_id']];
                $payCategoryName       = $rechargeTypeIdCodeMap[$code]['recharge_type_name'];
                $list [$k] ['account'] = $payName . '-' . $payCategoryName . '-' . $account;
            }

        }
        //相同的第三方黏在一起
        $list = array_sort_bykey($list, 'account');

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
            'totalRechargeCount'  => $totalRechargeRecord,
        ];

        return $returnArr;
    }


    /**
     * 获取公司入款报表详情
     * @param $params
     * @return array|void
     */
    private function _getCompanyRecharges($params) {

        $returnArr = [
            'totalCount' => 0,
            'list'       => [],
        ];

        $condition                = [];
        $condition ['urr_status'] = Config::get('status.recharge_status') ['success'];

        $condition ['urr_type'] = Config::get('status.user_recharge_type') ['company'];

        $condition ['urr_confirm_time'] = [
            'between',
            [
                $params ['start_date'],
                $params ['end_date'],
            ],
        ];

        $count = Loader::model('UserRechargeRecord')->where($condition)->group('urr_recharge_account_id')->count();

        if(empty($count)) {
            return $returnArr;
        }

        $list = Loader::model('UserRechargeRecord')->where($condition)->field('urr_recharge_account_id, sum(urr_amount) amount, urr_type')->order('urr_recharge_account_id asc')->group('urr_recharge_account_id')->limit($params ['num'])->page($params ['page'])->select();
        if(!empty ($list)) {
            $paUsername = $params['account'];
            foreach($list as $k => $v) {
                $info = Loader::model('PayAccount')->getInfosByPaId($v ['urr_recharge_account_id'], $paUsername);
                if(!empty ($info)) {
                    $bank                  = Loader::model('Bank')->where('bank_id=' . $info ['bank_id'])->find();
                    $bankName              = empty ($bank) ? '' : $bank ['bank_name'];
                    $list [$k] ['account'] = $bankName . '-' . $info ['pa_collection_user_name'];
                }else{
                    unset($list[$k]);
                }
            }
        }

        $order = ($params['sort'] == 'desc') ? 1 : 2;
        $list = array_sort_bykey($list, 'amount');

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }


    private function rowWhere($params) {
        $condition = [];
        // 搜索 大于 * 时间段
        $start_date = $params ['start_date'] ? $params ['start_date'] : date('Y-m-d 00:00:00');
        // 搜索 小于 * 时间段
        $end_date = $params ['end_date'] ? date('Y-m-d 23:59:59', strtotime($params ['end_date'])) : date('Y-m-d 23:59:59');

        $condition ['uar_finishtime'] = [
            'between',
            [
                $start_date,
                $end_date,
            ],
        ];
        $condition ['uar_status']     = Config::get('status.account_record_status') ['yes'];

        $condition ['uar_transaction_type'] = is_numeric($params ['type']) ? $params ['type'] : [
            'IN',
            $params ['type'],
        ];

        return $condition;
    }

    /**
     * 用户可用余额详情
     */
    public function accountBalance($params) {
        //$count = Loader::model('User')->count();
        //原后台默认取99条
        $count = 99;
        $condition = [
            'ue.ue_account_balance' => [
                'GT',
                0,
            ]
        ];
        //条件搜索
        if ( $params['username'] ) {
           $condition['u.user_name'] = [
               'like',
               '%'.$params['username'].'%'
           ];  
        }

        $list = Loader::model('UserExtend')->alias('ue')->join('User u', 'ue.user_id=u.user_id', 'LEFT')->where( $condition )->field('u.user_id as uid, u.user_name as username, ue.ue_account_balance')->order('ue.ue_account_balance desc')->limit($params ['num'])->page($params ['page'])->select();

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 用户投注金额详情
     */
    public function betBalance($params) {
        $condition = [
            'lo.order_status' => [
                'EQ',
                1,
            ]
        ];
        //条件搜索
        if ( $params['username'] ) {
           $condition['u.user_name'] = [
               'like',
               '%'.$params['username'].'%'
           ];  
        }
        // var_dump($condition);exit;
        $count = Loader::model('LotteryOrder')->alias('lo')->join('User u', 'lo.user_id=u.user_id', 'LEFT')->where($condition)->field('lo.user_id, u.user_name, sum(lo.order_bet_amount) as order_bet_amounts')->group('lo.user_id')->count();

        $list = Loader::model('LotteryOrder')->alias('lo')->join('User u', 'lo.user_id=u.user_id', 'LEFT')->where($condition)->field('lo.user_id as uid, u.user_name as username, sum(lo.order_bet_amount) as order_bet_amounts')->order('order_bet_amounts desc')->group('lo.user_id')->limit($params ['num'])->page($params ['page'])->select();

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 用户投注金额详情
     */
    public function detailList($params) {
        $condition = $this->detaillistWhere($params);

        $count = $this->rechargeRecordCount($condition);

        $list = Loader::model('UserAccountRecord')->alias('uar')
            ->join('User u', 'uar.user_id=u.user_id', 'LEFT')
            ->join('UserLevel ul', 'ul.ul_id=u.ul_id', 'LEFT')
            ->join('UserRechargeRecord urr', 'uar.uar_source_id=urr.urr_id', 'LEFT')
            ->where($condition)
            ->field('ul_name, user_name, user_realname, uar_transaction_type, uar_amount, uar_before_balance, uar_after_balance, uar_createtime, uar_remark')
            ->order('uar.uar_createtime asc')->limit($params ['num'])->page($params ['page'])->select();

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    private function rechargeRecordCount($condition='')
    {
        $count = Loader::model('UserAccountRecord')->alias('uar')
                  ->join('User u', 'uar.user_id=u.user_id', 'LEFT')
                  ->join('UserLevel ul', 'ul.ul_id=u.ul_id', 'LEFT')
                  ->join('UserRechargeRecord urr', 'uar.uar_source_id=urr.urr_id', 'LEFT')
                  ->where($condition)->count();

        return $count;
    }
    private function detaillistWhere($params) {
        $where  = [];
        $type   = $params ['type'];
        $userId = $params ['user_id'];

        if(isset($params ['urr_recharge_account_id'])) {
            $where ['urr.urr_recharge_account_id'] = $params ['urr_recharge_account_id'];
        } elseif($userId) {
            if($userId == 1) {
                $user_ids = Loader::model('User', 'logic')->getAllNextUsers($userId, true, [
                    'user_grade' => [
                        'neq',
                        0,
                    ],
                ]);
            } else {
                $user_ids = Loader::model('User')->getTeamAllUsers($userId);
            }

            $where ['uar.user_id'] = [
                'IN',
                $user_ids,
            ];
        }

        $where ['uar.uar_finishtime'] = [
            'between',
            [
                $params ['start_date'],
                $params ['end_date'],
            ],
        ];

        $where ['uar.uar_transaction_type'] = is_numeric($type) ? $type : [
            'IN',
            $type,
        ];
        $where ['uar.uar_status']           = Config::get('status.account_record_status') ['yes'];

        return $where;
    }

}