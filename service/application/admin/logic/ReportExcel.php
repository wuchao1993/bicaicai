<?php

namespace app\admin\logic;

use PHPExcel;
use PHPExcel_Writer_Excel5;

class ReportExcel
{

    /**
    * ExportPushList
    * @param $list 数据
    * @param $excel_title Excel单元格标题
    * @param $file_name Excel文件名
    */
    public function exportList($list,$excelTitle,$fileName='ExcelList') {
        $excel = new PHPExcel();
        $excelSave = new PHPExcel_Writer_Excel5($excel);

        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle('sheet1');
        $data = (object) $list;

        $Cell = '';
        foreach($excelTitle as $key=>$rs){
            $header[$key]=$rs;
        }
        foreach($header as $key=>$vo){
            $Cell = $Cell?$this->letterAdd($Cell):'A';
            $sheet->setCellValue($Cell.'1',$vo);
        }
        $code = 2;

        $Letter=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T');

        //读取数据库//数据数组必须预先处理要保留的数组元素
        foreach($data as $key=>$vo){
            $i=0;
            foreach($vo as $key=>$rs){
                $sheet->setCellValue($Letter[$i].$code,$rs);
                $i=$i+1;
            }
            $code++;
        }

        foreach ($Letter as $column){
            $sheet->getColumnDimension($column)->setWidth(20);
        }

        $outputFileName = $fileName.'.xls';
//        header("Pragma: public");
//        header("Expires: 0");
//        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
//        header("Content-Type:application/force-download");
//        header("Content-Type:application/vnd.ms-execl");
//        header("Content-Type:application/octet-stream");
//        header("Content-Type:application/download");;
//        header('Content-Disposition:attachment;filename='.$outputFileName);
////        header("Content-Transfer-Encoding:binary");
//        $excelSave->save('php://output');
        $excelSave->save($outputFileName);
    }

    /**
     * 英文字母累加函数
     * A,B,C,D...X,Y,Z,AA,AB...AZ,BA,BB...
     * 也就是说如果传入参数是ZZ，那么返回的就是AAA。
     */
    public function letterAdd($s){
        $Str = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $len = strlen($s);
        $i = 1;
        do{
            $a2 = substr($s,$len-$i,1);
            $pos = strpos($Str,$a2)+1;
            $b2 = $pos>25 ? "A" : $Str[$pos];
            $a1 = $len==$i ? ($b2=="A"?"A":"") : substr($s,0,$len-$i);
            $a3 = $i==1 ? "" : substr($s,$len-$i+1);
            $s = $a1.$b2.$a3;
            $i++;
        }while($b2=="A" && $len>$i-1);
        return $s;
    }



}