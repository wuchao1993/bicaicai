<?php
/**
 * 用户信息公共业务
 * @createTime 2017/5/10 14:17
 */

namespace app\common\logic;

use think\Loader;
use think\Model;
use think\Cache;
use think\Config;

class User extends Model {
    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 根据用户id获取用户主表信息
     * @param $uid 用户id
     * @param bool $isCache 是否走缓存
     * @param bool $extend 是否返回扩展信息
     * @return bool|mixed
     */
    public function getInfoByUid($uid, $isCache = false, $extend = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'user:user_info_'  . $uid;
        $data = [];
        if ($isCache) {
            $data = Cache::get($cacheKey);
        }

        if (!$data) {
            //获取用户主表数据
            $info = Loader::model('User')->where(['user_id' => $uid])->find();
            if (!$info) {
                return false;
            }
            $data['user_name']            = $info['user_name'];
            $data['nickname']             = $info['user_nickname'];
            $data['email']                = $info['user_email'];
            $data['mobile']               = $info['user_mobile'];
            $data['real_name']            = $info['user_realname'];
            $data['is_agent']             = $info['user_is_agent'];
            $data['remark']               = $info['user_remark'];
            $data['agent_status']         = $info['user_agent_check_status'];
            $data['ul_id']                = $info['ul_id'];
            $data['status']               = $info['user_status'];
            $data['last_login_time']      = $info['user_last_login_time'];
            $data['is_set_fund_password'] = empty($info['user_funds_password']) ? false : true;

            //非正式用户的用户名不返回前缀
            if (in_array(strtolower(substr($data['user_name'], 0, strpos($data['user_name'], '_'))), ['guest', 'special'])) {
                $data['user_name'] = substr($data['user_name'], strpos($data['user_name'], '_') + 1);
            }

            //是否绑定银行卡
            $bankInfo = Loader::model('UserBankRelation', 'logic')->getInfoByUid($uid, 'ub_status');
            if ($bankInfo && $bankInfo['ub_status'] == Config::get('status.user_bank_status')['enable']) {
                $data['is_bind_bank'] = true;
            } else {
                $data['is_bind_bank'] = false;
            }

            Cache::set($cacheKey, $data, Config::get('common.cache_time')['user_info']);
        }

        //获取未读消息数
        $data['message_not_read_num'] = Loader::model('common/Message', 'logic')->getNotReadNum();

        //获取用户扩展表数据, 不走缓存
        if($extend) {
            $info = $this->getExtendInfoByUid($uid);
            $info && $data = array_merge($data, $info);

            $notAllowWithdrawAmount = Loader::model('common/User', 'logic')->getNotAllowWithdrawAmount($uid);
            $data['allow_withdraw_amount'] = bcsub($data['account_balance'], $notAllowWithdrawAmount, 2);
            $data['allow_withdraw_amount'] < 0 && $data['allow_withdraw_amount'] = 0;
        }

        return $data;
    }

    /**
     * 根据用户名获取用户主表信息
     * @param $userName 用户名
     * @param bool $isCache 是否走缓存
     * @param bool $extend 是否返回扩展信息
     * @return bool
     */
    public function getInfoByUserName($userName, $isCache = false, $extend = false) {
        $cacheKey = Config::get('cache_option.prefix')['sports_common'] . 'user:user_info_'  . $userName;
        $data = $info = [];
        if ($isCache) {
            $data = Cache::get($cacheKey);
        }

        if (!$data) {
            //获取用户主表数据
            $info = Loader::model('User')->where(['user_name' => $userName])->find();
            if (!$info) {
                return false;
            }
            $data['user_id']              = $info['user_id'];
            $data['user_name']            = $info['user_name'];
            $data['nickname']             = $info['user_nickname'];
            $data['email']                = $info['user_email'];
            $data['mobile']               = $info['user_mobile'];
            $data['real_name']            = $info['user_realname'];
            $data['is_agent']             = $info['user_is_agent'];
            $data['remark']               = $info['user_remark'];
            $data['agent_status']         = $info['user_agent_check_status'];
            $data['ul_id']                = $info['ul_id'];
            $data['status']               = $info['user_status'];
            $data['last_login_time']      = $info['user_last_login_time'];
            $data['is_set_fund_password'] = empty($info['user_funds_password']) ? false : true;

            //是否绑定银行卡
            $userBankObj = new UserBankRelation();
            $bankInfo = $userBankObj->getInfoByUid($info['user_id'], 'ub_status');
            if ($bankInfo && $bankInfo['ub_status'] == Config::get('status.user_bank_status')['enable']) {
                $data['is_bind_bank'] = true;
            } else {
                $data['is_bind_bank'] = false;
            }

            Cache::set($cacheKey, $data, Config::get('common.cache_time')['user_info']);
        }

        //获取未读消息数
        $data['message_not_read_num'] = Loader::model('common/Message', 'logic')->getNotReadNum();

        //获取用户扩展表数据
        if ($extend) {
            $info = $this->getExtendInfoByUid($info['user_id']);
            $info && $data = array_merge($data, $info);
        }

        return $data;
    }

