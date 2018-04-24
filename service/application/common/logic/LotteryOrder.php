<?php
/**
 * 数字彩订单业务逻辑
 * Date: 2017/9/26
 */
namespace app\common\logic;

use think\Cache;
use think\Db;
use think\Loader;
use think\Model;
use think\Config;

class LotteryOrder extends Model {

    public $errorcode = EC_SUCCESS;
    private $redis = '';
    private $orderListKey = '';


    private function redisInit()
    {
        if($this->redis == '')
            $this->redis = redis_init();
    }

    private function setOrderListKey($userId)
    {
        $this->orderListKey = format_redis_key(ORDER_LIST_REDIS_KEY . $userId);
    }

    private function setExpire()
    {
        $this->redis->expire($this->orderListKey, 86400);
    }

    public function getCacheList($offset, $limit)
    {
        $this->redisInit();
        $list = $this->redis->zRevRange($this->orderListKey, $offset, $offset+$limit-1);
        $formatList = array();
        if(!empty($list))
        {
            foreach ($list as $info)
            {
                $formatList[] = json_decode($info, true);
            }
            $this->setExpire();
        }
        return $formatList;
    }

    public function getCacheInfo($orderId)
    {
        $this->redisInit();
        $list = $this->redis->zRangeByScore($this->orderListKey, $orderId, $orderId);
        if(!empty($list))
        {
            return json_decode($list[0], true);
        }
        return array();
    }


    public function setOrderToListCache($order)
    {
        if(empty($order['order_id']))
            return false;

        $this->redisInit();
        $this->setOrderListKey($order['user_id']);

        //存在score才更新
        if($this->redis->zCount($this->orderListKey, $order['order_id'], $order['order_id']) >= 1)
        {
            for($i = 0; $i < 10; $i++)
            {
                $this->redis->zRemRangeByScore($this->orderListKey, $order['order_id'], $order['order_id']);
                $result = $this->redis->zAdd($this->orderListKey, $order['order_id'], json_encode($order));

                if($result){
                    break;
                }else{
                    errorLog($result, '', 'redis_error');
                }
            }
        }

        $this->setExpire();
    }

    /**
     * 修改订单列表（自带UID登陆时才可以用）
     * @param unknown $orderList
     */
    public function batchSetOrderToListCache($orderList)
    {
        if(empty($orderList))
            return ;

        $this->redisInit();

        $p[] = $this->orderListKey;
        foreach ($orderList as $v){
            $p[] = $v['order_id'];
            $p[] = json_encode($v);
            if($this->redis->zCount($this->orderListKey, $v['order_id'], $v['order_id']) >= 1)
            {
                $this->redis->zRemRangeByScore($this->orderListKey, $v['order_id'], $v['order_id']);
            }
        }

        for($i = 0; $i < 5; $i++)
        {
            $result = call_user_func_array(array($this->redis, 'zAdd'), $p);
            if($result)
                break;
        }

        $this->setExpire();
    }

    /**
     * 用户二维数组批量修改订单（比如派奖）
     * @param unknown $orderList
     */
    public function batchSetOrderListCacheByUserArray($orderList)
    {
        if(empty($orderList))
            return;

        if($this->redis == '')
            $this->redis = redis_init();

        foreach($orderList as $userId => $list)
        {
            $this->setOrderListKey($userId);
            $p = array();
            $p[] = $this->orderListKey;
            foreach ($list as $v)
            {
                $p[] = $v['order_id'];
                $p[] = json_encode($v);
                if($this->redis->zCount($this->orderListKey, $v['order_id'], $v['order_id']) >= 1)
                {
                    $this->redis->zRemRangeByScore($this->orderListKey, $v['order_id'], $v['order_id']);
                }
            }
            for($i = 0; $i < 5; $i++)
            {
                $result = call_user_func_array(array($this->redis, 'zAdd'), $p);
                if($result)
                    break;
            }
            $this->setExpire();
        }
    }

}
