<?php
namespace app\admin\logic;
use think\Exception;
use think\Log;
use think\Loader;
use think\Config;

class SportsAgentRebate
{
    public $errorcode = EC_SUCCESS;

    /***
     * @desc 添加体彩代理返水配置信息
     * @param $rebateParam
     */
    public function addSportsAgentRebate($rebateParam){
        try{
            $rebateData['sar_profit_min'] = $rebateParam['profitMin'];
            $rebateData['sar_profit_max'] = $rebateParam['profitMax'];
            $rebateData['sar_valid_user'] = $rebateParam['validUser'];
            $rebateData['sar_rebate']     = $rebateParam['rebate'];
            $rebateData['sar_createtime'] = current_datetime ();
            $rebateData['sar_modifytime'] = current_datetime ();
            $flag = Loader::model ("SportsAgentRebate")->save($rebateData);
            if($flag === false){
                $this->errorcode = EC_INSERT_SPORTS_AGENT_REBATE_ERROR;
            }
        }catch(Exception $e){
            Log::write("添加体彩返水配置信息报错：".$e->getMessage());
            $this->errorcode = EC_INSERT_SPORTS_AGENT_REBATE_ERROR;
        }

    }

    /***
     * @desc 编辑体彩代理返水配置信息
     * @param $rebateParam
     */
    public function editSportsAgentRebate($rebateParam)
    {
        try {
            $sportsAgentRebateModel = Loader::model("SportsAgentRebate");
            $rebateData = $this->_buildEditCondition($rebateParam);
            $flag = $sportsAgentRebateModel->save($rebateData, ["sar_id" => $rebateParam['id']]);
            if ($flag === false) {
                $this->errorcode = EC_EDIT_SPORTS_AGENT_REBATE_ERROR;
            }
        } catch (Exception $e) {
            Log::write("编辑体彩返水配置信息报错：" . $e->getMessage());

            $rebateData['sar_id'] = $rebateParam['id'];
            $rebateData['sar_status'] = $rebateParam['status'];
            $rebateData['sar_modifytime'] = current_datetime();

            $sportsAgentRebateModel = Loader::model("SportsAgentRebate");

            if ($rebateParam['status'] == Config::get("qrcode.sports_agent_rebate_status")['enable']) {

                if (!empty($rebateParam['profitMin'])) {
                    $rebateData['sar_profit_min'] = $rebateParam['profitMin'];
                }
                if (!empty($rebateParam['profitMax'])) {
                    $rebateData['sar_profit_max'] = $rebateParam['profitMax'];
                }
                if (!empty($rebateParam['rebate'])) {
                    $rebateData['sar_rebate'] = $rebateParam['rebate'];
                }
                if (!empty($rebateParam['validUser'])) {
                    $rebateData['sar_valid_user'] = $rebateParam['validUser'];
                }
            }

            $rebateFlag = $sportsAgentRebateModel->update($rebateData);
            if ($rebateFlag) {
                $this->errorcode = EC_SUCCESS;
            } else {
                $this->errorcode = EC_EDIT_SPORTS_AGENT_REBATE_ERROR;
            }
        }
    }

    /***
     * @desc 状态为1，转换条件
     * @param $rebateParam
     * @return mixed
     */
    private function _buildEditCondition($rebateParam){
        $rebateData['sar_status']     = $rebateParam['status'];
        $rebateData['sar_modifytime'] = current_datetime ();
        $rebateData['sar_profit_max'] = $rebateParam['profitMax'];
        $rebateData['sar_profit_min'] = $rebateParam['profitMin'];
        $rebateData['sar_rebate']     = $rebateParam['rebate'];
        $rebateData['sar_valid_user'] = $rebateParam['validUser'];
        return $rebateData;
    }

    /***
     * @desc 删除体彩代理返水配置信息
     * @param $rebateParam
     */
    public function deleteSportsAgentRebate($rebateParam){
        try{
            $rebateData['sar_id']     = $rebateParam['id'];
            $rebateData['sar_status'] = Config::get("qrcode.sports_agent_rebate_status")['delete'];
            $flag =  Loader::model ("SportsAgentRebate")->update($rebateData);
            if($flag === false){
                $this->errorcode = EC_DELETE_SPORTS_AGENT_REBATE_ERROR;
            }
        }catch(Exception $e){
            Log::write("删除体彩返水配置信息报错：".$e->getMessage());
            $this->errorcode = EC_DELETE_SPORTS_AGENT_REBATE_ERROR;
        }
    }

    /***
     * $desc 获取体彩代理返水配置信息
     * @param $rebateParam
     * @return $array
     */
    public function getSportsAgentRebateList($rebateParam){
        try{
            $where['sar_status'] = ["NEQ",Config::get("qrcode.sports_agent_rebate_status")['delete']];
            $field = ['sar_id' => 'id',
                      'sar_profit_min' => 'profitMin',
                      'sar_profit_max' => 'profitMax',
                      'sar_valid_user' => 'validUser',
                      'sar_rebate'     => 'rebate',
                      'sar_status'     => 'status',
                      'sar_createtime' => 'createtime',
                      'sar_modifytime' => 'modifytime'];
            $order = "sar_modifytime desc";
            $sportAgentRebateModel =  Loader::model ("SportsAgentRebate");

            $rebateData["list"] = $sportAgentRebateModel
                ->field($field)
                ->where($where)
                ->limit($rebateParam['num'])
                ->page($rebateParam['limitStartNumber'])
                ->order($order)
                ->select();
            $rebateData["totalCount"] = $sportAgentRebateModel->where($where)->count();
            return $rebateData;
        }catch(Exception $e){
            Log::write("获取体彩返水配置信息报错：".$e->getMessage());
            $this->errorcode = EC_LIST_SPORTS_AGENT_REBATE_ERROR;
        }
    }

}