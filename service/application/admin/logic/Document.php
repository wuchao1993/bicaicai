<?php
/**
 * Created by PhpStorm.
 * 后台文档接口逻辑处理
 */

namespace app\admin\logic;

use think\Config;
use think\Exception;
use think\Loader;
use think\Log;
use think\Db;

class Document
{
    public $errorcode = EC_SUCCESS;

    /***
     * @desc 添加文档信息
     * @param $documentParam
     */
    public function addDocument($documentParam)
    {
        try {
            $documentData['document_title'] = $documentParam['title'];
            $documentData['document_content'] = $documentParam['content'];
            $documentData['document_description'] = $documentParam['description'];
            $documentData['document_image'] = $documentParam['image'];
            $documentData['document_sort'] = $documentParam['sort'];
            if(!empty($documentParam['type'])) {
                $documentData['document_type'] = $documentParam['type'];
            }else {
                $documentData['document_type'] = Config::get('status.document_type')['agent'];
            }
            $documentData['document_status'] = Config::get("status.document_status")["enable"];
            $documentData['document_createtime'] = current_datetime();
            $documentData['document_modifytime'] = current_datetime();
            $flag = Loader::model("Document")->save($documentData);
            if($flag === false){
                $this->errorcode = EC_INSERT_DOCUMENT_ERROR;
            }
        }
        catch(Exception $e) {
            Log::write("添加文档信息报错:" . $e->getMessage());
            $this->errorcode = EC_INSERT_DOCUMENT_ERROR;
        }
    }

    /***
     * @desc 编辑文档信息
     * @param $documentParam
     */
    public function editDocument($documentParam)
    {
        try {
            if(empty($documentParam['type'])){
                $this->errorcode = EC_EDIT_DOCUMENT_NO_TYPE;
                return false;
            }
            $documentModel = Loader::model("Document");
            $where['document_id'] = $documentParam['id'];
            $documentData = $this->_buildEditCondition($documentParam);
            $flag = $documentModel->save($documentData,["document_id"=>$documentParam['id']]);
            if($flag === false){
                $this->errorcode = EC_EDIT_DOCUMENT_ERROR;
            }
        }
        catch(Exception $e) {
            Log::write("编辑文档信息报错：" . $e->getMessage());
            $this->errorcode = EC_EDIT_DOCUMENT_ERROR;
        }

    }

    /***
     * @desc 状态为1，转换条件
     * @param $documentParam
     * @return mixed
     */
    private function _buildEditCondition($documentParam){
        $documentData['document_status'] = $documentParam['status'];
        $documentData['document_modifytime'] = current_datetime();
        $documentData['document_title'] = $documentParam['title'];
        $documentData['document_content'] = $documentParam['content'];
        $documentData['document_description'] = $documentParam['description'];
        $documentData['document_image'] = $documentParam['image'];
        $documentData['document_sort'] = $documentParam['sort'];
        $documentData['document_type'] = $documentParam['type'];

        return $documentData;
    }

    /***
     * @desc 删除文档信息
     * @param $documentParam
     */
    public function deleteDocument($documentParam)
    {
        try {
            $documentModel = Loader::model("Document");
            $where['document_id'] = $documentParam['id'];
            $documentData['document_status'] = Config::get("status.document_status")["delete"];
            $flag = $documentModel->where($where)->update($documentData);
            if($flag === false){
                $this->errorcode = EC_DELETE_DOCUMENT_ERROR;
            }
        }
        catch(Exception $e) {
            Log::write("删除文档信息报错：" . $e->getMessage());
            $this->errorcode = EC_DELETE_DOCUMENT_ERROR;
        }
    }

    /***
     * @desc 获取文档信息
     * @param $documentParam
     */
    public function getDocument($documentParam)
    {
        try {
            $condition = $this->_buildCondition($documentParam["data"]);

            $field = ['document_id'  =>  'id',
                      'document_title' => 'title',
                      'document_content' => 'content',
                      'document_status' => 'status',
                      'document_description' => 'description',
                      'document_sort' => 'sort',
                      'document_type' => 'type',
                      'document_image' => 'image',
                      'document_createtime' => 'createtime',
                      'document_modifytime' => 'modifytime'];

            $order = "document_sort asc,document_createtime desc";
            $documentData["list"] = Loader::model("Document")->where($condition)
                            ->field($field)->limit($documentParam['num'])->page($documentParam['limitStartNumber'])
                            ->order($order)->select();
            foreach($documentData['list'] as $k=>&$v){
                if($v['type'] == 1){
                    $v['type'] = Config::get("status.document_status_description")[$v["type"]];
                }
            }
            $documentData['totalCount'] = Loader::model("Document")->where($condition)->count();

            if(count($documentData) <= 0) {
                $this->errorcode = EC_LIST_DOCUMENT_ERROR;
            }
            return $documentData;
        }
        catch(Exception $e) {
            Log::write("获取文档信息报错：" . $e->getMessage());
            $this->errorcode = EC_LIST_DOCUMENT_ERROR;
        }
    }

    public function getDocumentById($documentParam){

        $where['document_id'] = $documentParam['id'];
        $field = ['document_id'  =>  'id',
            'document_title' => 'title',
            'document_content' => 'content',
            'document_status' => 'status',
            'document_description' => 'description',
            'document_sort' => 'sort',
            'document_type' => 'type',
            'document_image' => 'image',
            'document_createtime' => 'createtime',
            'document_modifytime' => 'modifytime'];

        $documentData = Db::name("Document")->field($field)->where($where)->find();
        if(!$documentData){
            $this->errorcode = EC_LIST_DOCUMENT_BYID_EMPTY;
        }
        return $documentData;
    }

    /***
     * @desc 获取指定类型文档信息
     * @param $documentParam
     */
    public function getDocumentType()
    {
        $documentResult = [[
            'id'            =>  Config::get("status.document_type")['agent'],
            'description'   =>  Config::get("status.document_status_description")[
                Config::get("status.document_type")['agent']],
        ]];
        return $documentResult;
    }

    /***
     * @desc 获取分页条件
     * @param $documentParam
     * @return array
     */
    private function _buildCondition($documentParam)
    {
        //文档最后修改时间
        $startTime = empty($documentParam['startTime']) ?
                        Config::get("status.document_default_time")['start_time'] :
                        $documentParam['startTime']." 00:00:00";
        $endTime = empty($documentParam['endTime']) ?
                        Config::get("status.document_default_time")['end_time'] :
                        $documentParam['endTime']." 23:59:59";

        if(!empty($startTime) && !empty($endTime)) {
            $where['document_modifytime'] = [['EGT', $startTime], ['ELT', $endTime]];
        }
        else {
            if(!empty($startTime)) {
                $where['document_modifytime'] = ['EGT', $startTime];
            }
            if(!empty($endTime)) {
                $where['document_modifytime'] = ['ELT', $endTime];
            }
        }

        //文档类型
        if(!empty($documentParam['type'])) {
            $where['document_type'] = $documentParam['type'];
        }else{
            $where['document_type'] = Config::get('status.document_type')['agent'];
        }
        //文档状态
        if(!empty($documentParam['status'])) {
            $where['document_status'] = $documentParam['status'];
        }
        else {
            $where['document_status'] = ["NEQ",Config::get("status.document_status")["delete"]];
        }
        //文档标题
        if(!empty($documentParam['keyword'])) {
            $title = trim($documentParam['keyword']);
            $where['document_title|document_content'] = ['like', "%{$title}%"];
        }
        return $where;
    }

}