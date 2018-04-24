<?php
namespace Home\Pay;

use Home\Pay\Pay;

//
class MingYun extends Pay
{
    /* new
     *二维码
     *wap
     * */
    public static $PAY_MERCHANT_ID = 'parter';
    public static $PAY_SIGN = 'sign';
    public static $PAY_TRANS_AMT = 'value';
    public static $PAY_MY_ORDER_ID = 'orderid';

    public static $PAY_TERMINAL_ID = 'agent';  //代理 ID
    public static $PAY_API_VERSION = 'version';
    public static $PAY_NAME = 'method';
    public static $PAY_TYPE = 'type';
    public static $PAY_NOTIFY_URL = 'callbackurl';
    public static $PAY_RETURN_URL = "hrefbackurl";
    public static $PAY_GOODS_NAME = 'goodsName';
    public static $PAY_SHOW_IMAGE = 'isshow';
    public static $PAY_TIME = '';
    public static $PAY_IP = 'payerIp';
    public static $PAY_Bank = '';
    public static $PAY_PARAM = 'attach';


    //返回
    public static $BACK_MESSAGE = 'message';
    public static $BACK_MERCHANT_ID = "";
    public static $BACK_SIGN = '';
    public static $BACK_MY_ORDER_ID = "";


    public static $BACK_STATE = '';               //支付成功
    public static $BACK_SUCCESS = 'respCode';     //响应成功

    public static $BACK_FOUR_ORDER_ID = "refno";
    public static $BACK_IMAGE = "barCode";        //二维码地址
    public static $BACK_REMARK = "remark";         //备注
    public static $BACK_TRANS_TIME = 'order_time';     //订单时间
    public static $BACK_CUSTOMER_IP = 'client_ip';      //客户端IP
    public static $BACK_SIGN_TYPE = 'sign_type';      //签名方式
    //异步
    public static $NOTICE_MERCHANT_ID = "";
    public static $NOTICE_SIGN = '';
    public static $NOTICE_TRANS_AMT = 'ovalue';
    public static $NOTICE_MY_ORDER_ID = 'orderid';
    public static $NOTICE_PAY_TYPE = '';
    public static $NOTICE_MESSAGE = "msg";

    public static $NOTICE_FOUR_ORDER_ID = 'sysorderid';        //第四方单号
    public static $NOTICE_PAY_STATE = 'opstate';
    public static $NOTICE_SUCCESS = '';

    public static $NOTICE_TYPE = "";
    public static $NOTICE_NOTE_ID = "";
    public static $NOTICE_TRADE_TIME = 'transTime';
    public static $NOTICE_NOTE_TIME = 'completiontime';               //通知时间

//查询
    //查询网关
    public static $QUERY_MERCHANT_ID = "";
    public static $QUERY_SIGN = '';
    public static $QUERY_TRANS_AMT = '';
    public static $QUERY_MY_ORDER_ID = '';
    public static $QUERY_PAY_TYPE = '';
    public static $QUERY_MESSAGE = '';               //查询返回信息

    public static $QUERY_STATE = 'respCode';     //要查询的状态 如 要查成功 或者 失败
    public static $QUERY_FOUR_ORDER_ID = '';               //第四方单号
    public static $QUERY_SUCCESS = '';         //成功查询
    public static $QUERY_API_VERSION = '';
    public static $QUERY_SERVICE_TYPE = '';
    public static $QUERY_TIME = '';               //查询的时间- 什么时间查询的
    public static $QUERY_PAY_TIME = '';               //支付时间
    public static $QUERY_TYPE = '';               //查询类型
    public static $QUERY_ERROR_CODE = '';               //查询错误信息
    const QUERY_URL = 'http://api.bsjpay.com:8089/payapi/qrcodeQuery';

    function __construct($id)
    {
        parent::__construct($id);
        //商户号
        empty(self::$BACK_MERCHANT_ID) && self::$BACK_MERCHANT_ID = self::$PAY_MERCHANT_ID;
        empty(self::$NOTICE_MERCHANT_ID) && self::$NOTICE_MERCHANT_ID = self::$PAY_MERCHANT_ID;
        empty(self::$QUERY_MERCHANT_ID) && self::$QUERY_MERCHANT_ID = self::$PAY_MERCHANT_ID;
        //签名
        empty(self::$BACK_SIGN) && self::$BACK_SIGN = self::$PAY_SIGN;
        empty(self::$NOTICE_SIGN) && self::$NOTICE_SIGN = self::$PAY_SIGN;
        empty(self::$QUERY_SIGN) && self::$QUERY_SIGN = self::$PAY_SIGN;
        //订单号
        empty(self::$BACK_MY_ORDER_ID) && self::$BACK_MY_ORDER_ID = self::$PAY_MY_ORDER_ID;
        empty(self::$NOTICE_MY_ORDER_ID) && self::$NOTICE_MY_ORDER_ID = self::$PAY_MY_ORDER_ID;
        empty(self::$QUERY_MY_ORDER_ID) && self::$QUERY_MY_ORDER_ID = self::$PAY_MY_ORDER_ID;
        //支付类型
        empty(self::$NOTICE_PAY_TYPE) && self::$NOTICE_PAY_TYPE = self::$PAY_TYPE;
        empty(self::$QUERY_PAY_TYPE) && self::$QUERY_PAY_TYPE = self::$PAY_TYPE;
        //返回消息
        empty(self::$NOTICE_MESSAGE) && self::$NOTICE_MESSAGE = self::$BACK_MESSAGE;
        empty(self::$QUERY_MESSAGE) && self::$QUERY_MESSAGE = self::$BACK_MESSAGE;

    }

