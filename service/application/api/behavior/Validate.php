<?php
/**
 * 全局验证行为
 * @createTime 2017/3/22 11:04
 */

namespace app\api\behavior;

use think\Controller;
use think\Hook;
use think\Loader;
use think\Request;
use think\Response;
use think\exception\HttpResponseException;

class Validate extends Controller {

    private $action;

    /**
     * 全局验证
     * @return bool
     */
    public function run() {
        $request = Request::instance();
        $controller = $request->controller();
        $action = $request->action();
        $this->action = $action;
        try {
            $validate = Loader::validate($controller);
        } catch(\Exception $e) {
            return true;
        }
        if (empty($validate->scene)) {
            return true;
        }
        $arrActionName = array_filter(array_keys($validate->scene), function($v) {
            return $this->action === $v ? true: false;
        });
        if(!empty($arrActionName)) {
            $strActionName = reset($arrActionName);
            $params = $request->param();
            if(!$validate->scene($strActionName)->check($params)) {
                $data = ['errorcode' => EC_PARAMS_ILLEGAL, 'message' => $validate->getError()];
                $type = $this->getResponseType();
                $response = Response::create($data, $type);
                Hook::listen('Cross', $response);
                throw new HttpResponseException($response);
            }
        };
        return true;
    }
}