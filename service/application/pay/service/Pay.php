<?php
namespace app\pay\service;


class Pay
{
    protected $kvs = array();
    private $signKey = '';
    private $gateWayUrl = '';

    private $memberNo;
    private $terId;
    private $memberKey;
    private $isReturn = false;
    private $redirectDomain;
    private $bankCode;
    private $rsaPubKey;
    private $rsaPriKey;
    private $notifyUrl;
    private $returnUrl;

    function __construct($id)
    {
        parent::__construct();
        $config = $this->getPayConfig($id);
        $redirect_domain = D('Home/PayPlatform')->getRedirectDomain($id, $config['pay_type_id']);
        $this->setRedirectDomain($redirect_domain);
        $this->setMemberNo($config['pp_account_no']);
        $this->setMemberKey(think_decrypt($config['pp_account_key']));
        $this->setTerId($config['pp_terminal_id']);
        $this->setGateWayUrl($config['gateway_url']);
        $this->setNotifyUrl(U($this->formatUrl('notifyUrl'), array('id' => $id), true, true));
        $this->setReturnUrl(U($this->formatUrl('returnUrl'), array('id' => $id), true, true));
        $this->setRsaPriKey($config['pp_rsa_pri_key']);
        $this->setRsaPubKey($config['pp_rsa_pub_key']);
    }

    //如果回调地址需要用商城域名需要把地址调用此函数修改路径
    public function formatUrl($url)
    {
        $domain = $this->getRedirectDomain();
        if ($domain) {
            return "/$url@{$domain}";
        } else {
            return "/Home/Pay/" . $url;
        }
    }

    public function handleOrder($order_no, $order_amount, $trade_no, $trade_time, $status, $operator_id = 0)
    {

        $order_info = D('Home/UserRechargeRecord')->getInfoByNo($order_no);

        if ($order_info) {
            $pay_status = $order_info['urr_status'];
            $amount = $order_info['urr_amount'];
            $user_id = $order_info['user_id'];
            $order_id = $order_info['urr_id'];
//             $total_amount       = $order_info['urr_total_amount'];
            $recharge_discount = $order_info['urr_recharge_discount'];

            if (bccomp($amount, $order_amount, 3) !== 0) {
                return false;
            }
            //已经处理过的订单
            if ($pay_status == RECHARGE_STATUS_SUCCES || $pay_status == RECHARGE_STATUS_FAIL) {
                return true;
            }

            if ($pay_status == RECHARGE_STATUS_NO_PAY) {
                $save_data = array();
                $save_data['urr_trade_no'] = $trade_no;
                $save_data['urr_trade_time'] = $trade_time ? $trade_time : current_datetime();
                $save_data['urr_status'] = $status;
                $save_data['urr_operator_id'] = $operator_id;
                $save_data['urr_confirm_time'] = current_datetime();
                M()->startTrans();
                $condition = array();
                $condition['urr_id'] = $order_id;
                $condition['urr_status'] = RECHARGE_STATUS_NO_PAY;
                $recharge_result = D('Home/UserRechargeRecord')->modify($condition, $save_data);
                if (empty($recharge_result)) {
                    M()->rollback();
                    return false;
                }

                //如果支付失败结束返回。支付成功继续
                if ($status == RECHARGE_STATUS_SUCCES && $recharge_result) {
                    $user_before_balance = D('Home/UserExtend')->getBalance($user_id);
                    $extend_result = D('Admin/UserExtend')->addRechargeAmount($order_info['user_id'], $amount, $recharge_discount);
                    if (empty($extend_result)) {
                        M()->rollback();
                        return false;
                    }
                    $user_after_balance = bcadd($user_before_balance, $amount, 3);

                    $user_account_record = array();
                    $user_account_record['user_id'] = $user_id;
                    $user_account_record['uar_source_id'] = $order_id;
                    $user_account_record['uar_source_type'] = SOURCE_TYPE_RECHARGE;
                    $user_account_record['uar_transaction_type'] = ACCOUNT_TRANSACTION_TYPE_RECHARGE;
                    $user_account_record['uar_action_type'] = ACCOUNT_TRANSFER_IN;
                    $user_account_record['uar_amount'] = $amount;
                    $user_account_record['uar_before_balance'] = $user_before_balance;
                    $user_account_record['uar_after_balance'] = $user_after_balance;

                    $user_account_record['uar_remark'] = '在线充值';
                    $account_record_result = M('UserAccountRecord')->add($user_account_record);

                    if (bccomp($recharge_discount, 0, 3) > 0) {
                        $discount_account_record = array();
                        $discount_account_record['user_id'] = $user_id;
                        $discount_account_record['uar_source_id'] = $order_id;
                        $discount_account_record['uar_source_type'] = SOURCE_TYPE_RECHARGE;
                        $discount_account_record['uar_transaction_type'] = ACCOUNT_TRANSACTION_TYPE_DISCOUNT;
                        $discount_account_record['uar_action_type'] = ACCOUNT_TRANSFER_IN;
                        $discount_account_record['uar_amount'] = $recharge_discount;
                        $discount_account_record['uar_before_balance'] = $user_after_balance;
                        $discount_account_record['uar_after_balance'] = bcadd($user_after_balance, $recharge_discount, 3);

                        $discount_account_record['uar_remark'] = '在线充值优惠';
                        M('UserAccountRecord')->add($discount_account_record);
                    }


                    if (empty($account_record_result)) {
                        M()->rollback();
                        return false;
                    }
                }
                D('Admin/PayPlatform')->addStatistics($order_info['urr_recharge_account_id'], $amount);
                if (M()->getDbError()) {
                    M()->rollback();
                    return false;
                } else {
                    M()->commit();
                    return true;
                }

            }
        }
    }

