<?php
namespace app\api\controller;

use think\Loader;
use think\Request;
use think\Config;

class Help{

    public function getHelpList(Request $request) {

        $type = $request->post('type', 1);
        $page = $request->post('page', 1);
        $count = $request->post('count', 10);

        $helpLogic = Loader::model('Help', 'logic');

        $data = $helpLogic->getHelpList($type, $page, $count);

        return [
            'errorcode' => $helpLogic->errorcode,
            'message'   => Config::get('errorcode')[$helpLogic->errorcode],
            'data' => output_format($data)
        ];
    }


    public function feedback(Request $request) {
        $params = $request->post();
        $feedbackLogic = Loader::model('Feedback', 'logic');

        $feedbackLogic->addFeedback($params);

        return [
            'errorcode' => $feedbackLogic->errorcode,
            'message'   => Config::get('errorcode')[$feedbackLogic->errorcode]
        ];
    }

}