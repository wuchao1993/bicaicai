<?php

/**
 * 用户相关业务逻辑
 */

namespace app\admin\logic;

use app\pay\service\Pay;
use passport\Passport;
use think\Cache;
use think\Config;
use Filipac\Ip;
use think\Env;
use think\Loader;
use think\Collection;
use Think\Model;

class User extends \app\common\logic\User {
    protected $userIds = [];

    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 错误消息
     * @var string
     */
    public $message = '';

    /**
     * 获取用户列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getList($params) {
        $condition = [];
        if(isset ($params ['user_pid'])) {
            $condition ['u.user_pid'] = $params ['user_pid'];
        }
        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['u.user_createtime'] = [
                [
                    'EGT',
                    $params ['start_date'],
                ],
                [
                    'ELT',
                    $params ['end_date'],
                ],
            ];
        }
        if(isset ($params ['user_is_agent'])) {
            $condition ['u.user_is_agent'] = $params ['user_is_agent'];
        }
        if(isset ($params ['user_bank_no'])) {
            $userBankInfo = Loader::model('UserBankRelation')->getUidByAccount($params ['user_bank_no']);

            if(!empty($userBankInfo)){
                $condition ['u.user_id'] = $userBankInfo;
            }else{
                $returnArr = [
                    'totalCount' => 0,
                    'list'       => [],
                ];
                return $returnArr;
            }
        }

        if($params ['is_precise_query'] == 1) {

            if(isset ($params ['user_name'])) {
                $condition ['u.user_name'] = $params ['user_name'];
            }
            if(isset ($params ['user_realname'])) {
                $condition ['u.user_realname'] = $params ['user_realname'];
            }
            if(isset ($params ['user_mobile'])) {
                $condition ['u.user_mobile'] = $params ['user_mobile'];
            }
            if(isset($params['user_qq'])) {
                $condition ['u.user_qq'] = $params ['user_qq'];
            }
            if(isset($params['user_email'])) {
                $condition ['u.user_email'] = $params ['user_email'];
            }
        }else {
            if(isset ($params ['user_name'])) {
                $condition ['u.user_name'] = [
                    'LIKE',
                    $params ['user_name'] . '%',
                ];
            }
            if(isset ($params ['user_realname'])) {
                $condition ['u.user_realname'] = [
                    'LIKE',
                    $params ['user_realname'] . '%',
                ];
            }
            if(isset ($params ['user_mobile'])) {
                $condition ['u.user_mobile'] = [
                    'LIKE',
                    $params ['user_mobile'] . '%',
                ];
            }
            if(isset($params['user_qq'])) {
                $condition ['u.user_qq'] = [
                    'LIKE',
                    $params ['user_qq'] . '%',
                ];
            }
            if(isset($params['user_email'])) {
                $condition ['u.user_email'] = [
                    'LIKE',
                    $params ['user_email'] . '%',
                ];
            }
        }

        if(isset ($params ['ul_id'])) {
            $condition ['u.ul_id'] = $params ['ul_id'];
        }

        if(isset($params['reg_terminal'])) {
            $condition ['u.reg_terminal'] = $params ['reg_terminal'];
        }


        if(isset($params['channel_name'])) {
            $channelId = Loader::model('Channel')->where( ['channel_name' => $params['channel_name'] ])->value('id');
            $condition ['u.channel_id'] = $channelId;
        }

        //获取当前在线用户数据
        $cache = Cache::init();
        $handler = $cache->handler();
        $onlineCacheKey = Config::get('cache.default')['prefix'] . Config::get('cache_option.prefix')['sports_common'] . 'user_online_status:';
        $onlineUsers = $handler->keys($onlineCacheKey . '*');

        //搜索在线用户
        if (isset($params['online_users']) && $params['online_users']) {
            if ($onlineUsers) {
                $onlineUserIds = array_map(function($value) use ($onlineCacheKey ) {
                    return str_replace($onlineCacheKey, '', $value);
                }, $onlineUsers);
                $condition['u.user_id'] = ['IN', $onlineUserIds];
            } else {
                return [
                    'totalOnlineUsers' => 0,
                    'totalCount'       => 0,
                    'list'             => [],
                ];
            }
        }

        $order = 'u.user_createtime desc';
        if(isset($params['sortMode'])) {
            switch($params['sortMode']) {
                case 1:
                    $sortMode = 'desc';
                    break;
                case 2:
                    $sortMode = 'asc';
                    break;
                default:
                    $sortMode = 'desc';
                    break;
            }
            $order = 'ue.ue_account_balance ' . $sortMode;
        }

        $userModel = Loader::model('User');
        // 获取总条数
        $count = $userModel->alias('u')->where($condition)->count();
        //导出excel表
        if(isset($params['export_excel'])) {
            $list = $userModel->alias('u')->join('UserExtend ue', 'ue.user_id=u.user_id', 'LEFT')->field('u.user_id,u.user_name,u.user_realname,u.user_mobile,u.user_contact_info,u.user_qq,ue.ue_account_balance,u.user_createtime,u.user_last_login_time,ue.ue_discount_amount,ue.ue_recharge_amount,ue.ue_withdraw_amount')->where($condition)->order($order)->select();
            $list =  $list ? collection($list)->toArray() : [];

            if(!empty($list)){
                foreach ($list as $key=>$row){
                    $list[$key]['user_realname'] = !empty($row['user_realname'])?$row['user_realname']:'';
                    $list[$key]['user_mobile'] = !empty($row['user_mobile'])?"'".$row['user_mobile']:'';
                    $list[$key]['user_contact_info'] = !empty($row['user_contact_info'])?"'".$row['user_contact_info']:'';
                    $list[$key]['user_qq'] = !empty($row['user_qq'])?"'".$row['user_qq']:'';
                    $list[$key]['ue_account_balance'] = !empty($row['ue_account_balance'])?$row['ue_account_balance']:0;
                    $list[$key]['user_createtime'] = !empty($row['user_createtime'])?$row['user_createtime']:'';
                    $list[$key]['user_last_login_time'] = !empty($row['user_last_login_time'])?$row['user_last_login_time']:'';
                    $list[$key]['ue_discount_amount'] = !empty($row['ue_discount_amount'])?$row['ue_discount_amount']:0;
                    $list[$key]['ue_recharge_amount'] = !empty($row['ue_recharge_amount'])?$row['ue_recharge_amount']:0;
                    $list[$key]['ue_withdraw_amount'] = !empty($row['ue_withdraw_amount'])?$row['ue_withdraw_amount']:0;
                }
            }

            $fileName = 'user_info_'.$params['start_date'].'-'.$params['end_date'];
            $title = ['用户ID','账号','真实姓名','手机号码','联系方式','QQ','用户余额','注册时间','最近登录时间','优惠总额','充值总额','提现总额'];
            return $this->_exportExcel($list, $title, $fileName);
        }else {
            $list = $userModel->alias('u')->join('UserExtend ue', 'ue.user_id=u.user_id', 'LEFT')->field('u.*,ue.ue_account_balance,ue.ue_discount_amount,ue.ue_recharge_amount,ue.ue_withdraw_amount')->where($condition)->order($order)->limit($params ['num'])->page($params ['page'])->select();
        }
        //批量获取用户上级名称
        $userPIds = extract_array($list, 'user_pid');
        $userPList = Loader::model('User')->where(['user_id'=>['IN', $userPIds]])->column('user_name', 'user_id');

        //批量获取用户层级名称
        $userULIds = extract_array($list, 'ul_id');
        $userULList = Loader::model('UserLevel')->where(['ul_id'=>['IN', $userULIds]])->column('ul_name', 'ul_id');

        if(!empty ($list)) {
            foreach($list as &$val) {
                if ($val ['user_last_login_ip'] == '') {
                    $val ['user_last_login_ip'] = 0;
                }

                $val['parent_user_name'] = !empty($userPList[$val['user_pid']])?$userPList[$val['user_pid']]:'';
                $val['ul_name'] = $userULList[$val['ul_id']];

                //获取用户在线状态
                $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'user_online_status:'  . $val['user_id'];
                if (Cache::get($cacheKey)) {
                    $val['online_status'] = true;
                } else {
                    $val['online_status'] = false;
                }
            }
        }

        //判断层级会员人数是否有误，有，则自动更新！
        if(isset($params['ul_id'])){
            $ulLogic = Loader::model('UserLevel','logic');
            $UlUserCount = $ulLogic->getUserCountByUlid($params['ul_id']);
            if($UlUserCount != $count){
                $ulLogic->updateUserCount($params['ul_id'],$count);
            }
        }

        $returnArr = [
            'totalOnlineUsers' => count($onlineUsers),
            'totalCount'       => $count,
            'list'             => $list,
        ];

        return $returnArr;
    }

    /**
     * 获取用户信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserIdByUsername($username) {
        if ( strpos($username,',') === false ) {
            $condition = [
                'user_name' => $username,
            ];
        }else{
            $condition = [
                'user_name' => [
                    'IN',
                    $username
                ],
            ];
        }

        $info      = Loader::model('User')->where($condition)->column('user_id');
        if( count($info) == 1) {
            return $info[0];
        }elseif (count($info) > 1) {
            return $info;
        }else {
            return '';
        }
    }
    /**
     * 获取用户层级
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserInfoByUserId($userId) {
        $condition = [
            'user_id' => $userId,
        ];
        $info      = Loader::model('User')->where($condition)->find();

        if(!empty ($info)) {
            return $info ['ul_id'];
        } else {
            return '';
        }
    }


    /**
     * 获取用户信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getInfo($uid) {
        $condition = [
            'user_id' => $uid,
        ];
        $info      = Loader::model('User')->where($condition)->find();

        // 获取上级名称
        $parentInfo = Loader::model('User')->where([
            'user_id' => $info ['user_pid'],
        ])->find();
        if(!empty ($parentInfo)) {
            $info ['parent_user_name'] = $parentInfo ['user_name'];
        } else {
            $info ['parent_user_name'] = '';
        }
        //获取渠道名称
        $channelName = Loader::model('Channel')->where([
            'id' => $info ['channel_id'],
        ])->find();

        if(!empty ($channelName)) {
            $info ['channel_name'] = $channelName ['channel_name'];
        } else {
            $info ['channel_name'] = '';
        }

        return $info;
    }

    /**
     * 获取用户额外信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getExtendInfo($username) {

        $condition = [
            'u.user_name' => [
                'IN',
                $username,
            ],
        ];
        $info      = Loader::model('User')->alias('u')->join('UserExtend ue', 'ue.user_id=u.user_id', 'LEFT')->field('u.user_name as username, u.user_realname as realname,u.ul_id as ulId, ue.ue_account_balance as accountBalance')->where($condition)->select();

        if(!$info) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        $info = collection($info)->toArray();

        //添加人工入款-查询用户+用户层级
        $ulIds = extract_array($info,'ulId');
        $ulIds = array_unique($ulIds);

        if(!empty($ulIds)){
            $where = [];
            $where['ul_id'] = ['IN',$ulIds];
            $ulNmaes = Loader::model('UserLevel')->where($where)->column('ul_id,ul_name');
        }

        foreach ($info as $key=>$row){
            $info[$key]['ulName'] = isset($ulNmaes[$row['ulId']])?$ulNmaes[$row['ulId']]:'';
        }

        return $info;
    }

    /**
     * 获取用户银行列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserBankList($uid) {
        $condition = [
            'ubr.user_id' => $uid,
            'ubr.ub_status' => 1
        ];
        $list      = Loader::model('UserBankRelation')->alias('ubr')->join('Bank b', 'ubr.bank_id=b.bank_id', 'LEFT')->field('ubr.ub_id,ubr.user_id,ubr.bank_id,b.bank_name,ubr.ub_bank_account,ubr.ub_bank_user_name,ubr.ub_address,ubr.ub_status')->where($condition)->select();

        return $list;
    }

    /**
     * 获取用户银行信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserBankInfo($ubId) {
        $condition = [
            'ubr.ub_id' => $ubId,
            'ubr.ub_status' => 1
        ];
        $info      = Loader::model('UserBankRelation')->alias('ubr')->join('Bank b', 'ubr.bank_id=b.bank_id', 'LEFT')->join('User u', 'ubr.user_id=u.user_id', 'LEFT')->field('ubr.ub_id,ubr.user_id,ubr.bank_id,b.bank_name,ubr.ub_bank_account,ubr.ub_bank_user_name,ubr.ub_address,ubr.ub_status,u.user_remark')->where($condition)->find();

        return $info;
    }

    /**
     * 获取用户返点信息
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserRebateInfo($uid) {
        // 获取博彩分类
        $where['lottery_category_id'] = ['not in',[PC_EGG_CATEGORY_ID,LHC_CATEGORY_ID]];
        $lotteryCategoryList = Loader::model('LotteryCategory')->where($where)->field('lottery_category_id,lottery_category_max_rebate,lottery_category_name')->select();

        // 获取父级返点信息
        $userInfo = $this->getInfo($uid);
        if(!empty ($userInfo ['user_pid'])) {
            $condition       = [
                'user_id' => $userInfo ['user_pid'],
            ];
            $pUserRebatelist = Loader::model('UserAutoRebateConfig')->where($condition)->select();
        } else {
            $pUserRebatelist = [];
            foreach($lotteryCategoryList as $key => $val) {
                $pUserRebatelist [$key] ['lottery_category_id'] = $val ['lottery_category_id'];
                $pUserRebatelist [$key] ['user_rebate']         = $val ['lottery_category_max_rebate'];
            }
        }

        // 获取自身返点信息
        $condition      = [
            'user_id' => $uid,
        ];
        $userRebatelist = Loader::model('UserAutoRebateConfig')->where($condition)->select();
        if(!empty ($userRebatelist)) {
            foreach($userRebatelist as $key => $val) {
                foreach($pUserRebatelist as $key2 => $val2) {
                    if($val ['lottery_category_id'] == $val2 ['lottery_category_id']) {
                        if($val ['user_rebate'] > $val2 ['user_rebate']) {
                            $userRebatelist [$key] ['user_rebate'] = $val2 ['user_rebate'];
                        }
                    }
                }
            }
        } else {
            foreach($lotteryCategoryList as $key => $val) {
                $userRebatelist [$key] ['lottery_category_id'] = $val ['lottery_category_id'];
                $userRebatelist [$key] ['user_rebate']         = $val ['lottery_category_max_rebate'];
            }
        }

        // 组装返回列表
        foreach($lotteryCategoryList as $key => $val) {
            foreach($userRebatelist as $key2 => $val2) {
                if($val ['lottery_category_id'] == $val2 ['lottery_category_id']) {
                    $lotteryCategoryList [$key] ['user_rebate'] = $val2 ['user_rebate'];
                }
            }
            if(!isset($userRebatelist[$key])) {
                $lotteryCategoryList [$key] ['user_rebate'] = $pUserRebatelist[$key] ['user_rebate'];
            }
        }

        return $lotteryCategoryList;
    }

    /**
     * 获取用户统计资料
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserStatistics($uid) {
        $userInfo                      = $this->getInfo($uid);
        $condition                     = [
            'user_id' => $uid,
        ];
        $info                          = Loader::model('UserExtend')->where($condition)->find();
        $info ['user_last_login_time'] = $userInfo ['user_last_login_time'];

        return $info;
    }

    /**
     * 获取用户投注列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserOrderList($params) {
        $condition ['lo.user_id'] = $params ['user_id'];

        if(isset ($params ['lottery_id'])) {
            $condition ['lo.lottery_id'] = $params ['lottery_id'];
        }
        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['lo.order_createtime'] = [
                [
                    'EGT',
                    $params ['start_date'],
                ],
                [
                    'ELT',
                    $params ['end_date'],
                ],
            ];
        }
        if(isset ($params ['issue_no'])) {
            $condition ['lo.issue_no'] = $params ['issue_no'];
        }
        if(isset ($params ['order_status'])) {
            $condition ['lo.order_status'] = $params ['order_status'];
        }

        $lotteryOrderModel = Loader::model('LotteryOrder');

        // 获取总条数
        $count = $lotteryOrderModel->alias('lo')->join('LotteryCategory lc', 'lo.lottery_category_id=lc.lottery_category_id', 'LEFT')->where($condition)->count();

        $list = $lotteryOrderModel->alias('lo')->join('LotteryCategory lc', 'lo.lottery_category_id=lc.lottery_category_id', 'LEFT')->join('LotteryType lt', 'lo.lottery_type_id=lt.lottery_type_id', 'LEFT')->field('lo.*,lc.lottery_category_name,lt.lottery_type_name')->where($condition)->order('lo.order_createtime desc')->limit($params ['num'])->page($params ['page'])->select();

        //对六合彩进行特殊处理
        if(!empty ($list)) {
             //批量获取游戏类型
             $lotteryTypeIds = extract_array($list, 'lottery_type_id');

             $lotteryTypeList = Loader::model('LhcType')->where([
                 'lhc_type_id' => [
                     'IN',
                     $lotteryTypeIds
                 ]
             ])->column('lhc_type_name', 'lhc_type_id');

            //批量获取游戏玩法
            $playIds = extract_array($list, 'play_id');

            $playList = Loader::model('LotteryPlay')->where([
                'play_id' => [
                    'IN',
                    $playIds
                ]
            ])->column('play_group_name,play_name', 'play_id');
            $lotteryList = Loader::model('Lottery')->column('lottery_name','lottery_id');
            foreach($list as $val) {
                //对六合彩做特殊处理
                if( in_array($val['lottery_id'], Config::get('six.LHC_LOTTERY_ID_ALL')  ) ){
                    $val['lottery_type_name'] = $lotteryTypeList[$val['lottery_type_id']] . '（' . $val['order_bet_position'] . '）';
                }else {
                    $val['lottery_type_name'] = $playList[$val['play_id']]['play_group_name'].'（'.$playList[$val['play_id']]['play_name'].'）';
                }
                $val['lottery_name'] = $lotteryList[$val['lottery_id']];
            }
        }

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 新增
     *
     * @param
     *            $params
     * @return bool
     */
    public function add($params) {
        // 判断帐号是否已经存在
        $ret = Loader::model('User')->where([
            'user_name' => $params ['user_name'],
        ])->count();
        if($ret > 0) {
            $this->errorcode = EC_AD_REG_USER_EXISTING;
            return false;
        }

        if(!empty($params ['user_name'])){
            if(!preg_match("/^[a-zA-Z][a-zA-Z0-9]{5,15}$/", trim($params ['user_name']))){
                $this->errorcode = EC_AD_USER_NAME_FORMAT_ERROR;
                return false;
            }
        }

        if(!empty($params ['user_password'])){
            if(!preg_match('/^(?!^[0-9]+$)(?!^[A-z]+$)[0-9A-Za-z]{6,12}$/', $params ['user_password'])){
                $this->errorcode = EC_AD_USER_PASSWORD_FORMAT_ERROR;
                return false;
            }
        }

        if(!empty($params ['user_mobile'])){
            // 判断手机号码是否已经存在
            $ret = Loader::model('User')->where([
                'user_mobile' => $params ['user_mobile'],
            ])->find();
            if(!empty($ret)) {
                $this->errorcode = EC_AD_USER_MOBILE_ALREADY_EXISTS;
                return false;
            }
        }

        //真实姓名是否重复
        if(!empty($params ['user_realname'])){
            $conf = Loader::model('ConfigManagement','logic')->getMapByName('IS_VERIFY_REALNAME');
            if($conf['value']){
                $condition['user_realname'] = $params ['user_realname'];
                $count  = Loader::model('User')->where($condition)->count();
                if($count>0){
                    $this->errorcode = EC_AD_USER_REAL_NAME_ALREADY_EXISTS;
                    return false;
                }
            }
        }


        if ($params['user_email']) {
            if(preg_match('/([\w\-]+\@[\w\-]+\.[\w\-]+)/', $params['user_email']) !== 1 ){
                $this->errorcode = EC_AD_USER_EMAIL_FORMAT_ERROR;
                return false;
            }

            $where['user_email']    = $params['user_email'];
            $count = Loader::model('User')->where($where)->count();
            if($count>0){
                $this->errorcode = EC_AD_USER_EMAIL_ALREADY_EXISTS;
                return false;
            }
            unset($where);
        }
        if ($params['user_qq']) {
            if(preg_match('/^[1-9][0-9]{4,13}$/', $params['user_qq']) !== 1 ){
                $this->errorcode = EC_AD_USER_QQ_FORMAT_ERROR;
                return false;
            }

            $where['user_qq']   = $params['user_qq'];
            $count = Loader::model('User')->where($where)->count();
            if($count>0){
                $this->errorcode = EC_AD_USER_QQ_ALREADY_EXISTS;
                return false;
            }
            unset($where);
        }
        if($params ['user_is_agent'] == Config::get('status.user_is_agent') ['yes']){
            $userPid = 0;
            $userIsAgent = Config::get('status.user_is_agent') ['yes'];
            $data['user_agent_check_status'] = Config::get('status.user_agent_check_status') ['enable'];
        }else{
            $userPid = Config::get('common.default_agent_uid');
            $userIsAgent = Config::get('status.user_is_agent') ['no'];
            $data['user_all_pid'] = Config::get('common.default_agent_uid');
        }
        $rebateConfig = Loader::model('LotteryCategory','logic')->getDefaultRebateMap();

        $pUserInfo = Loader::model('User')->getInfo($userPid);

        if($pUserInfo){
            $pUserGrade = $pUserInfo['user_grade'];
            $userGrade = $pUserGrade + 1;
        }else{
            $userGrade = 0;
        }

        $defaultLevelId = Loader::model('UserLevel','logic')->getDefaultLevelId();

        $this->startTrans();

        // 入库
        $salt                     = random_string();
        $data ['user_name']       = $params ['user_name'];
        $data ['user_realname']   = $params ['user_realname'];
        $data ['user_password']   = md5($params ['user_password'] . $salt);
        $data ['user_salt']       = $salt;
        $data ['user_mobile']     = $params ['user_mobile'];
        $data ['user_email']      = $params ['user_email'];
        $data ['user_is_agent']   = $userIsAgent;
        $data ['user_createtime'] = current_datetime();
        $data ['user_reg_ip']     = Ip::get();

        $data ['user_pid']        = $userPid;
        $data ['user_grade']      = $userGrade;
        $data ['ul_id']           = $defaultLevelId;

        $userModel = Loader::model('User');
        $ret       = $userModel->save($data);
        if($ret) {

            // 添加额外信息
            $user_extend             = [];
            $user_extend ['user_id'] = $userModel->user_id;
            Loader::model('UserExtend')->insert($user_extend);

            //添加用户返点配置
            Loader::model('UserAutoRebateConfig','logic')->addUserRebateConfig($rebateConfig,$userModel->user_id,$userPid);

            if($userPid){
                $this->addLowerCount($userPid);
            }

            Loader::model('UserLevel','logic')->setIncUserCountByUlId($defaultLevelId);

            if(empty($this->getError())){

                $this->commit();

                //记录行为
                Loader::model('General', 'logic')->actionLog('add_user', 'User', $userModel->user_id, MEMBER_ID, json_encode($data));

                $userInfo = [
                    'uid' => $userModel->user_id,
                ];

                return $userInfo;
            }
        }

        $this->rollback();

        $this->errorcode = EC_AD_REG_FAILURE;

        return false;
    }


