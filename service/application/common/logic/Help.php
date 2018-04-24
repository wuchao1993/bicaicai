<?php
namespace app\common\logic;

use think\Loader;

class Help{

    public $errorcode = EC_SUCCESS;

    public function getHelpList($type, $page= 1, $count = 10) {

        $condition = [
            'help_type' => $type,
        ];

        $fields = "help_id as id, help_title as title, help_content as content";
        $result = Loader::model('Help')->where($condition)->page($page)->limit($count)->field($fields)->order('help_sort')->select();

        return $result ? collection($result) : [];
    }

}