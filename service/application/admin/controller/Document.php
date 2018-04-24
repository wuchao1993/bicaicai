<?php

namespace app\admin\controller;
use think\Request;
use think\Loader;
use think\Config;


class Document
{

    /***
     * @desc 添加文档信息
     * @param Request $request
     * @return array
     */
    public function addDocument(Request $request){
        $documentLogic = Loader::model("Document","logic");
        $documentLogic->addDocument($request->post());

        return send_response('', $documentLogic->errorcode);
    }

    /***
     * @desc 编辑文档信息
     * @param Request $request
     * @return array
     */
    public function editDocument(Request $request){
        $documentLogic = Loader::model("Document","logic");
        $documentLogic->editDocument($request->post());

        return send_response('',$documentLogic->errorcode);
    }

    /***
     * @desc 删除文档信息
     * @param Request $request
     * @return array
     */
    public function deleteDocument(Request $request){
        $documentLogic = Loader::model("Document","logic");
        $documentLogic->deleteDocument($request->post());

        return send_response('',$documentLogic->errorcode);
    }

    /***
     * @desc 获取文档信息
     * @param Request $request
     * @return array
     */
    public function getDocumentList(Request $request){
        $documentLogic = Loader::model("Document","logic");
        $count = Config::get ("qrcode.limit_num")['count'];
        $limitStartNumber = Config::get ("qrcode.limit_num")['page'];
        $param["limitStartNumber"] = $request->param("page",$limitStartNumber);
        $param["num"] = $request->param("num",$count);
        $param["data"] = $request->post();
        $documentData  = $documentLogic->getDocument($param);

        return send_response($documentData,$documentLogic->errorcode);
    }

    /***
     * @desc 通过类型获取文档信息
     * @param Request $request
     * @return array
     */
    public function getDocumentType(){
        $documentLogic = Loader::model("Document","logic");
        $documentData  = $documentLogic->getDocumentType();

        return send_response($documentData,$documentLogic->errorcode);
    }

    /***
     * @desc 获取指定文档详情
     * @param Request $request
     * @return array
     */
    public function getDocumentById(Request $request){
        $documentLogic = Loader::model("Document","logic");
        $documentData  = $documentLogic->getDocumentById($request->post());

        return send_response($documentData,$documentLogic->errorcode);

    }
}