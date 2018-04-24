<?php
/**
 * 数字彩结果表
 * @author paulli
 */

namespace app\admin\model;

use think\Model;
use think\Config;
use lunar\Lunar;

class LotteryIssue extends Model {

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'lottery_issue_id';

    /**
     * 定义映射字段
     * @var array
     */
    public $_fields = [
        'lottery_issue_id'            => '主键',
        'lottery_id'                  => '彩票ID',
        'lottery_issue_no'            => '彩票期号',
        'lottery_issue_prize_num'     => '开奖号码',
        'lottery_issue_start_time'    => '开售时间',
        'lottery_issue_end_time'      => '停售时间',
        'lottery_issue_prize_time'    => '开奖时间',
        'lottery_issue_status'        => '彩期状态',
        'lottery_issue_prize_status'  => '开奖状态',
        'lottery_issue_bet_amount'    => '单期投注金额',
        'lottery_issue_bonus'         => '单期中奖金额',
        'lottery_issue_reprize_count' => '重新开奖次数',
        'lottery_createtime'          => '创建时间',
    ];

    public function updatePrizeNumber($lotteryId, $issue, $prizeNumber) {
        $condition = [
            'lottery_id'       => $lotteryId,
            'lottery_issue_no' => $issue,
        ];

        $info = $this->where($condition)->find();

        if(empty($info)) {
            return false;
        }else{
            $info = $info->toArray();
        }

        $info['lottery_issue_prize_num'] = $prizeNumber;
        $info['lottery_issue_prize_time'] = current_datetime();

        return $this->update($info);
    }

    public function updateIssuePrizeStatus($lotteryId, $issueNo, $status)
    {
        $condition = [];
        $condition['lottery_id']        = $lotteryId;
        $condition['lottery_issue_no']  = $issueNo;

        $info['lottery_issue_prize_status'] = $status;

        return $this->save($info,$condition);
    }


