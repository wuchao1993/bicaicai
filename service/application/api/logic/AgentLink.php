<?php

namespace app\api\logic;

use think\Config;
use think\Db;
use think\Exception;
use think\Loader;
use think\Log;
use think\Env;
use qrcode\QrcodeUpload;
use alioss\OssQrcode;


class AgentLink
{

    /**
     * 定义主键
     * @var string
     */
    protected $pk = 'agl_id';
    public $errorcode = EC_SUCCESS;

    /***
     * @desc 生成二维码图片
     * @param $data
     * @param $param
     * @param $userId
     * @return bool|string
     */
    public function generateCode($alParam)
    {
        try {
            $agentLinkModel = Loader::model("AgentLink");
            //获取生成邀请码的参数
            $insertData = $this->_buildAgentLinkCondition($alParam);

            $condition['agl_status'] = Config::get("qrcode.agl_status")['enable'];
            $condition['user_id'] = USER_ID;
            $count = $agentLinkModel->where($condition)->count();
            
            if($count >= Config::get("qrcode.qrcode_max_num")){
                $this->errorcode = EC_GENERATE_INSERT_MAX_COUNT;
            }else{
                $agentLinkFlag = $agentLinkModel->save($insertData);

                if($agentLinkFlag === false){
                    $this->errorcode = EC_GENERATE_INSERT_ERROR_CODE;
                }else{
                    $agentLinkId = $agentLinkModel->getLastInsID();
                    if($alParam['rebate']) {
                        
                        $rebateCon = $this->_buildRebateCondition($alParam['rebate'], $agentLinkId);
                        $rebateFlag = Loader::model("AgentLinkRebate")->saveAll($rebateCon);
                        if($rebateFlag === false){
                            $this->errorcode = EC_GENERATE_INSERT_ERROR_CODE;
                        }
                    }
                    return $this->_listGenerateQrcode($agentLinkId,$insertData);
                }
            }
        }
        catch(Exception $e) {
            Log::write("添加邀请码错误信息:" . $e->getMessage());
            $this->errorcode = EC_GENERATE_INSERT_ERROR_CODE;
        }
    }

    /***
     * @desc 获取邀请码列表
     * @param $param
     * @return mixed
     */
    public function getQrcodeList()
    {
        return Loader::model('AgentLink')->buildEnableAgentLinks();
    }


    /***
     * @desc 编辑邀请码
     * @param array $condition
     * @param  array $data
     */
    public function editQrCode($alParam)
    {

        try {
            $agentLinkRebateModel = Loader::model("AgentLinkRebate");
            $agentLinkModel = Loader::model("AgentLink", "model");

            $agentLinkData['agl_status'] = $alParam['status'];
            $where['agl_id'] = $alParam['id'];
            if($alParam['status'] == Config::get("qrcode.agl_status")['enable']) {
                $agentLinkFlag =  $agentLinkModel->where($where)->update($agentLinkData);
                if($agentLinkFlag === false){
                    $this->errorcode = EC_EDIT_GENERATE_ERROR_CODE;
                }
                if($alParam['rebate']) {

                    $count = $agentLinkRebateModel->where($where)->select();
                    if(count($count)>0){
                        $sql = $this->_buildRebateSql($alParam);
                        $agentLinkRebateFlag = $agentLinkRebateModel->execute($sql);
                    }else{
                        $rebateLinkData = $this->_buildRebateCondition($alParam['rebate'], $alParam['id']);
                        $agentLinkRebateFlag = $agentLinkRebateModel->saveAll($rebateLinkData);
                    }

                    if($agentLinkRebateFlag === false ) {
                        $this->errorcode = EC_EDIT_GENERATE_ERROR_CODE;
                    }
                }

            }elseif($alParam['status'] == Config::get("qrcode.agl_status")['disable']) {
                $flag = $agentLinkModel->where($where)->update($agentLinkData);
                if($flag === false){
                    $this->errorcode = EC_EDIT_GENERATE_ERROR_CODE;
                }
            }
        }catch(Exception $e) {
            Log::write("编辑邀请码错误信息:" . $e->getMessage());
            $this->errorcode = EC_EDIT_GENERATE_ERROR_CODE;
        }
    }

