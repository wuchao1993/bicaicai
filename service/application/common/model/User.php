<?php
/**
 * 用户表
 * @createTime 2017/3/31 17:08
 */

namespace app\common\model;

use think\Db;
use think\Loader;
use think\Log;
use think\Model;
use think\Exception;

class User extends Model
{

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'user_id';

    public function getInfoByUserName($userName)
    {
        return $this->where(['user_name' => $userName])->find();
    }

    public function getUserCountByIp($ip){
        $condition = [
            'user_reg_ip' => $ip,
            'user_createtime' => ['between', [date('Y-m-d'), current_datetime()]]
        ];

        return $this->where($condition)->count();
    }

    public function getInfo($userId){
        $info = $this->where(['user_id' => $userId])->find();
        return $info;
    }


    public function addLowerCount($userId){
        $condition = [
            'user_id' => $userId
        ];

        return $this->where($condition)->setInc('user_lower_count');
    }


    public function getUsers($condition = [], $limit = 10, $order = ''){
        $fields = "user_id,user_name,user_pid,user_grade,ul_id,bank_id,user_lower_count,user_realname,user_is_agent,user_status,user_all_pid";
        if(empty($order)){
            $order = "user_createtime desc";
        }

        return $this->where($condition)->order($order)->limit($limit)->column($fields);
    }


    public function getSubordinateInfo($userPid){
        try{
            $condition = [
                'user_pid' => $userPid,
            ];
            $fields = [
                'count(user.user_id)' => 'count',
                'sum(ue_account_balance)' => 'balance'
            ];
            $userSql = $this->where($condition)->field('user_id')->buildSql();
            $result = Db::table('__USER_EXTEND__')->alias('ue')->join([$userSql => 'user'], "ue.user_id = user.user_id")->field($fields)->find();
            return $result ? collection($result)->toArray() : false;
        }catch (Exception $exception){
            Log::write("error: ". $exception->getMessage());
        }
    }


    public function getRegisterUserCountBetweenDateFromAgentInvite($userPid, $startDate, $endDate){
        $startDate = date('Y-m-d 00:00:00', strtotime($startDate));
        $endDate = date('Y-m-d 23:59:59', strtotime($endDate));
        $condition = [
            'user_pid' => $userPid,
            'user_createtime' => ['between', $startDate, $endDate]
        ];
        return $this->where($condition)->count();
    }
}