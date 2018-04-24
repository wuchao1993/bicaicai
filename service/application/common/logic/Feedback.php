<?php
namespace app\common\logic;

use think\Loader;

class Feedback{

    public $errorcode = EC_SUCCESS;

    public function addFeedback($params) {
        if(USER_ID){
            $data['user_id'] = USER_ID;
        }

        $data['feedback_content'] = $params['content'];
        $data['feedback_contact'] = $params['contact'];
        $data['feedback_createtime'] = current_datetime();

        $result = Loader::model('Feedback')->save($data);

        if($result == false){
            $this->errorcode = EC_DATABASE_ERROR;
        }

        return $result;
    }
    
}