    function buildUrl()
    {
        $parameters = array();
        foreach ($this->kvs as $key => $val) {
            $parameters[] = $key . '=' . $val;
        }
        $parameters_str = implode('&', $parameters);
        return $this->getGateWayUrl() . '?' . $parameters_str;
    }

    function buildRequestForm($method = 'post')
    {
        if ($this->isReturn) {
            return $this->buildUrl();
        } else {
            $sHtml = '<form action="' . $this->getGateWayUrl() . '" method="' . $method . '">';
            foreach ($this->kvs as $key => $val) {
                $sHtml .= '<input type="hidden" name="' . $key . '" value="' . $val . '"/>';
            }
            $sHtml = $sHtml . '<form/>';
            $sHtml = $sHtml . "<script>document.forms[0].submit();</script>";
            echo $sHtml;
        }
    }


    static function appendParam(& $sb, $name, $val, $and = true, $charset = null)
    {
        if ($and) {
            $sb .= "&";
        } else {
            $sb .= "?";
        }
        $sb .= $name;
        $sb .= "=";
        if (is_null($val)) {
            $val = "";
        }

        if (is_null($charset)) {
            $sb .= $val;
        } else {
            $sb .= urlencode($val);
        }
    }

    function add($k, $v)
    {
        if (!is_null($v))
            $this->kvs[$k] = $v;
    }

    function sign($sign_data = '')
    {
        $strb = "";
        $sign_data = $sign_data ? $sign_data : $this->kvs;
        ksort($sign_data);
        $sb_list = array();
        foreach ($sign_data as $key => $value) {
            if ($key == "sign" || $key == "signType" || $value == "") {
                continue;
            }
            if (is_array($value) && sizeof($value) == 0) {
                continue;
            }
            $sb_list[] = $key . '=' . $value;
        }
        $sb_list[] = $this->getSignKey() . '=' . $this->getMemberKey();
        return md5(implode('&', $sb_list));
    }

    function link()
    {
        $strb = "";
        ksort($this->kvs);
        foreach ($this->kvs as $key => $val) {
            if ($key == 'sign' || $val == "") {
                continue;
            }
            self::appendParam($strb, $key, $val);
        }
        self::appendParam($strb, $this->getSignKey(), $this->getMemberKey());
        $strb = substr($strb, 1, strlen($strb) - 1);
        return $strb;
    }

    public function getPayConfig($id)
    {
        $config = M('PayPlatform')->where(array('pp_id' => $id))->find();
        if ($config) {
            $gateway_url = D('Admin/PayTypeConfig')->getUrl($config['pay_type_id'], $config['pp_category_id']);
            $mer_no = $config['pp_account_no'];
            if (empty($gateway_url) || empty($mer_no)) {
                $this->error('缺少支付参数！');
            }
            $config['gateway_url'] = $gateway_url;
            return $config;
        } else {
            $this->error('缺少支付参数！');
        }
    }

    public function deXml($xml)
    {
        $obj = simplexml_load_string($xml);
        return objectToArray($obj);
    }

    public static function curl($url, $data = array(), $headr = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:14.0) Gecko/20100101 Firefox/14.0.2');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_HEADER, $headr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function getSignKey()
    {
        return $this->signKey;
    }

    public function getGateWayUrl()
    {
        return $this->gateWayUrl;
    }

    public function setSignKey($signKey)
    {
        $this->signKey = $signKey;
    }

    public function setGateWayUrl($gateWayUrl)
    {
        $this->gateWayUrl = $gateWayUrl;
    }

    public function getMemberNo()
    {
        return $this->memberNo;
    }

    public function getMemberKey()
    {
        return $this->memberKey;
    }

    public function setMemberNo($memberNo)
    {
        $this->memberNo = $memberNo;
    }

    public function setMemberKey($memberKey)
    {
        $this->memberKey = $memberKey;
    }

    public function getIsReturn()
    {
        return $this->isReturn;
    }

    public function setIsReturn($isReturn)
    {
        $this->isReturn = $isReturn;
    }

    public function getTerId()
    {
        return $this->terId;
    }

    public function setTerId($terId)
    {
        $this->terId = $terId;
    }

    public function getRedirectDomain()
    {
        return $this->redirectDomain;
    }

    public function setRedirectDomain($redirectDomain)
    {
        $this->redirectDomain = $redirectDomain;
    }

    public function getBankCode()
    {
        return $this->bankCode;
    }

    public function setBankCode($bankCode)
    {
        $this->bankCode = $bankCode;
    }

    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
    }


    public function getRsaPubKey()
    {
        return $this->rsaPubKey;
    }

    public function getRsaPriKey()
    {
        return $this->rsaPriKey;
    }

    public function setRsaPubKey($rsaPubKey)
    {
        $this->rsaPubKey = $rsaPubKey;
    }

    public function setRsaPriKey($rsaPriKey)
    {
        $this->rsaPriKey = $rsaPriKey;
    }

}