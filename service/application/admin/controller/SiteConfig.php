<?php

/**
 * 网站配置控制器（新）
 * @author paulli
 */

namespace app\admin\controller;

use think\Request;
use think\Loader;
use think\Config;

class SiteConfig {

    /**
     * 获取网站配置列表
     *
     * @param Request $request
     * @return array
     */
    public function getSiteConfigList(Request $request) {
        $params ['sc_group'] = $request->param('group',0);
        $params ['page']  = $request->param('page',1);
        $params ['num']   = $request->param('num',10);
        $params ['sc_lottery_type']   = $request->param('lotteryType',2);

        $configLogic = Loader::model('SiteConfig', 'logic');
        $configList  = $configLogic->getList($params);

        foreach($configList['list'] as &$info) {
            $info = $this->_packSiteConfigInfo($info);
        }

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => output_format($configList)
        ];
    }

    /**
     * 获取配置信息
     *
     * @param Request $request
     * @return array
     */
    public function getSiteConfigInfo(Request $request) {
        $id = $request->param('id');

        $configLogic = Loader::model('SiteConfig', 'logic');
        $configInfo  = $configLogic->getInfo($id);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => output_format($configInfo)
        ];
    }

    /**
     * 新增配置
     *
     * @param Request $request
     * @return array
     */
    public function addConfig(Request $request) {
        $params ['sc_name']         = $request->param('name');
        $params ['sc_title']        = $request->param('title');
        $params ['sc_remark']       = $request->param('remark', '');
        $params ['sc_type']         = $request->param('type', 0);
        $params ['sc_lottery_type'] = $request->param('lotteryType', 2);
        $params ['sc_group']        = $request->param('group', 0);
        $params ['sc_extra']        = $request->param('extra', '');
        $params ['sc_value']        = $request->param('value', '');
        $params ['sc_sort']         = $request->param('sort', 0);

        $configLogic = Loader::model('SiteConfig', 'logic');
        $configInfo  = $configLogic->add($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => output_format($configInfo)
        ];
    }

    /**
     * 编辑配置
     *
     * @param Request $request
     * @return array
     */
    public function editConfig(Request $request) {
        $params ['sc_id']           = $request->param('id');
        $params ['sc_name']         = $request->param('name');
        $params ['sc_title']        = $request->param('title');
        $params ['sc_remark']       = $request->param('remark', '');
        $params ['sc_type']         = $request->param('type', 0);
        $params ['sc_lottery_type'] = $request->param('lotteryType', 2);
        $params ['sc_group']        = $request->param('group', 3);
        $params ['sc_extra']        = $request->param('extra', '');
        $params ['sc_value']        = $request->param('value', '');
        $params ['sc_sort']         = $request->param('sort', 0);

        $configLogic = Loader::model('SiteConfig', 'logic');
        $result      = $configLogic->edit($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 删除配置
     *
     * @param Request $request
     * @return array
     */
    public function delConfig(Request $request) {
        $params ['sc_id'] = $request->param('id/a');

        $configLogic = Loader::model('SiteConfig', 'logic');
        $result      = $configLogic->del($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => $result
        ];
    }

    /**
     * 编辑网站设置
     *
     * @param Request $request
     * @return array
     */
    public function editGroup(Request $request) {
        $params ['config'] = $request->param('config/a');

        $configLogic = Loader::model('SiteConfig', 'logic');
        $result      = $configLogic->editGroup($params);

        return [
            'errorcode' => $configLogic->errorcode,
            'message'   => Config::get('errorcode') [$configLogic->errorcode],
            'data'      => $result
        ];
    }

    private function _packSiteConfigInfo($info) {
        return [
            'id'          => $info ['sc_id'],
            'name'        => $info ['sc_name'],
            'type'        => $info ['sc_type'],
            'title'       => $info ['sc_title'],
            'lotteryType' => $info ['sc_lottery_type'],
            'group'       => $info ['sc_group'],
            'extra'       => $info ['sc_extra'],
            'remark'      => $info ['sc_remark'],
            'value'       => $info ['sc_value'],
            'sort'        => $info ['sc_sort'],
        ];
    }
}