    public function addLowerCount($userPid){

        $condition = [];

        $condition['user_id']   = $userPid;

        return $this->where($condition)->setInc('user_lower_count');

    }


    /**
     * 编辑
     *
     * @param
     *            $params
     * @return array
     */
    public function edit($params) {
        $userModel = Loader::model('User');

        // 获取用户信息
        $info = $userModel->where([
            'user_id' => $params ['user_id'],
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }


        //旧数据：无改动不做验证
        if($params['user_mobile']&&$params['user_mobile']!=$info['user_mobile']){
            if(preg_match('/1[34578]{1}\d{9}$/',$params['user_mobile'])!==1){
                $this->errorcode = EC_AD_USER_MOBILE_FORMAT_ERROR;
                return false;
            }

            $where['user_mobile']   = $params['user_mobile'];
            $where['user_id']       = array('neq',$params['user_id']);
            $count = $userModel->where($where)->count();
            if($count>0){
                $this->errorcode = EC_AD_USER_MOBILE_ALREADY_EXISTS;
                return false;
            }
            unset($where);
        }

        if ($params['user_email']&&$params['user_email']!=$info['user_email']) {
            if(preg_match('/([\w\-]+\@[\w\-]+\.[\w\-]+)/', $params['user_email']) !== 1 ){
               $this->errorcode = EC_AD_USER_EMAIL_FORMAT_ERROR;
               return false;
            }

            $where['user_email']    = $params['user_email'];
            $where['user_id']       = array('neq',$params['user_id']);
            $count = $userModel->where($where)->count();
            if($count>0){
                $this->errorcode = EC_AD_USER_EMAIL_ALREADY_EXISTS;
                return false;
            }
            unset($where);
        }
        if ($params['user_qq']&&$params['user_qq']!=$info['user_qq']) {
            if(preg_match('/^[1-9][0-9]{4,13}$/', $params['user_qq']) !== 1 ){
               $this->errorcode = EC_AD_USER_QQ_FORMAT_ERROR;
               return false;
            }

            $where['user_qq']   = $params['user_qq'];
            $where['user_id']       = array('neq',$params['user_id']);
            $count = $userModel->where($where)->count();
            if($count>0){
                $this->errorcode = EC_AD_USER_QQ_ALREADY_EXISTS;
                return false;
            }
            unset($where);
        }

        // 修改用户表信息
        if($params ['user_password'] != '') {
            if(!preg_match('/^(?!^[0-9]+$)(?!^[A-z]+$)[0-9A-Za-z]{6,12}$/', $params ['user_password'])){
                $this->errorcode = EC_AD_USER_PASSWORD_FORMAT_ERROR;
                return false;
            }
            $data ['user_password'] = md5($params ['user_password'] . $info ['user_salt']);
        }

        $data ['user_nickname'] = $params ['user_nickname'];
        $data ['user_mobile']   = $params ['user_mobile'];
        $data ['user_email']    = $params ['user_email'];
        $data ['user_qq']       = $params ['user_qq'];
        $data ['user_remark']   = $params ['user_remark'];
        $data ['user_status']   = $params ['user_status'];

        //只有会员可以设置为代理
        if($info['user_is_agent'] == Config::get('status.user_is_agent') ['no']) {
            $data ['user_is_agent'] = $params ['user_is_agent'];
        }

        if(isset($params['user_agent_check_status'])) {
            $data ['user_agent_check_status'] = $params ['user_agent_check_status'];
        }

        if(isset($params['user_contact_info'])) {
            $data ['user_contact_info'] = $params ['user_contact_info'];
        }

        $actionData = Loader::model('General','logic')->getActionData($params ['user_id'],$data,'User');

        //修改用户中心
        if (Env::get('passport.admin_open')) {
            $uData = [
                'password' => $params['user_password'],
                'nickname' => $params['user_nickname'],
                'email'    => $params['user_email'],
                'mobile'   => $params['user_mobile'],
                'qq'       => $params['user_qq'],
            ];
            $passport = new Passport();
            $result = $passport->systemUpdate($info['user_id'], $uData);
            if (!$result) {
                $this->errorcode = $passport->getErrorCode();
                $this->message = $passport->getErrorMessage();
                return false;
            }
        }

        $ret = $userModel->save($data, ['user_id' => $info['user_id']]);

        //记录行为
        Loader::model('General', 'logic')->actionLog('update_basicInfo', 'User', $info ['user_id'], MEMBER_ID, json_encode($actionData));

        return $ret;
    }

    /**
     * 新增银行资料
     *
     * @param
     *            $params
     * @return array
     */
    public function addBank($params) {
        $userBankRelationModel = Loader::model('UserBankRelation');

        foreach($params ['bank_list'] as $key => $val) {

            $where['ub_bank_account'] = $val ['bankAccount'];
            $where['ub_status'] = Config::get('status.bank_status') ['normal'];
            $count = $userBankRelationModel->where($where)->count();
            if($count>0){
                $this->errorcode = EC_AD_ADD_BANK_ACCOUNT_EXISTS;
                return false;
            }

            //验证银行资料
            if(empty($val ['bankAccount'])||empty($val ['bankUserName'])||empty($val ['address'])){
                $this->errorcode = EC_AD_ADD_BANK_ACCOUNT_INFO_EMPTY;
                return false;
            }

            // 修改用户银行表信息
            $data                       = [];
            $data ['user_id']           = $params ['user_id'];
            $data ['bank_id']           = $val ['bankId'];
            $data ['ub_bank_account']   = $val ['bankAccount'];
            $data ['ub_bank_user_name'] = $val ['bankUserName'];
            $data ['ub_address']        = $val ['address'];

            $ret = $userBankRelationModel->insertGetId($data);

            //记录行为
            Loader::model('General', 'logic')->actionLog('add_bankInfo', 'UserBankRelation', $params ['user_id'], MEMBER_ID, json_encode($data));
        }

        return $ret;
    }

    /**
     * 编辑银行资料
     *
     * @param
     *            $params
     * @return array
     */
    public function editBank($params) {
        $userBankRelationModel = Loader::model('UserBankRelation');

        foreach($params ['bank_list'] as $key => $val) {
            // 修改用户银行表信息
            $data                       = [];
            $data ['bank_id']           = $val ['bankId'];
            $data ['ub_bank_account']   = $val ['bankAccount'];
            $data ['ub_address']        = $val ['address'];

            $where['ub_status']       = Config::get('status.bank_status')['normal'];
            $where['ub_bank_account'] = $val['bankAccount'];
            $where['user_id']         = array('neq',$val ['uid']);
            $info = $userBankRelationModel->where($where)->find();
            if(!empty($info))
            {
                $this->errorcode = EC_AD_USER_BANK_ALREADY_EXISTS;
                return false;
            }

            $actionData = Loader::model('General','logic')->getActionData($val ['id'],$data,'UserBankRelation');

            $ret = $userBankRelationModel->where(['ub_id' => $val ['id']])->update($data);

            //记录行为
            Loader::model('General', 'logic')->actionLog('update_bankInfo', 'UserBankRelation', $val ['id'], MEMBER_ID, json_encode($actionData));
        }

        return $ret;
    }

    /**
     * 删除银行资料
     *
     * @param
     *            $params
     * @return array
     */
    public function delBank($params) {

        $userBankRelationModel = Loader::model('UserBankRelation');

        $data = $userBankRelationModel->where(['ub_id'=>$params['ub_id']])->find();

        $updateData['ub_status'] = USER_BANK_STATUS_DISABLE;

        $ret = Loader::model('UserBankRelation')->save($updateData, ['ub_id' => $params['ub_id'],'user_id' => $params ['user_id']]);

        //记录行为
        Loader::model('General', 'logic')->actionLog('del_bankInfo', 'UserBankRelation', $params ['user_id'], MEMBER_ID, json_encode($data));

        return $ret;

    }

    /**
     * 编辑返点信息
     *
     * @param
     *            $params
     * @return array
     */
    public function editRebate($params) {
        $userAutoRebateConfigModel = Loader::model('UserAutoRebateConfig');

        $userInfo = $this->getInfo($params ['user_id']);
        if(!$userInfo) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        if($userInfo['user_pid']&&$userInfo['user_pid'] != Config::get("common.default_agent_uid")){
            $agent_rebate = Loader::model('UserAutoRebateConfig','logic')->getUserRebateByUserId($userInfo['user_pid']);
        }

        $this->startTrans();

        $setRebate = [];
        $dataList  = [];
        foreach($params ['rebate_list'] as $val) {
            if(!empty($agent_rebate) && $val['categoryId'] != PC_EGG_CATEGORY_ID && $val['userRebate']>$agent_rebate[$val['categoryId']]){
                $this->rollback();
                $this->errorcode = EC_AD_USER_UPDATE_REBATE_GT_AGENT;
                return false;
            }
            // 修改用户返点信息
            $data                         = [];
            $data ['user_id']             = $userInfo ['user_id'];
            $data ['lottery_category_id'] = $val ['categoryId'];
            $data ['user_rebate']         = $val ['userRebate'];
            $data ['user_pid']            = $userInfo ['user_pid'];
            $dataList[]   = $data;
            $setRebate[$val ['categoryId']] = $val ['userRebate'];
        }
        if(false == $userAutoRebateConfigModel->insertAll($dataList, true)){
            $this->rollback();
            $this->errorcode = EC_AD_USER_UPDATE_REBATE_ERROR;
            return false;
        }

        if($userInfo['user_is_agent'] == 1){
            $this->modifyChildUserRebate($params ['user_id'], $setRebate);
            $this->modifyChildDomainRebate($params ['user_id'], $setRebate);
            $this->modifyChildLinkRebate($params ['user_id'], $setRebate);
        }

        if(!empty($this->getError())){
            $this->rollback();
            $this->errorcode = EC_AD_USER_UPDATE_REBATE_ERROR;
            return false;
        }

        $this->commit();

        //记录行为
        Loader::model('General', 'logic')->actionLog('update_rebate', 'UserAutoRebateConfig', $userInfo ['user_id'], MEMBER_ID, json_encode($dataList));

        return true;
    }


    private function modifyChildUserRebate($userId, $pRebateConfig){
        $logic = Loader::model('UserAutoRebateConfig','logic');
        $child_rebate = $logic->getListByPid($userId);
        $child_rebate = $child_rebate?collection($child_rebate)->toArray():[];

        if(!empty($child_rebate)){

            $data = [];
            $userIds = [];
            foreach ($child_rebate as $rebate_config){
                $lottery_category_id    = $rebate_config['lottery_category_id'];
                $child_category_rebate  = $rebate_config['user_rebate'];

                //如果有下级的返点数大于上级，那么相应的修改下级的返点数
                if(isset($pRebateConfig[$lottery_category_id]) && $child_category_rebate > $pRebateConfig[$lottery_category_id]){
                    $tmp = $rebate_config;
                    $tmp['user_rebate']           = $pRebateConfig[$lottery_category_id];
                    $data[] = $tmp;

                    $userIds[]                   = $rebate_config['user_id'];
                }
            }

            if(!empty($data))
                Loader::model('UserAutoRebateConfig')->insertAll($data,true);

            $is_agent_list = Loader::model('User')->getUserAgentInfo($userIds);

            if(!empty($is_agent_list)){
                foreach ($is_agent_list as $userId=>$is_agent){
                    if($is_agent == 1){
                        $this->modifyChildUserRebate($userId, $pRebateConfig);
                        $this->modifyChildDomainRebate($userId, $pRebateConfig);
                        $this->modifyChildLinkRebate($userId, $pRebateConfig);
                    }
                }
            }

        }
    }

    private function modifyChildDomainRebate($userId,$pRebateConfig){

        $logic = Loader::model('AgentDomainRebate','logic');
        $cur_rebate = $logic->getListByUserId($userId);
        $cur_rebate = $cur_rebate?collection($cur_rebate)->toArray():[];

        if(!empty($cur_rebate)){

            $data = array();
            foreach ($cur_rebate as $rebate_config){

                //判断当前代理返点是否下调返点，比当前代理域名返点数低，低则修正，下调
                if(isset($pRebateConfig[$rebate_config['category_id']]) && $rebate_config['rebate'] > $pRebateConfig[$rebate_config['category_id']]){
                    $temp = $rebate_config;
                    $temp['rebate'] = $pRebateConfig[$rebate_config['category_id']];
                    $data[] = $temp;
                }
            }
            if(!empty($data)){
                $logic->insertAll($data,true);
            }
        }

    }

    private function modifyChildLinkRebate($userId,$pRebateConfig){

        $logic = Loader::model('AgentLinkRebate','logic');
        $cur_rebate = $logic->getListByUserId($userId);
        $cur_rebate = $cur_rebate?collection($cur_rebate)->toArray():[];

        if(!empty($cur_rebate)){

            $data = array();
            foreach ($cur_rebate as $rebate_config){

                //判断当前代理返点是否下调返点，比当前代理链接code返点数低，低则修正，下调
                if(isset($pRebateConfig[$rebate_config['category_id']]) && $rebate_config['rebate'] > $pRebateConfig[$rebate_config['category_id']]){
                    $temp = $rebate_config;
                    $temp['rebate'] = $pRebateConfig[$rebate_config['category_id']];
                    $data[] = $temp;
                }
            }
            if(!empty($data)){
                $logic->insertAll($data,true);
            }
        }
    }



    /**
     * 删除
     *
     * @param
     *            $params
     * @return array
     */
    public function del($params) {

        $data = Loader::model('User')->where([
            'user_id' => $params ['user_id'],
        ])->find();

        $ret = Loader::model('User')->where([
            'user_id' => $params ['user_id'],
        ])->delete();

        Loader::model('General', 'logic')->actionLog('del_user', 'User', $params ['user_id'], MEMBER_ID, json_encode($data));
        return $ret;
    }

    /**
     * 修改状态
     *
     * @param
     *            $params
     * @return array
     */
    public function changeStatus($params) {

        $condition = [
            'user_id' => $params ['user_id'],
        ];

        $updateData ['user_status'] = $params ['user_status'];
        $ret = Loader::model('User')->save($updateData, $condition);

        if($ret && Env::get('passport.admin_open')) {
            $passport = new Passport();
            $passport->forceOffline($params ['user_id']);
        }

        //代理则同时禁用链接
        $isAgent      = Loader::model('User')->where($condition)->value('user_is_agent');
        if ($isAgent && ($params ['user_status'] == 0) ) {
            //代理网站不可用
            $updateDataAgd ['agd_status'] = 0;
            $agentDomainStop      = Loader::model('AgentDomain')->save($updateDataAgd, $condition);
            //注册链接不可用
            $updateDataAgl ['agl_status'] = 0;
            $agentLinkStop      = Loader::model('AgentLink')->save($updateDataAgl, $condition);
        }

        return $ret;
    }

    /**
     * 获取用户登陆日志列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getLoginLogList($params) {
        $condition = [];
        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['ull.ull_login_time'] = [
                [
                    'EGT',
                    $params ['start_date'],
                ],
                [
                    'ELT',
                    $params ['end_date'],
                ],
            ];
        }
        if(isset ($params ['user_name'])) {
            if ( empty( $params['isPreciseQuery']) ) {
                $condition ['u.user_name'] = [
                    'LIKE',
                    $params ['user_name'] . '%',
                ];
            }else{
                $condition ['u.user_name'] = $params ['user_name'];
            }

        }
        if(isset ($params ['ull_login_ip'])) {
            $condition ['ull.ull_login_ip'] = $params ['ull_login_ip'];
        }

        $userLoginLogModel = Loader::model('UserLoginLog');

        // 获取总条数
        $count = $userLoginLogModel->alias('ull')->join('User u', 'ull.user_id=u.user_id', 'LEFT')->where($condition)->count();

        $list = [];
        if($count>0){
            $list = $userLoginLogModel->alias('ull')->join('User u', 'ull.user_id=u.user_id', 'LEFT')->field('u.user_name,ull.*')->where($condition)->order('ull.ull_id desc')->limit($params ['num'])->page($params ['page'])->select();
        }

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 获取同IP用户列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getSameIpList($params) {
        $condition = [];
        if(isset ($params ['user_name'])) {
            $condition ['u.user_name'] = [
                'NEQ',
                $params ['user_name'],
            ];
        }
        if(isset ($params ['ull_login_ip'])) {
            $condition ['ull.ull_login_ip'] = $params ['ull_login_ip'];
        }

        $userLoginLogModel = Loader::model('UserLoginLog');

        // 获取总条数
        $count = $userLoginLogModel->alias('ull')->join('User u', 'ull.user_id=u.user_id', 'LEFT')->where($condition)->count();

        $list = $userLoginLogModel->alias('ull')->join('User u', 'ull.user_id=u.user_id', 'LEFT')->field('u.user_name,ull.*')->where($condition)->order('ull.ull_id desc')->limit($params ['num'])->page($params ['page'])->select();

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 获取待审核代理列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getAgentPendingList($params) {
        $userModel = Loader::model('User');
        $condition = [
            'u.user_is_agent' => 1,
        ];

        if(isset ($params ['user_name'])) {
            $condition ['u.user_name'] = [
                'LIKE',
                $params ['user_name'] . '%',
            ];
        }

        if(isset ($params ['user_agent_check_status'])) {
            $condition ['u.user_agent_check_status'] = $params['user_agent_check_status'];
        }

        // 获取总条数
        $count = $userModel->alias('u')->join('UserLevel ul', 'ul.ul_id=u.ul_id', 'LEFT')->join('UserExtend ue', 'ue.user_id=u.user_id', 'LEFT')->where($condition)->count();

        $list = $userModel->alias('u')->join('UserLevel ul', 'ul.ul_id=u.ul_id', 'LEFT')->join('UserExtend ue', 'ue.user_id=u.user_id', 'LEFT')->field('u.*,ul.ul_name,ue.ue_account_balance,ue.ue_discount_amount,ue.ue_recharge_amount,ue.ue_withdraw_amount')->where($condition)->order('user_id desc')->limit($params ['num'])->page($params ['page'])->select();

        if(!empty ($list)) {
            foreach($list as &$val) {
                //兼容PHP7.1
                $lastLoginIp = $val ['user_last_login_ip'] != '' ? $val ['user_last_login_ip'] : 0;
                $val ['user_last_login_ip']   = long2ip($lastLoginIp);
                // 获取上级名称
                if ( !empty($val ['user_pid']) ) {
                    $parentInfo = $this->getInfo($val ['user_pid']);
                    $val ['parent_user_name'] = $parentInfo ['user_name'];
                } else {
                    $val ['parent_user_name'] = '';
                }

            }
        }

        $returnArr = [
            'totalCount' => $count,
            'list'       => $list,
        ];

        return $returnArr;
    }

    /**
     * 审核代理
     *
     * @param
     *            $params
     * @return array
     */
    public function changeAgentStatus($params) {
        $updateData ['user_agent_check_status'] = $params ['user_agent_check_status'];

        $actionData = Loader::model('General','logic')->getActionData($params ['user_id'],$updateData,'User');

        //记录行为
        Loader::model('General', 'logic')->actionLog('user_agent_check', 'User', $params ['user_id'], MEMBER_ID, json_encode($actionData));

        $ret = Loader::model('User')->save($updateData, [
            'user_id' => $params ['user_id'],
        ]);

        return $ret;
    }

    /**
     * 获取会员层级列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserLevelList($params) {
        $userLevelModel = Loader::model('UserLevel');
        $condition      = [
            'ul_status' => 1,
        ];

        if(isset ($params ['ul_name'])) {
            $condition ['ul_name'] = [
                'LIKE',
                '%' . $params ['ul_name'] . '%',
            ];
        }

        $list = $userLevelModel->field('ul_id,ul_name,ul_description,ul_user_create_start_time,ul_user_create_end_time,ul_user_recharge_start_time,ul_user_recharge_end_time,ul_recharge_count,ul_recharge_amount,ul_recharge_max_amount,ul_withdraw_count,ul_withdraw_amount,ul_recharge_highest,ul_recharge_lowest,ul_user_count,ul_status,ul_default,ul_rebate_percentage')->where($condition)->order('ul_id desc')->select();
        foreach ($list as $k => $v) {
           if (!$v['ul_rebate_percentage']) {
               $list[$k]['ul_rebate_percentage'] = 0;
           }else{
               $ul_rebate_percentage = json_decode($v['ul_rebate_percentage']);
               $list[$k]['ul_rebate_percentage'] = $ul_rebate_percentage->win / 0.01;
           }
        }
        return $list;
    }

    /**
     * 新增用户层级
     *
     * @param
     *            $params
     * @return bool
     */
    public function addUserLevel($params) {
        $check = $this->checkParams($params);
        if ( $check == false ) {
            return false;
        }
        // 入库
        $data ['ul_name']                     = $params ['ul_name'];
        $data ['ul_description']              = $params ['ul_description'];
        $data ['ul_user_create_start_time']   = $params ['ul_user_create_start_time'];
        $data ['ul_user_create_end_time']     = $params ['ul_user_create_end_time'];
        $data ['ul_user_recharge_start_time'] = $params ['ul_user_recharge_start_time'];
        $data ['ul_user_recharge_end_time']   = $params ['ul_user_recharge_end_time'];
        $data ['ul_recharge_count']           = $params ['ul_recharge_count'];
        $data ['ul_recharge_amount']          = $params ['ul_recharge_amount'];
        $data ['ul_withdraw_count']           = $params ['ul_withdraw_count'];
        $data ['ul_withdraw_amount']          = $params ['ul_withdraw_amount'];
        $data ['ul_status']                   = $params ['ul_status'];
        $data ['ul_default']                  = $params ['ul_default'];

        $userLevelModel = Loader::model('UserLevel');
        $ret            = $userLevelModel->save($data);
        if($ret) {
            $userLevelInfo = [
                'ul_id' => $userLevelModel->ul_id,
            ];

            return $userLevelInfo;
        }
        $this->errorcode = EC_AD_ADD_USER_LEVEL_ERROR;

        return false;
    }

    /**
     * 编辑用户层级
     *
     * @param
     *            $params
     * @return array
     */
    public function editUserLevel($params) {
        // 获取用户层级信息
        $info = Loader::model('UserLevel')->where([
            'ul_id' => $params ['ul_id'],
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_USER_LEVEL_NONE;

            return false;
        }

        // 修改用户层级表信息
        $data ['ul_name']                     = $params ['ul_name'];
        $data ['ul_description']              = $params ['ul_description'];
        $data ['ul_user_create_start_time']   = $params ['ul_user_create_start_time'];
        $data ['ul_user_create_end_time']     = $params ['ul_user_create_end_time'];
        $data ['ul_user_recharge_start_time'] = $params ['ul_user_recharge_start_time'];
        $data ['ul_user_recharge_end_time']   = $params ['ul_user_recharge_end_time'];
        $data ['ul_recharge_count']           = $params ['ul_recharge_count'];
        $data ['ul_recharge_amount']          = $params ['ul_recharge_amount'];
        $data ['ul_withdraw_count']           = $params ['ul_withdraw_count'];
        $data ['ul_withdraw_amount']          = $params ['ul_withdraw_amount'];
        $ret = Loader::model('UserLevel')->save($data, [
            'ul_id' => $info ['ul_id'],
        ]);

        return $ret;
    }

    /**
     * 审核代理
     *
     * @param
     *            $params
     * @return array
     */
    public function setDefaultUserLevel($params) {
        // 将自身设置为默认
        $updateData ['ul_default'] = $params ['ul_default'];
        Loader::model('UserLevel')->save($updateData, [
            'ul_id' => $params ['ul_id'],
        ]);

        // 将其他设置为不默认
        $updateData ['ul_default'] = 0;
        Loader::model('UserLevel')->save($updateData, [
            'ul_id' => [
                'NEQ',
                $params ['ul_id'],
            ],
        ]);

        return true;
    }

    /**
     * 获取下级用户
     *
     * @param
     *            $params
     * @return array
     */
    public function nextUsers($userId) {
        return Loader::model('User')->where([
            'user_pid' => $userId,
        ])->column('user_id');

    }

    //old 获取团队用户ID
    public function getAllNextUser(array $userId, $where = []) {
        $condition = [
            'user_pid' => [
                'IN',
                $userId,
            ],
        ];
        if(!empty ($where)) {
            $condition = array_merge($condition, $where);
        }
        $nextUsers = Loader::model('User')->where($condition)->field('user_id')->select();

        if(!empty ($nextUsers)) {
            foreach($nextUsers as $val) {
                $this->user_ids = array_merge($this->user_ids, [
                    $val ['user_id'],
                ]);
            }

            $this->getAllNextUser($nextUsers);
        } else
            return true;
    }


    /**
     * old获取团队全部的UID
     *
     * $own 是否包含自己
     */
    public function getAllNextUsers($userId, $own = true, $where = []) {
        $this->user_ids = [];

        if($own) $this->user_ids [] = $userId;

        $this->getAllNextUser([
            $userId,
        ], $where);

        return $this->user_ids;
    }

    /**
     * 获取会员层级支付配置列表
     *
     * @return array
     */
    public function getLevelPayConfigList() {

        $list      = Loader::model('PayConfig')->select();

        return $list;
    }

    /**
     * 获取会员层级支付配置
     *
     * @param
     *            $params
     * @return array
     */
    public function getLevelPayConfig($params) {
        $condition = [
            'ul_id' => $params ['ul_id'],
        ];
        $info      = Loader::model('PayConfig')->where($condition)->find();

        if(empty($info)) {
            $info['ul_id'] = $params ['ul_id'];
        }

        return $info;
    }

    /**
     * 编辑会员层级支付配置
     *
     * @param
     *            $params
     * @return array
     */
    public function editLevelPayConfig($params) {
        $check = $this->checkParams($params);
        if ( $check == false ) {
            return false;
        }
        // 获取用户层级信息
        $info = Loader::model('UserLevel')->where([
            'ul_id' => $params ['ul_id'],
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_USER_LEVEL_NONE;

            return false;
        }

        $data = array_filter($params);

        Loader::model('PayConfig')->insert($data, true);

        return true;
    }

    //校验参数限制范围
    public function checkParams($params='')
    {
        if ($params ['pc_online_recharge_min_amount'] < 1) {
            $this->errorcode = EC_AD_USER_RECHARGE_MIN_AMOUNT_ERROR;
            return false;
        }

        if ( $params['pc_relax_amount'] < 0.01 || $params['pc_relax_amount'] > 1 ) {
            $this->errorcode = EC_AD_USER_RELAX_AMOUNT_ERROR;
            return false;
        }
        return true;
    }

    public function lockUserLevel($params) {
        $operate = $params['operate'];
        $userIds = is_array($params['ids']) ? $params['ids'] : explode(',', $params['ids']);
        if($operate == 1) {
            $data['user_level_status'] = Config::get('status.user_level_status')['lock'];
        } else {
            $data['user_level_status'] = Config::get('status.user_level_status')['normal'];
        }

        $condition = [
            'user_id' => [
                'in',
                $userIds,
            ],
        ];

        $result = Loader::model('User')->where($condition)->update($data);

        if($result === false) {
            $this->errorcode = EC_DATABASE_ERROR;
        }
    }

    /**
     * 分层
     *
     * @param
     *            $params
     * @return array
     */
    public function updateUserLevel($params) {

        $curr_level_id = $params ['curr_level_id'];
        $ul_ids        = $params['ul_id'];

        // 获取用户层级信息
        $level_info = Loader::model('UserLevel')->where([
            'ul_id' => $curr_level_id,
        ])->find();
        if(!$level_info) {
            $this->errorcode = EC_AD_USER_LEVEL_NONE;

            return false;
        }

        if($ul_ids) {
            $reg_staarttime     = date('Y-m-d 00:00:00', strtotime($level_info['ul_user_create_start_time']));
            $reg_endtime        = date('Y-m-d 23:59:59', strtotime($level_info['ul_user_create_end_time']));
            $recharge_starttime = date('Y-m-d 00:00:00', strtotime($level_info['ul_user_recharge_start_time']));;
            $recharge_endtime = date('Y-m-d 23:59:59', strtotime($level_info['ul_user_recharge_end_time']));;

            $userIds = $this->_getUserIdsByReg($ul_ids, $reg_staarttime, $reg_endtime);
            if(is_string($userIds)){
                $userIds = explode(',',$userIds);
            }
            if($userIds) {
                //jesse 修复 ：只设置ul_recharge_count = 0，ul_recharge_amount>0的情况
                if($level_info['ul_recharge_count'] > 0 || $level_info['ul_recharge_amount']>0){
                    $userIds = $this->_getUserIdsByRecharge($userIds, $recharge_starttime, $recharge_endtime, $level_info);
                }
                if($userIds) {
                    if($level_info['ul_withdraw_count'] > 0 || $level_info['ul_withdraw_amount']>0){
                        $userIds = $this->_getUserIdsByWithdraw($userIds, $recharge_starttime, $recharge_endtime, $level_info);
                    }
                } else {
                    $this->errorcode = EC_AD_USER_RECHARGE_NONE;
                    return false;
                }
                if($userIds && count($userIds) > 0) {
                    //行为日志数据

                    $actionData = Loader::model('User')->where('user_id','in',$userIds)->field("user_id,ul_id")->select();

                    $result = $this->modifyUserLevel($userIds, $curr_level_id);

                    $this->updateUserLevelCount();
                    if($result) {

                        //行为日志
                        $actionData['_change_'] = json_encode(['ul_id'=>$curr_level_id]);

                        Loader::model('General', 'logic')->actionLog('update_user_level', 'User', $userIds[0], MEMBER_ID, json_encode($actionData));

                        return count($userIds);
                    } else {
                        $this->errorcode = EC_AD_UPDATE_USER_LEVEL_ERROR;
                    }

                } else {
                    $this->errorcode = EC_AD_USER_WITHDRAW_NONE;
                }

            } else {
                $this->errorcode = EC_AD_USER_REG_DATE_NONE;
            }

        } else {
            $this->errorcode = EC_AD_USER_LEVEL_NEED_ONE;
        }

        return false;
    }


    /**
     * new分层
     *
     * @param
     *$params
     * @return array
     */
    public function newUpdateUserLevel($params){
        $curr_level_id = $params ['curr_level_id'];
        $ul_ids        = $params['ul_id'];
        // 获取用户层级信息
        $level_info = Loader::model('UserLevel')->where([
            'ul_id' => $curr_level_id,
        ])->find();
        if(!$level_info) {
            $this->errorcode = EC_AD_USER_LEVEL_NONE;
            return false;
        }
        if($ul_ids) {
            $reg_starttime      = date('Y-m-d 00:00:00', strtotime($level_info['ul_user_create_start_time']));
            $reg_endtime        = date('Y-m-d 23:59:59', strtotime($level_info['ul_user_create_end_time']));
            $recharge_starttime = date('Y-m-d 00:00:00', strtotime($level_info['ul_user_recharge_start_time']));
            $recharge_endtime   = date('Y-m-d 23:59:59', strtotime($level_info['ul_user_recharge_end_time']));

            $user_condition                        = [];
            $user_condition['u.ul_id']             = ['IN',$ul_ids];
            $user_condition['u.user_createtime']   = ['BETWEEN',[$reg_starttime,$reg_endtime]];
            $user_condition['u.user_status']       = Config::get('status.user_status')['enable'];
            $user_condition['u.user_level_status'] = Config::get('status.user_level_status')['normal'];

            $recharge_condition                       = [];
            $recharge_condition['urr_status']     = Config::get('status.recharge_status')['success'];
            $recharge_condition['urr_createtime'] = ['between', [$recharge_starttime, $recharge_endtime]];

            $user_condition['ue.ue_recharge_amount']    = ['EGT',$level_info['ul_recharge_amount']];
            $user_condition['ue.ue_withdraw_amount']    = ['EGT',$level_info['ul_withdraw_amount']];
            $user_condition['ue.ue_recharge_count']     = ['EGT',$level_info['ul_recharge_count']];
            $user_condition['ue.ue_withdraw_count']     = ['EGT',$level_info['ul_withdraw_count']];
            //先判断目标层级要求
            if($level_info['ul_recharge_count'] > 0 || $level_info['ul_recharge_amount']>0){
                $userIds = Loader::model('User')->alias('u')->join('__USER_EXTEND__ ue','u.user_id = ue.user_id','left')
                    ->where($user_condition)->where('u.user_id','IN',function($query) use ($recharge_condition){
                    $query->table('ds_user_recharge_record')->where($recharge_condition)->group('user_id')->field('user_id');
                })->column('u.user_id');
            }else{
               $userIds = Loader::model('User')->alias('u')->join('__USER_EXTEND__ ue','u.user_id = ue.user_id','left')
                   ->where($user_condition)->column('u.user_id');  
            }

            if($userIds && count($userIds) > 0) {
                //行为日志数据分批查询修改
                $actionData = array();
                if(count($userIds)>Config::get('common.per_update_user_ids')){
                    $chunkUserIds = array_chunk($userIds,Config::get('common.per_update_user_ids'));
                    foreach ($chunkUserIds as $value){
                        $litte_infos = Loader::model('User')->where('user_id','in',$value)->field("user_id,ul_id")->select();
                        if(!empty($litte_infos)){
                            $actionData = array_merge($actionData,$litte_infos);
                        }
                        $result = $this->modifyUserLevel($value, $curr_level_id);
                    }
                }else {
                    $actionData = Loader::model('User')->where('user_id', 'in', $userIds)->field("user_id,ul_id")->select();
                    $result = $this->modifyUserLevel($userIds, $curr_level_id);
                }
                $this->updateUserLevelCount();
                if($result) {
                    //行为日志
                    if(count($actionData)>Config::get('common.per_log_user_ids')){
                        $chunkActionData = array_chunk($actionData,Config::get('common.per_log_user_ids'));
                        foreach ($chunkActionData as $val){
                            $val['_change_'] = json_encode(['ul_id'=>$curr_level_id]);
                            Loader::model('General', 'logic')->actionLog('update_user_level', 'User', $userIds[0], MEMBER_ID, json_encode($val));
                        }
                    }else{
                        Loader::model('General', 'logic')->actionLog('update_user_level', 'User', $userIds[0], MEMBER_ID, json_encode($actionData));
                    }
                    return count($userIds);
                } else {
                    $this->errorcode = EC_AD_UPDATE_USER_LEVEL_ERROR;
                }
            } else {
                $this->errorcode = EC_AD_USER_WITHDRAW_NONE;
            }
        } else {
            $this->errorcode = EC_AD_USER_LEVEL_NEED_ONE;
        }
        return false;
    }

    public function modifyUserLevel($userIds, $level_id) {
        $condition            = array();
        $condition['user_id'] = array(
            'in',
            $userIds
        );

        $data          = array();
        $data['ul_id'] = $level_id;

        return Loader::model('User')->where($condition)->update($data);
    }

    public function updateUserLevelCount() {
        $params      = [];
        $level_list  = $this->getUserLevelList($params);
        $level_count = $this->getUserCountGroupByUserLevelId();
        $level_count = reindex_array($level_count, 'ul_id');

        foreach($level_list as $level_info) {
            $ul_id                        = $level_info['ul_id'];
            $update_data                  = array();
            $update_data['ul_id']         = $ul_id;
            $update_data['ul_user_count'] = intval($level_count[$ul_id]['user_count']);
            Loader::model('UserLevel')->where(['ul_id' => $ul_id])->update($update_data);
        }
    }

    public function getUserCountGroupByUserLevelId() {
        $fields = array(
            'ul_id',
            'count(*)' => 'user_count'
        );

        return Collection(model('User')->field($fields)->group('ul_id')->select())->toArray();

    }

    private function _getUserIdsByReg($ul_ids, $reg_staarttime, $reg_endtime) {
        $user_condition                      = array();
        $user_condition['ul_id']             = array(
            'in',
            $ul_ids
        );
        $user_condition['user_createtime']   = array(
            'between',
            array(
                $reg_staarttime,
                $reg_endtime
            )
        );
        $user_condition['user_status']       = Config::get('status.user_status')['enable'];
        $user_condition['user_level_status'] = Config::get('status.user_level_status')['normal'];
        $userIds                            = $this->getUserIdsByCondition($user_condition);

        return $userIds;
    }

    private function _getUserIdsByRecharge($userIds, $recharge_starttime, $recharge_endtime, $level_info) {

        $recharge_infos = Loader::model('UserRechargeRecord', 'logic')->getStatistics($userIds, $recharge_starttime, $recharge_endtime);

        $userIds    = array();
        foreach($recharge_infos as $recharge_info) {
            if($recharge_info['recharge_count'] >= $level_info['ul_recharge_count'] && $recharge_info['recharge_total'] >= $level_info['ul_recharge_amount']) {
                $userIds[] = $recharge_info['user_id'];
            }
        }

        return $userIds;
    }

    private function _getUserIdsByWithdraw($userIds, $recharge_starttime, $recharge_endtime, $level_info) {

        $withdraw_infos = Loader::model('UserWithdrawRecord', 'logic')->getStatistics($userIds, $recharge_starttime, $recharge_endtime);

        $userIds       = array();
        foreach($withdraw_infos as $withdraw_info) {
            if($withdraw_info['withdraw_count'] >= $level_info['ul_withdraw_count'] && $withdraw_info['withdraw_total'] >= $level_info['ul_withdraw_amount']) {
                $userIds[] = $withdraw_info['user_id'];
            }
        }

        return $userIds;
    }

    public function getUserIdsByCondition($condition) {
        $result = Loader::model('User')->where($condition)->field('user_id')->select();

        $userIds = [];
        foreach($result as $val) {
            $userIds[] = $val['user_id'];
        }

        return implode(',', $userIds);
    }

    /**
     * 回归
     *
     * @param
     *            $params
     * @return array
     */
    public function regressUserLevel($params) {
        $id = $params['ul_id'];

        $user_count = $this->getUserCountByUserLevelId($id);
        if($user_count <= 0) {
            $this->errorcode = EC_AD_USER_LEVEL_NO_USER;

            return false;
        }
        $default_level = $this->getUserLevelDefault();
        $result        = $this->setRegress($id, $default_level['ul_id']);
        if(false !== $result) {
            $this->updateUserLevelCount();

            return $result;

        } else {
            $this->errorcode = EC_AD_REGRESS_USER_LEVEL_ERROR;

            return false;
        }
    }

    /**
     * 删除层级
     *
     * @param
     *            $params
     * @return array
     */
    public function delUserLevel($params) {
        $id = $params['ul_id'];

        $user_count = $this->getUserCountByUserLevelId($id);
        if($user_count > 0) {
            $this->errorcode = EC_AD_USER_LEVEL_HAD_USER;
            return false;
        }
        $ul_status = Config::get('status.ul_status')['deleted'];
        $result = Loader::model('UserLevel')->where(['ul_id' => $id])->update(['ul_status' => $ul_status]);
        if($result !== 0) {
            $this->errorcode = EC_AD_SUCCESS;
            return false;
        } else {
            $this->errorcode = EC_AD_DELETE_USER_LEVEL_ERROR;
            return false;
        }
    }

    public function getUserCountByUserLevelId($level_id) {
        $condition          = array();
        $condition['ul_id'] = $level_id;

        return Loader::model('User')->where($condition)->count();
    }

    public function getUserLevelDefault() {
        $condition               = array();
        $condition['ul_default'] = 1;

        return Loader::model('UserLevel')->where($condition)->find();
    }

    public function setRegress($ul_id, $default_ul_id) {
        $condition                      = array();
        $condition['ul_id']             = $ul_id;
        $condition['user_level_status'] = Config::get('status.user_level_status')['normal'];

        $data          = array();
        $data['ul_id'] = $default_ul_id;

        return Loader::model('User')->where($condition)->update($data);
    }

    /**
     * 编辑用户真实姓名
     *
     * @param
     *            $params
     * @return array
     */
    public function editUserRealName($params) {
        $userModel = Loader::model('User');

        // 获取用户信息
        $info = $userModel->where([
            'user_id' => $params ['user_id'],
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;
            return false;
        }

        if($info['user_realname'] == $params ['user_realname']){
            return true;
        }

        //20171023后台不管配置，都可以修改
        //真实姓名去重复
        /*if(!empty($params ['user_realname'])){
            $conf = Loader::model('ConfigManagement','logic')->getMapByName('IS_VERIFY_REALNAME');
            if($conf['value']){
                $condition['user_realname'] = $params ['user_realname'];
                $condition['user_id']       = array('neq',$params['user_id']);
                $count  = $userModel->where($condition)->count();
                if($count>0){
                    $this->errorcode = EC_AD_USER_REAL_NAME_ALREADY_EXISTS;
                    return false;
                }
            }
        }*/

        // 修改用户表信息
        $data ['user_realname'] = $params ['user_realname'];

        $actionData = Loader::model('General','logic')->getActionData($params ['user_id'],$data,'User');

        $result = $userModel->save($data, [
            'user_id' => $info ['user_id'],
        ]);

        if($result!=false){
            //银行卡关联表
            Loader::model('UserBankRelation','logic')->modifyBankUserName($info ['user_id'],$params ['user_realname']);

            //记录行为
            Loader::model('General', 'logic')->actionLog('update_user_realname', 'User', $info ['user_id'], MEMBER_ID, json_encode($actionData));

            return true;
        }

        $this->errorcode = EC_AD_UPDATE_ERROR;
        return false;

    }

    /**
     * 编辑用户资金密码
     *
     * @param
     *            $params
     * @return array
     */
    public function editUserFundsPassword($params) {
        $userModel = Loader::model('User');

        // 获取用户信息
        $info = $userModel->where([
            'user_id' => $params ['user_id'],
        ])->find();
        if(!$info) {
            $this->errorcode = EC_AD_LOGIN_USER_NONE;

            return false;
        }

        // 修改用户表信息
        $data ['user_funds_password'] = md5($params ['user_funds_password'] . $info ['user_funds_salt']);

        $actionData = Loader::model('General','logic')->getActionData($params ['user_id'],$data,'User');

        $ret = $userModel->save($data, [
            'user_id' => $info ['user_id'],
        ]);

        //记录行为
        Loader::model('General', 'logic')->actionLog('update_fundspassword', 'User', $info ['user_id'], MEMBER_ID, json_encode($actionData));

        return $ret;
    }

    /**
     * 编辑用户自身层级 (会员详情-修改会员层级)
     *
     * @param
     *            $params
     * @return array
     */
    public function editUserSelfLevel($params) {

        foreach($params ['user_id'] as $val) {
            $data ['ul_id'] = $params ['ul_id'];

            $userModel = Loader::model('User');

            $originalUlId = $userModel->where(['user_id'=>$val])->value("ul_id");

            $ret = $userModel->where(['user_id' => $val,'user_level_status' => Config::get('status.user_level_status')['normal']])->update($data);
            if($ret) {
                Loader::model('UserLevel','logic')->setIncUserCountByUlId($params ['ul_id']);
                Loader::model('UserLevel','logic')->setDecUserCountByUlId($originalUlId);

                //行为日志
                $actionData = [
                    'ul_id'    => $originalUlId,
                    '_change_' =>json_encode($data)
                ];

                //记录行为
                Loader::model('General', 'logic')->actionLog('update_user_level', 'User', $val, MEMBER_ID, json_encode($actionData));
            }else {
                return false;
            }
        }

        return true;
    }


    /**
    +---------------------------
     * 编辑会员层级 *new (会员层级-会员人数)
    +---------------------------
     * @param $params
     * @return array
     * @author jesse
     */
    public function editUserListLevel($params) {

        $uCount = count($params['user_id']);
        if($uCount == 0){
            $this->errorcode = EC_AD_UPDATE_USER_LEVEL_USER_NONE;
            return fasle;
        }


        $data ['ul_id'] = $params ['ul_id'];

        // 目标用户层级信息
        $levelInfo = Loader::model('UserLevel')->where($data)->find();
        if(!$levelInfo) {
            $this->errorcode = EC_AD_USER_LEVEL_NONE;
            return false;
        }

        $regStartTime      = date('Y-m-d 00:00:00', strtotime($levelInfo['ul_user_create_start_time']));
        $regEndTime        = date('Y-m-d 23:59:59', strtotime($levelInfo['ul_user_create_end_time']));
        $rechargeStartTime = date('Y-m-d 00:00:00', strtotime($levelInfo['ul_user_recharge_start_time']));;
        $rechargeEndTime   = date('Y-m-d 23:59:59', strtotime($levelInfo['ul_user_recharge_end_time']));;

        //检验注册日期
        $userIds = $this->_checkUserIdsByReg($params['user_id'],$regStartTime,$regEndTime);
        if($userIds){
            //检验存款
            if($levelInfo['ul_recharge_count'] > 0 || $levelInfo['ul_recharge_amount']>0){
                $userIds = $this->_getUserIdsByRecharge($userIds, $rechargeStartTime, $rechargeEndTime, $levelInfo);
            }

            if($userIds) {
                //检验提款
                if($levelInfo['ul_withdraw_count'] > 0 || $levelInfo['ul_withdraw_amount']>0){
                    $userIds = $this->_getUserIdsByWithdraw($userIds, $rechargeStartTime, $rechargeEndTime, $levelInfo);
                }
                if(is_string($userIds)){
                    $userIds = explode(',',$userIds);
                }

                $userCount = count($userIds);

                if($userIds && $userCount > 0) {
                    $userModel = Loader::model('User');

                    $originalUlId = $userModel->where(['user_id'=>$userIds[0]])->value("ul_id");

                    $result = $this->modifyUserLevel($userIds, $params ['ul_id']);

                    if($result) {

                        Loader::model('UserLevel','logic')->setIncUserCountByUlId($params ['ul_id'],$userCount);
                        Loader::model('UserLevel','logic')->setDecUserCountByUlId($originalUlId,$userCount);

                        //行为日志
                        $actionData = [];
                        foreach($userIds as $userId){
                            $tmp = [];
                            $tmp['user_id'] = $userId;
                            $tmp['ul_id']   = $originalUlId;
                            $actionData[] = $tmp;
                        }
                        $actionData['_change_'] = json_encode(['ul_id'=>$params ['ul_id']]);

                        Loader::model('General', 'logic')->actionLog('update_user_level', 'User', $userIds[0], MEMBER_ID, json_encode($actionData));

                        return $userCount;
                    } else {
                        $this->errorcode = EC_AD_UPDATE_USER_LEVEL_ERROR;
                    }

                } else {
                    $this->errorcode = $uCount==1?EC_AD_UPDATE_USER_LEVEL_WITHDRAW_NONE:EC_AD_USER_WITHDRAW_NONE;
                }

            }else{
                $this->errorcode = $uCount==1?EC_AD_UPDATE_USER_LEVEL_RECHARGE_NONE:EC_AD_USER_RECHARGE_NONE;
            }
        }else{
            $this->errorcode = $uCount==1?EC_AD_UPDATE_USER_LEVEL_REG_DATE_NONE:EC_AD_USER_REG_DATE_NONE;
        }

        return false;

    }


    /**
     * 分层条件：检验用户注册
     * @param $userIds
     * @param $regStartTime
     * @param $regEndTime
     */
    public function _checkUserIdsByReg($userIds,$regStartTime,$regEndTime){

        $condition = [];
        $condition['user_id']           = ['IN',$userIds];
        $condition['user_createtime']   = ['between',[$regStartTime,$regEndTime]];

        $condition['user_status']       = Config::get('status.user_status')['enable'];
        $condition['user_level_status'] = Config::get('status.user_level_status')['normal'];

        return $this->getUserIdsByCondition($condition);
    }




    /**
     * excel导出用户列表
     *
     * @param $list array 用户列表
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
     * 设置返水比例
     * @param $params
     * @return bool
     */
    public function setSportRebate($params){
        $data = [];
        $data['win']       = $params['proportion'] * 0.01;
        $data['win_half']  = $params['proportion'] * 0.5 * 0.01;
        $data['lose']      = $params['proportion'] * 0.01;
        $data['lose_half'] = $params['proportion'] * 0.5 * 0.01;
        $data['back']      = 0;
        $update['ul_rebate_percentage'] = json_encode($data);
        $result = Loader::model('UserLevel')->where(['ul_id'=>$params['ul_id']])->update($update);
        if (false === $result) {
            $this->errorcode = EC_AD_USER_SET_REBATE_PROPORTION_ERROR;
            return false;
        }

        return true;
    }

    /**
     * 获取用户体彩投注列表
     *
     * @param
     *            $params
     * @return array
     */
    public function getUserSportOrderList($params) {
        $condition ['so.so_user_id'] = $params ['so_user_id'];

        if(isset ($params ['so_st_id'])) {
            $condition ['so.so_st_id'] = $params ['so_st_id'];
        }

        if(isset ($params ['start_date']) && isset ($params ['end_date'])) {
            $condition ['so.so_create_time'] = [
                [
                    'EGT',
                    $params ['start_date'],
                ],
                [
                    'ELT',
                    $params ['end_date'],
                ],
            ];
        }
        if(isset ($params ['so_no'])) {
            $condition ['so.so_no'] = $params ['so_no'];
        }
        if(isset ($params ['so_status'])) {
            $condition ['so.so_status'] = $params ['so_status'];
        }

        $sportOrderModel = Loader::model('SportsOrders');

        // 获取总条数
        $count = $sportOrderModel->alias('so')->where($condition)->count();

        $list = $sportOrderModel->alias('so')->join('SportsTypes st', 'so.so_st_id=st.st_id', 'LEFT')->field('so.*,st.st_name,st_eng_name')->where($condition)->order('so.so_create_time desc')->limit($params ['num'])->page($params ['page'])->select();

        $data = [];
        foreach($list as $key => $order) {
            $data[$key]['id']                 = $order->so_id;
            $data[$key]['ip']                 = $order->so_ip;
            $data[$key]['order_no']           = $order->so_no;
            $data[$key]['bet_amount']         = $order->so_bet_amount;
            $data[$key]['to_win']             = $order->so_to_win;
            $data[$key]['bonus']              = $order->so_bonus;
            $data[$key]['bonus_no_principal'] = $order->so_bonus_no_principal;
            $data[$key]['event_type']         = Config::get('status.order_event_type_name')[$order->so_event_type];
            $data[$key]['remark']             = $order->so_remark;
            $data[$key]['bet_time']           = $order->so_create_time;
            $data[$key]['sport_type_name']    = $order->st_name;

            //订单状态
            $data[$key]['status'] = Loader::model('SportsOrders', 'logic')->getOrderStatus($order->so_status, $order->so_bet_status);

            //处理注单信息
            $betInfo = json_decode($order->so_bet_info, true);
            $data[$key]['bet_num'] = count($betInfo);

            //串关
            if ($data[$key]['bet_num'] > 1) {
                $data[$key]['have_result'] = 0;
                foreach($betInfo as $info) {
                    if (isset($info['calculate_result'])) {
                        $data[$key]['have_result'] ++;
                    }
                }
                //单关
            } else {
                //冠军
                if ($order->so_source_ids_from == Config::get('status.order_source_ids_from')['outright']) {
                    $betInfo[0] = Loader::model('Orders', $order->st_eng_name)->getOutrightOrderInfo($betInfo[0]);
                    $data[$key] = array_merge($data[$key], $betInfo[0]);

                    //单关
                } elseif ($order->so_source_ids_from == Config::get('status.order_source_ids_from')['schedule']) {
                    $betInfo[0] = Loader::model('Orders', $order->st_eng_name)->getScheduleOrderInfo($betInfo[0]);
                    $data[$key] = array_merge($data[$key], $betInfo[0]);
                }
            }
        }

        $returnArr = [
            'totalCount' => $count,
            'list'       => $data,
        ];

        return $returnArr;
    }

    /**
     * 强踢用户下线
     * @param $uid
     * @return bool
     */
    public function forceOffline($uid) {
        $passport = new Passport();
        $result = $passport->forceOffline($uid);
        if (!$result) { //下线成功
            $this->errorcode = $passport->getErrorCode();
            $this->message = $passport->getErrorMessage();
            return false;
        }
        return true;
    }

    /**
     * 刷新用户信息统计
     * @param uid
     * @return array
     */
    public function refreshUserStatistics($uid){
        $condition['user_id'] = $uid;

        $loginCount = Loader::model('UserLoginLog')->where($condition)->count();

        $result = Loader::model('UserExtend')->where($condition)->setField('ue_login_count', $loginCount);
        if($result) {
            return true;
        }else{
            return false;
        }

    }

}