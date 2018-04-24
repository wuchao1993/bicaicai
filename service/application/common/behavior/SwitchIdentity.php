<?php
/**
 * 选择用户身份
 * @createTime 2017/7/17 15:09
 */

namespace app\common\behavior;

use think\Controller;
use think\Request;
use think\Response;
use think\Hook;
use think\Config;
use think\exception\HttpResponseException;

class SwitchIdentity extends Controller {

    public function run(&$identity) {
        $request = Request::instance();
        $action = strtolower($request->controller() . '/' . $request->action());
        $authIdentity = $request->header('Auth-Identity') ?: $request->param('identity');
        if (isset($identity) && $identity && in_array($identity, ['host', 'normal', 'guest', 'special'])) {
            $status = $identity;
        } elseif (in_array($action, Config::get('common.guest_action'))) {
            $status = 'guest';
        } elseif (in_array($action, Config::get('common.agent_action'))) {
            $status = 'special';
        } elseif ($authIdentity) {
            $status = $authIdentity;
        } else {
            $status = 'normal';
        }

        if (!in_array($status, ['host', 'normal', 'guest', 'special'])) {
            $data = ['errorcode' => EC_IDENTITY_STATUS_ERROR, 'message' => Config::get('errorcode')[EC_IDENTITY_STATUS_ERROR]];
            $type = $this->getResponseType();
            $response = Response::create($data, $type);
            Hook::listen('Cross', $response);
            throw new HttpResponseException($response);
        }

        $GLOBALS['auth_identity'] = $status;

        //重新加载数据库配置文件和缓存文件
        $dbFilename = CONF_PATH . 'extra' . DS . 'database' . CONF_EXT;
        $cacheFilename = CONF_PATH . 'extra' . DS . 'cache' . CONF_EXT;
        Config::load($dbFilename, 'database');
        Config::load($cacheFilename, 'cache');
    }
}