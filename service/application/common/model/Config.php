<?php
namespace app\common\model;

use think\Model;

class Config extends Model{

    public $pk = 'id';

    public function getConfig($name){
        $condition = [
            'name' => $name,
            'status' => 1,
        ];

        $info = $this->where($condition)->find();
        if(!is_object($info)){
            return false;
        }

        $info = $info->toArray();
        if($info) {
            if($info['type'] == 3){
                $value = $this->_parse($info['value']);
            } else {
                $value = $info['value'];
            }
        }

        return $value;
    }

    private function _parse($value){
        $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
        foreach ($array as $key=>&$row){
            $row = trim($row);
        }

        if(strpos($value,':')){
            $value  = [];
            foreach ($array as $val) {
                list($k, $v) = explode(':', $val);
                $value[$k]   = $v;
            }
        }else{
            $value = $array;
        }

        return $value;
    }
}