    /***
     * @desc 删除二维码图片
     * @param $condition
     * @param $data
     * @return bool
     */
    public function deleteQrCode($deleteAgentLinkParam)
    {
        try {
            $where['agl_id'] = $deleteAgentLinkParam['id'];
            $updateData['agl_status'] = Config::get("qrcode.agl_status")['delete'];
            $flag =  DB::name("AgentLink")->where($where)->update($updateData);
            if($flag === false ){
                $this->errorcode = EC_DELETE_GENERATE_ERROR_CODE;
            }
        }catch(Exception $e) {
            Log::write("删除邀请码错误信息:" . $e->getMessage());
            $this->errorcode = EC_DELETE_GENERATE_ERROR_CODE;
        }
    }

    /***
     * @desc 验证生成邀请码的数据
     * @param $param
     * @return mixed
     */
    private function _buildAgentLinkCondition($alParam)
    {
        $insertData['user_id']       = USER_ID;
        $insertData['agl_user_type'] = !empty($alParam['type'])?$alParam['type']:Config::get("qrcode.user_play_type")['player'] ;
        $insertData['agl_use_count'] = Config::get("qrcode.agent_link_default")['use_count'];
        $insertData['agl_endtime']   = Config::get("qrcode.agent_link_default")['expire_time'];
        $insertData['agl_code']      = random_string(Config::get("qrcode.rand_qrcode_strlen"));
        $insertData['agl_qrcode_url'] = QrcodeUpload::generateQrCode($insertData['agl_code']);

        $fileName                    = QrcodeUpload::getQrcodeFileName($insertData['agl_code']);
        $pathName                    = $insertData['agl_qrcode_url'];
        $flag                        = 1;
        $ossQrcode = new OssQrcode();
        while($flag <=5) {
            $qrcode_url = $ossQrcode->uploadQrcodeOss($fileName, $pathName);
            if(!$qrcode_url) {
                $flag++;
            }else{
                break;
            }
        }
        $insertData['agl_qrcode_url'] = $qrcode_url;
        return $insertData;
    }

    /***
     * @desc 获取生成邀请码后的返回数据
     * @param $agentLinkId
     * @param $insertData
     * @return array
     */
    private function _listGenerateQrcode($agentLinkId,$insertData){
        return ['id' => $agentLinkId, 'code' => $insertData['agl_code'],
            'userType' => $insertData['agl_user_type'],'userCount' => $insertData['agl_use_count'],
            'qrcode' => Env::get('oss.sports_url').$insertData['agl_qrcode_url'],
            'status' => Config::get("qrcode.agl_status")['enable']];
    }

    /***
     * @desc 生成新的rebate概率的sql语句
     * @param $alParam
     * @return string
     */
    private function _buildRebateSql($alParam)
    {
        $valueSql = '';
        foreach($alParam['rebate'] as $key => $value) {
            $valueSql .= " WHEN {$value['categoryId']} THEN {$value['userRebate']} ";
        }
        $sql = "UPDATE ds_agent_link_rebate SET rebate = CASE category_id";
        $sql .= $valueSql . " END WHERE agl_id = {$alParam['id']} and user_id =".USER_ID;
        return $sql;
    }


    /***
     * @desc 生成rebate值
     * @param $param
     * @param $agentLinkId
     * @return array
     */
    private function _buildRebateCondition($rebate, $agentLinkId)
    {
        $agentLinkData  = [];
        foreach($rebate as $key => $value) {
            $agentLinkData[] = [
                'user_id'       => USER_ID,
                'agl_id'        => $agentLinkId,
                'category_id'   => $value['categoryId'],
                'rebate'        => $value['userRebate']
            ];
        }
        return $agentLinkData;
    }

}