    /**
     * 根据用户id获取用户扩展表信息
     * @param $uid
     * @return bool
     */
    public function getExtendInfoByUid($uid) {
        $field = [
            'ue_account_balance AS account_balance',
        ];
        $info = Loader::model('UserExtend')->field($field)->where(['user_id' => $uid])->find();
        return $info ? $info->toArray() : false;
    }

    /**
     * 根据用户id获取层级返水信息
     * @param $uid
     * @return bool
     */
    public function getRebateByUid($uid) {
        //获取用户层级
        $info = Loader::model('User')->field('ul_id')->where(['user_id' => $uid])->find();
        if (!$info->ul_id) {
            return false;
        }

        //获取层级对应的反水
        $info = Loader::model('UserLevel')->field('ul_rebate_percentage')->where(['ul_id' => $info->ul_id])->find();
        if (!$info->ul_rebate_percentage) {
            return false;
        }

        $rebate = json_decode($info->ul_rebate_percentage, true);
        return $rebate;
    }

    /**
     * 扣除用户余额
     * @param $uid 用户id
     * @param $amount 金额
     * @return bool
     */
    public function balanceDeduct($uid, $amount) {
        $update['ue_account_balance'] = [
            'exp',
            'ue_account_balance-' . $amount
        ];
        $ret = Loader::model('UserExtend')->where('user_id', $uid)->update($update);
        return $ret === false ? false : true;
    }

    /**
     * 增加用户余额
     * @param $uid 用户id
     * @param $amount 金额
     * @return bool
     */
    public function balanceAdd($uid, $amount) {
        $update['ue_account_balance'] = [
            'exp',
            'ue_account_balance+' . $amount
        ];
        $ret = Loader::model('UserExtend')->where('user_id', $uid)->update($update);
        return $ret === false ? false : true;
    }

    /**
     * 获取用户不可提现金额
     * @param $uid
     * @param $relaxAmount
     * @return int
     */
    public function getNotAllowWithdrawAmount($uid, $relaxAmount = null) {
        $notAllowWithdrawAmount = 0;

        if ($relaxAmount === null) {
            $info = Loader::model('User')->field('ul_id')->where(['user_id' => $uid])->find();
            $payConfig = Loader::model('PayConfig', 'logic')->getInfoByUserLevelId($info->ul_id);
            if ($payConfig['pc_relax_amount']) {
                $relaxAmount = $payConfig['pc_relax_amount'];
            } else {
                $relaxAmount = 0;
            }
        }

        //计算不可提现的充值记录
        $where = [
            'user_id'                 => $uid,
            'urr_status'              => Config::get('status.recharge_status')['success'],
            'urr_is_withdraw'         => Config::get('status.recharge_record_withdraw_status')['no'],
            'urr_required_bet_amount' => ['exp', '<(urr_traffic_amount-' . intval($relaxAmount) . ')']
        ];
        $rechargeRecords = Loader::model('UserRechargeRecord')
            ->field('urr_amount,urr_recharge_discount')
            ->where($where)
            ->select();
        if ($rechargeRecords) {
            foreach ($rechargeRecords as $record) {
                $notAllowWithdrawAmount += $record['urr_amount'] + $record['urr_recharge_discount'];
            }
        }

        //计算不可提现的低赔率奖金
        $where = [
            'slobr_user_id'            => $uid,
            'slobr_is_withdraw'        => Config::get('status.bonus_record_withdraw_status')['no'],
            'slobr_require_bet_amount' => ['exp', '<slobr_traffic_amount']
        ];
        $totalBonus = Loader::model('SportsLowOddsBonusRecord')->where($where)->sum('slobr_amount');
        if ($totalBonus) {
            $notAllowWithdrawAmount += $totalBonus;
        }

        return $notAllowWithdrawAmount;
    }
}