    /**
     * 六合彩中奖球颜色配置
     */
    public function sixColorConfig($num)
    {
        if(in_array($num, array('1', '2', '7', '8', '01', '02', '07', '08', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46')))
        {
            return '#F45959';//红
        }
        elseif(in_array($num, array('3', '4', '9', '03', '04', '09', '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48')))
        {
            return '#51ABF0';//蓝
        }
        elseif(in_array($num, array('5', '6', '05', '06', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49')))
        {
            return '#6ACC7B';//绿
        }
        else
            return false;
    }

    /**
     * PC蛋蛋类颜色配置
     */
    public function pc28ColorConfig($num)
    {
        if(in_array($num, array(0, 13, 14, 27)))
            return '#AAAAAA';
        else
        {
            switch ($num%3)
            {
                case 1:
                    return '#6ACC7B';//绿
                    break;
                case 2:
                    return '#51ABF0';//蓝
                    break;
                default:
                    return '#F45959';//红
                    break;
            }
        }
    }

    /**
     * 取生肖列表
     */
    public function getSxList($day=''){
        $today 	  = $day ? $day : date("Y-m-d");
        $lunar 	  = new Lunar();
        $year 	  = date("Y",$lunar->S2L($today));
        $arr 	  = array('猴','鸡','狗','猪','鼠','牛','虎','兔','龙','蛇','马','羊');
        if( preg_match("/^\d{4}$/",$year)){
            $m 	  = $year % 12;
            $x 	  = $arr[$m];
        }
        switch ($x)	{
            case '猪':
                $sx = array('猪','狗','鸡','猴','羊','马','蛇','龙','兔','虎','牛','鼠');
                break;
            case '狗':
                $sx = array('狗','鸡','猴','羊','马','蛇','龙','兔','虎','牛','鼠','猪');
                break;
            case '鸡':
                $sx = array('鸡','猴','羊','马','蛇','龙','兔','虎','牛','鼠','猪','狗');
                break;
            case '猴':
                $sx = array('猴','羊','马','蛇','龙','兔','虎','牛','鼠','猪','狗','鸡');
                break;
            case '羊':
                $sx = array('羊','马','蛇','龙','兔','虎','牛','鼠','猪','狗','鸡','猴');
                break;
            case '马':
                $sx = array('马','蛇','龙','兔','虎','牛','鼠','猪','狗','鸡','猴','羊');
                break;
            case '蛇':
                $sx = array('蛇','龙','兔','虎','牛','鼠','猪','狗','鸡','猴','羊','马');
                break;
            case '龙':
                $sx = array('龙','兔','虎','牛','鼠','猪','狗','鸡','猴','羊','马','蛇');
                break;
            case '兔':
                $sx = array('兔','虎','牛','鼠','猪','狗','鸡','猴','羊','马','蛇','龙');
                break;
            case '虎':
                $sx = array('虎','牛','鼠','猪','狗','鸡','猴','羊','马','蛇','龙','兔');
                break;
            case '牛':
                $sx = array('牛','鼠','猪','狗','鸡','猴','羊','马','蛇','龙','兔','虎');
                break;
            case '鼠':
                $sx = array('鼠','猪','狗','鸡','猴','羊','马','蛇','龙','兔','虎','牛');
                break;
            default:
                $sx = array('鼠','猪','狗','鸡','猴','羊','马','蛇','龙','兔','虎','牛');
        }
        return $sx;
    }

    /**
     * 取生肖对应号码 type=0    array('生肖'=>'生肖号码');
     * type=1  array($k=>array('sx'=>生肖,num=>'生肖号码'))
     * $k 对应着下注时的1-12
     */
    public function getSxNumList($type=0,$day=''){
        $sx 			= $this->getSxList($day);
        $all_sx_number 	= Config::get('six.LHC_SX_NUMBER');
        if(!$type){
            foreach ($sx as $k=>$v){
                $list[$v] 	= $all_sx_number[$k];
            }
        }
        if($type==1){
            $sx_name		= Config::get('six.LHC_SX_NAME');
            foreach ($sx_name as $k=>$v) {
                $i = array_search($v, $sx);
                $list[$k+1] = array('sx'=>$v,'num'=>$all_sx_number[$i]);
            }
        }
        return $list;
    }

    /**
     * 取生肖号码列表
     * @return   array    号码=>生肖
     * @author   <carton>
     */
    public function getNumSx($day=''){
        $list = $this->getSxNumList(0, $day);
        foreach ($list as $k=>$v) {
            $arr = explode(",",$v);
            foreach ($arr as $val) {
                $return[$val] = $k;
            }
        }
        return $return;
    }

    public function getSxByNum($num, $day=''){
        $day = date('Y-m-d', strtotime($day));

        $lunar 	  = new Lunar();
        $year 	  = date("Y",$lunar->S2L($day));
        static $config = array();
        $sx_config = $config[$year];
        if(empty($sx_config)){
            $config[$year] = $this->getNumSx($day);
        }
        return $config[$year][$num];
    }

    /**
     * 格式化开奖号码
     *
     */
    public function formatLotteryIssuePrizeNum($info, $lottery_issue_prize_num)
    {
        if(is_array($lottery_issue_prize_num))
        {
            if(in_array($info['lottery_id'], Config::get('six.LHC_LOTTERY_ID_ALL') ) )
            {
                foreach ($lottery_issue_prize_num as $k => $v)
                {
                    $time = empty($info['order_calculate_time']) ? $info['order_createtime'] : $info['order_calculate_time'];
                    $lottery_issue_prize_num[$k] = array('number' => $v, 'style' => $this->sixColorConfig($v),
                        'sx'=>$this->getSxByNum($v, date('Y-m-d', strtotime($time))));
                }
            }
            elseif($info['lottery_category_id'] == Config::get('lottery.PC28_CATEGROY_ID'))
            {
                foreach ($lottery_issue_prize_num as $k => $v)
                {
                    if($k == 3)
                        $lottery_issue_prize_num[$k] = array('number' => $v, 'style' => $this->pc28ColorConfig($v));
                    else
                        $lottery_issue_prize_num[$k] = array('number' => $v, 'style' => '#E6A837');
                }
            }
            else
            {
                foreach($lottery_issue_prize_num as $k => $v)
                {
                    $lottery_issue_prize_num[$k] = array('number' => $v, 'style' => '#FF7F00');
                }
            }
        }
        return $lottery_issue_prize_num;
    }

}