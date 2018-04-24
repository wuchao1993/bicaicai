<?php
namespace app\common\model;

use think\Model;

class Document extends Model{

    public $pk = 'document_id';

    public function getDocumentsByType($type){
        $condition = [
            'document_type' => $type
        ];
        return $this->where($condition)->select();
    }

}