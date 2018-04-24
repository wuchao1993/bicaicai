<?php
namespace app\admin\logic;

use PHPExcel;
use PHPExcel_Writer_Excel5;
use PHPExcel_Writer_Excel2007;

class ImportExcel{

    /**
     * 错误变量
     * @var
     */
    public $errorcode = EC_AD_SUCCESS;


    /**
     * 二进制流上传
     * @return array|bool
     */
    public function getExcelDataByBinary(){
        $content = file_get_contents('php://input');
        $filepath = RUNTIME_PATH .'system_recharge.xlsx';
        file_put_contents($filepath,$content,LOCK_EX);

        $data = [];
        if($content){
            $reader = new \PHPExcel_Reader_Excel2007();
            if(!$reader->canRead($filepath)){
                $reader = new \PHPExcel_Reader_Excel5();
                if(!$reader->canRead($filepath)){
                    $this->errorcode = EC_AD_EXCEL_NOT_READ;
                    return false;
                }
            }
            $excel = $reader->load($filepath);
            $sheet = $excel->getSheet()->toArray();
            unset($sheet[0]);

            if(!$sheet){
                $this->errorcode = EC_AD_EXCEL_NOT_DATA;
                return false;
            }


            foreach($sheet as $vo){
                //过滤：第一列不为空
                if(!empty($vo[0])){
                    $data[] = $vo;
                }
            }
        }

        unlink($filepath);
        return $data;
    }

    /**
     * 获取excel表数据
     * form-data 文件字段 file
     * @return array|bool
     */
    public function getExcelData(){

        $name = $_FILES['file']['name'];
        if($name){
            $filepath = $_FILES['file']['tmp_name'];
            $reader = new \PHPExcel_Reader_Excel2007();
            if(!$reader->canRead($filepath)){
                $reader = new \PHPExcel_Reader_Excel5();
                if(!$reader->canRead($filepath)){
                    $this->errorcode = EC_AD_EXCEL_NOT_READ;
                    return false;
                }
            }
            $excel = $reader->load($filepath);
            $sheet = $excel->getSheet()->toArray();
            unset($sheet[0]);

            if(!$sheet){
                $this->errorcode = EC_AD_EXCEL_NOT_DATA;
                return false;
            }

            $data = [];
            foreach($sheet as $vo){
                //过滤：第一列不为空
                if(!empty($vo[0])){
                    $data[] = $vo;
                }
            }
            return $data;
        }
    }


}