<?php
/**
 * 用户账户业务逻辑
 * @createTime 2017/4/25 14:46
 */

namespace app\api\logic;

use passport\Passport;
use think\Config;
use think\Loader;
use think\Log;
use think\Model;

class Account extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 错误消息
     * @var string
     */
    public $message = '';

    /**
     * 每页显示数量
     * @var int
     */
    public $pageSize = 15;

    /**
     * 账户明细
     * @param string $type
     * @param $page
     * @return array|bool
     */
    public function getDetails($type = '', $page) {
        empty($page) && $page = 1;
        $where['user_id'] = USER_ID;
        if ($type) {
            $where['uar_transaction_type'] = $type;
        }
        $field = [
            'uar_transaction_type AS type_id',
            'uar_action_type AS action_type',
            'uar_amount AS amount',
            'uar_finishtime AS time',
            'uar_remark AS remark',
        ];

        //计算总数
        $total = Loader::model('UserAccountRecord')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $data = Loader::model('UserAccountRecord')
            ->where($where)
            ->field($field)
            ->page($page, $this->pageSize)
            ->order('uar_id', 'desc')
            ->select();
        if (!$data) {
            return ['total_page' => 0, 'result' => []];
        }
        $records = [];
        foreach($data as $item) {
            $date = substr($item['time'], 0, 7);
            $item['type_name'] = Config::get('status.account_record_transaction_type_name')[$item['type_id']];
            if ($item['action_type'] == Config::get('status.account_record_action_type')['deposit']) {
                $item['amount'] = '+' . $item['amount'];
            } elseif($item['action_type'] == Config::get('status.account_record_action_type')['fetch']) {
                $item['amount'] = '-' . $item['amount'];
            }
            if (isset($records[$date])) {
                $records[$date]['records'][] = $item;
            } else {
                $records[$date]['date'] = $date;
                $records[$date]['records'][] = $item;
            }
        }
        return ['total_page' => ceil($total / $this->pageSize), 'result' => array_values($records)];
    }

    /**
     * 获取账户明细类型
     * @return bool
     */
    public function getDetailTypes() {
        $types = Config::get('status.account_record_transaction_type_name');
        if (!$types) {
            $this->errorcode = EC_ACCOUNT_DETAIL_TYPES_EMPTY;
            return false;
        }
        foreach($types as $key => $val) {
            $type['type_id'] = $key;
            $type['type_name'] = $val;
            $data[] = $type;
        }
        return $data;
    }

    /**
     * 获取充值类型
     * @return bool
     */
    public function getRechargeTypes() {
        $types = Config::get('status.user_recharge_type_name');
        if (!$types) {
            $this->errorcode = EC_ACCOUNT_RECHARGE_TYPES_EMPTY;
            return false;
        }
        foreach($types as $key => $val) {
            $type['type_id'] = $key;
            $type['type_name'] = $val;
            $data[] = $type;
        }
        return $data;
    }

    /**
     * 获取提现类型
     * @return bool
     */
    public function getWithdrawTypes() {
        $types = Config::get('status.withdraw_status_name');
        if (!$types) {
            $this->errorcode = EC_ACCOUNT_WITHDRAW_TYPES_EMPTY;
            return false;
        }
        foreach($types as $key => $val) {
            $type['type_id'] = $key;
            $type['type_name'] = $val;
            $data[] = $type;
        }
        return $data;
    }

    /**
     * 用户充值列表
     * @param string $type 充值类型
     * @param int $page 页码
     * @return array|bool
     */
    public function getRechargeRecords($type = '', $page) {
        empty($page) && $page = 1;
        $where['user_id'] = USER_ID;
        if ($type) {
            $where['urr_type'] = $type;
        }
        $field = [
            'urr_id',
            'urr_no AS no',
            'urr_type AS type_id',
            'urr_amount AS amount',
            'urr_createtime AS time',
            'urr_reason AS remark',
            'urr_status AS status',
        ];

        //计算总数
        $total = Loader::model('UserRechargeRecord')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $data = Loader::model('UserRechargeRecord')
            ->where($where)
            ->field($field)
            ->page($page, $this->pageSize)
            ->order('urr_id', 'desc')
            ->select();
        if (!$data) {
            return ['total_page' => 0, 'result' => []];
        }
        $records = [];
        foreach($data as $item) {
            $date = substr($item['time'], 0, 7);
            $item['type_name'] = Config::get('status.user_recharge_type_name')[$item['type_id']];
            $item['status']    = Config::get('status.recharge_status_id')[$item['status']];

            if($item['status'] == Config::get('status.recharge_status')['success']) {
                $accountRecordInfo = Loader::model('UserAccountRecord')->getInfoBySource($item['urr_id'], Config::get('status.user_account_record_source_type')['recharge']);
                $item['beforeBalance'] = $accountRecordInfo['uar_before_balance'];
                $item['afterBalance'] = $accountRecordInfo['uar_after_balance'];
            }

            unset($item['urr_id']);

            if (isset($records[$date])) {
                $records[$date]['records'][] = $item;
            } else {
                $records[$date]['date'] = $date;
                $records[$date]['records'][] = $item;
            }
        }
        return ['total_page' => ceil($total / $this->pageSize), 'result' => array_values($records)];
    }

    /**
     * 返回pc端格式的用户充值列表
     * @param $params
     * @return array
     */
    public function getPcRechargeRecords($params) {
        empty($params['page']) && $params['page'] = 1;
        $where['user_id'] = USER_ID;
        if ($params['type']) {
            $where['urr_type'] = $params['type'];
        }
        if ($params['order_no']) {
            $where['urr_no'] = $params['order_no'];
        }

        if($params['status']){
            $where['urr_status'] = Config::get("status.recharge_status")[$params['status']];
        }
        $field = [
            'urr_id',
            'urr_no AS no',
            'urr_type AS type_id',
            'urr_amount AS amount',
            'urr_recharge_discount AS recharge_discount',
            'urr_createtime AS time',
            'urr_remark AS remark',
            'urr_status AS status_id',
        ];
        if(($params['start_time'] && (time()-strtotime($params['start_time']) > 60*60*24*14)) || !$params['start_time']){
            $date= date_create();
            date_sub($date,date_interval_create_from_date_string("14 days"));
            $params['start_time'] =  date_format($date,"Y-m-d");
        }

        if(!$params['end_time'] || ($params['end_time'] && (time() - strtotime($params['end_time']) <0 ))){
            $params['end_time'] = date('Y-m-d');
        }

        if ($params['start_time'] && $params['end_time']) {

            $where['urr_createtime'] = ['between', [$params['start_time'] . ' 00:00:00', $params['end_time'] . ' 23:59:59']];
        } else {
            if($params['start_time']) {
                $where['urr_createtime'] = ['egt', $params['start_time'] . ' 00:00:00'];
            }
            if($params['end_time']) {
                $where['urr_createtime'] = ['elt', $params['end_time'] . ' 23:59:59'];
            }
        }


        //计算总数
        $total = Loader::model('UserRechargeRecord')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }
        $data = Loader::model('UserRechargeRecord')
            ->where($where)
            ->field($field)
            ->page($params['page'], $this->pageSize)
            ->order('urr_id', 'desc')
            ->select();
        if (!$data) {
            return ['total_page' => 0, 'result' => []];
        }

        foreach($data as $key => $item) {
            $data[$key]['type_name'] = Config::get('status.user_recharge_type_name')[$item['type_id']];
            $data[$key]['status_name']    = Config::get('status.recharge_status_name')[$item['status_id']];

            if($item['status_id'] == Config::get('status.recharge_status')['success']) {
                $accountRecordInfo = Loader::model('UserAccountRecord')->getInfoBySource($item['urr_id'], Config::get('status.user_account_record_source_type')['recharge']);
                $data[$key]['beforeBalance'] = $accountRecordInfo['uar_before_balance'];
                $data[$key]['afterBalance'] = $accountRecordInfo['uar_after_balance'];
            }
        }
        return ['total_page' => ceil($total / $this->pageSize), 'result' => $data];
    }

    /**
     * 用户提现记录列表
     * @param string $type 类型
     * @param int $page 页码
     * @return array|bool
     */
    public function getWithdrawRecords($type = '', $page) {
        empty($page) && $page = 1;
        $where['user_id'] = USER_ID;
        if ($type) {
            $where['uwr_status'] = $type;
        }
        $field = [
            'uwr_no AS no',
            'uwr_real_amount AS amount',
            'uwr_createtime AS time',
            'uwr_touser_remark AS remark',
            'uwr_status AS type_id',
        ];

        //计算总数
        $total = Loader::model('UserWithdrawRecord')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $data = Loader::model('UserWithdrawRecord')
            ->where($where)
            ->field($field)
            ->page($page, $this->pageSize)
            ->order('uwr_id', 'desc')
            ->select();
        if (!$data) {
            return ['total_page' => 0, 'result' => []];
        }
        $records = [];
        foreach($data as $item) {
            $date = substr($item['time'], 0, 7);
            $item['type_name'] = Config::get('status.withdraw_status_name')[$item['type_id']];
            if (isset($records[$date])) {
                $records[$date]['records'][] = $item;
            } else {
                $records[$date]['date'] = $date;
                $records[$date]['records'][] = $item;
            }
        }
        return ['total_page' => ceil($total / $this->pageSize), 'result' => array_values($records)];
    }

    /**
     * pc端用户提现记录列表
     * @param $params
     * @return array
     */
    public function getPcWithdrawRecords($params) {
        empty($params['page']) && $params['page'] = 1;
        $where['user_id'] = USER_ID;
        if ($params['type']) {
            $where['uwr_status'] = $params['type'];
        }
        if ($params['order_no']) {
            $where['uwr_no'] = $params['order_no'];
        }
        $field = [
            'uwr_no AS no',
            'uwr_real_amount AS amount',
            'uwr_createtime AS time',
            'uwr_remark AS remark',
            'uwr_touser_remark AS touser_remark',
            'uwr_status AS status_id',
            'uwr_type AS type_id',
        ];

        if ($params['start_time'] && $params['end_time']) {
            $where['uwr_createtime'] = ['between', [$params['start_time'] . ' 00:00:00', $params['end_time'] . ' 23:59:59']];
        } else {
            if($params['start_time']) {
                $where['uwr_createtime'] = ['egt', $params['start_time'] . ' 00:00:00'];
            }
            if($params['end_time']) {
                $where['uwr_createtime'] = ['elt', $params['end_time'] . ' 23:59:59'];
            }
        }

        //计算总数
        $total = Loader::model('UserWithdrawRecord')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $data = Loader::model('UserWithdrawRecord')
            ->where($where)
            ->field($field)
            ->page($params['page'], $this->pageSize)
            ->order('uwr_id', 'desc')
            ->select();
        if (!$data) {
            return ['total_page' => 0, 'result' => []];
        }

        foreach($data as $key => $item) {
            $data[$key]['type_name']   = Config::get('status.user_withdraw_type_name')[$item['type_id']];
            $data[$key]['status_name'] = Config::get('status.withdraw_status_name')[$item['status_id']];

            //线上入款的话，用touser_remark替代remark
            if($item['type_id'] == Config::get('status.user_withdraw_type')['online']) {
                $data[$key]['remark'] = $item['touser_remark'];
            }
            unset($data[$key]['touser_remark']);

        }
        return ['total_page' => ceil($total / $this->pageSize), 'result' => array_values($data)];
    }

    /**
     * 修改密码
     * @param $oldPassword 原始密码
     * @param $newPassword 新密码
     * @param $newPasswordConfirm 新密码
     * @return bool
     */
    public function changePassword($oldPassword, $newPassword, $newPasswordConfirm) {
        if ($newPassword != $newPasswordConfirm) {
            $this->errorcode = EC_USER_PASSWORD_NOT_MATCH;
            return false;
        }

        $passport = new Passport();
        $result = $passport->setAccessToken(USER_TOKEN)->changePassword($oldPassword, $newPassword);
        if ($result) {
            return true;
        }

        $this->errorcode = $passport->getErrorCode();
        $this->message = $passport->getErrorMessage();
        return false;
    }


    /**
     * 修改资金密码
     * @param $oldFundsPassword
     * @param $newFundsPassword
     * @return bool
     */
    public function changeFundsPassword($oldFundsPassword, $newFundsPassword) {
        if(!USER_ID){
            $this->errorcode = EC_USER_NEED_TOKEN;
            return false;
        }

        $info = Loader::model('User')->where(['user_id' => USER_ID])->find();
        if (!$info) {
            $this->errorcode = EC_USER_INFO_NONE;
            return false;
        }

        $foundsSalt = $info['user_funds_salt'];
        if(encrypt_password($oldFundsPassword, $foundsSalt) != $info['user_funds_password']){
            $this->errorcode = EC_USER_OLD_FOUNS_PASSWORD_ERROR;
            return false;
        }

        $update = [
            'user_funds_password' => encrypt_password($newFundsPassword, $foundsSalt)
        ];

        return Loader::model('User')->where(['user_id' => USER_ID])->update($update);
    }
}