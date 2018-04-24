<?php
namespace app\common\model;

use think\Model;

class CategoryDisplay extends Model{

    public function getCategoryNameMap(){
        return $this->column('category_display_name', 'category_display_id');
    }


}