    public function pay($money, $order_no, $pp_category_id)
    {

        header("Content-type: text/html; charset=utf-8");
        $keyArr = array();
        switch ($pp_category_id) {
            case PAY_PLATFORM_CATEGORY_WEIXINSCAN :
                $pay_method = '1004';
                break;
            case PAY_PLATFORM_CATEGORY_ALIPAYSCAN :
                $pay_method = '992';
                break;
            case PAY_PLATFORM_CATEGORY_ONLINEBANK :
                $pay_method = $this->getBankCode();    //不支持
                break;
            default:
                $pay_method = '';
                return false;
        }
        $this->add(self::$PAY_MERCHANT_ID, $this->getMemberNo());
        $this->add(self::$PAY_MY_ORDER_ID, $order_no);
        $this->add(self::$PAY_TRANS_AMT, $money);
        $this->add(self::$PAY_TYPE, $pay_method);

        $this->add(self::$PAY_NOTIFY_URL, $this->getNotifyUrl());
        $this->add(self::$PAY_RETURN_URL, $this->getReturnUrl());

        $this->add(self::$PAY_SHOW_IMAGE, "0");
        $this->add(self::$PAY_PARAM, "0");
        $this->add(self::$PAY_TERMINAL_ID, $this->getTerId());
        $this->add(self::$PAY_IP, "127.0.0.1");


        $keyArr[] = self::$PAY_MERCHANT_ID;
        $keyArr[] = self::$PAY_TYPE;
        $keyArr[] = self::$PAY_TRANS_AMT;
        $keyArr[] = self::$PAY_MY_ORDER_ID;
        $keyArr[] = self::$PAY_NOTIFY_URL;
        $this->add(self::$PAY_SIGN, $this->sign($this->kvs, $keyArr));
        $this->buildRequestForm("get");
    }


    public function buildRequestForm($method = 'post')
    {
        $data = $this->kvs;
        $url = $this->getGateWayUrl() . "?" . urldecode(http_build_query($data));
        header("location:" . $url);
    }

    public function returnPay($money, $order_no, $pp_category_id)
    {
        return false;

    }


    //异步通知
    public function notifyUrl()
    {
        $arr = array();
        $arr[self::$NOTICE_MERCHANT_ID] = I(self::$NOTICE_MERCHANT_ID);
        $arr[self::$NOTICE_MY_ORDER_ID] = I(self::$NOTICE_MY_ORDER_ID);
        $arr[self::$NOTICE_PAY_STATE] = I(self::$NOTICE_PAY_STATE);
        $arr[self::$NOTICE_TRANS_AMT] = I(self::$NOTICE_TRANS_AMT);
        $arr[self::$NOTICE_FOUR_ORDER_ID] = I(self::$NOTICE_FOUR_ORDER_ID);
        $arr[self::$NOTICE_NOTE_TIME] = I(self::$NOTICE_NOTE_TIME, date("Y-m-d H:i;s"));
        $rSign = I(self::$NOTICE_SIGN);
        $local_order_id = I(self::$NOTICE_FOUR_ORDER_ID);;

        $keyArr = array();

        $keyArr[] = self::$NOTICE_MY_ORDER_ID;
        $keyArr[] = self::$NOTICE_PAY_STATE;
        $keyArr[] = self::$NOTICE_TRANS_AMT;

        $sign = $this->sign($_REQUEST, $keyArr);
        if ($sign == $rSign) {
            if ($arr[self::$NOTICE_PAY_STATE] === "0") {
                $recharge_status = RECHARGE_STATUS_SUCCES;
            } else {
                $recharge_status = RECHARGE_STATUS_FAIL;
            }
            if (isset($recharge_status)) {
                $result = $this->handleOrder($arr[self::$NOTICE_MY_ORDER_ID], $arr[self::$NOTICE_TRANS_AMT], $local_order_id, $arr[self::$NOTICE_NOTE_TIME], $recharge_status);
                if ($result) {
                    exit('opstate=0');
                } else {
                    exit('opstate=-1');
                }
            }
        } else {
            errorLog($_REQUEST, '签名错误', __CLASS__ . '_response_error');
            exit('opstate=-1');
        }
    }

    //同步返回
    public function returnUrl()
    {

    }

    public function sign($data, $signKey = '')
    {
        $sign_data = array();
        if (!empty($signKey)) {
            foreach ($signKey as $v) {
                $sign_data[$v] = $data[$v];
            }
        } else {
            $sign_data = $data;

        }
        $sb_list = array();
        foreach ($sign_data as $key => $value) {
            if ($key == self::$PAY_SIGN || $key == self::$NOTICE_SIGN || $value == "") {
                continue;
            }
            if (is_array($value) && sizeof($value) == 0) {
                continue;
            }
            $sb_list[] = $key . '=' . $value;
        }
        $key = implode('&', $sb_list) . $this->getMemberKey();
        return md5($key);
    }


    public static function undoWord($result, $k = '')
    {
        if ($k == "json") {
            return json_decode($result, true);
        } elseif ($k == "xml") {
            $obj = simplexml_load_string($result);
            return objectToArray($obj);
        } else {
            return $result;
        }
    }
}