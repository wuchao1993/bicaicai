<?php

/**
 * 数字彩控制器
 * @author
 */

namespace app\admin\controller;

use think\Log;
use think\Request;
use think\Loader;
use think\Config;
use curl\Curlrequest;

class Lottery {

    /**
     * 获取游戏分类（搜索查询专用）
     *
     * @param Request $request
     * @return array
     */
    public function getList(Request $request) {
        $lotteryLogic = Loader::model('Lottery', 'logic');
        $categoryList = $lotteryLogic->getList();

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => output_format($categoryList),
        ];
    }

    /**
     * 获取分类设置
     *
     * @return array
     */
    public function getCategoryList() {

        $lotteryCategoryLogic = Loader::model('LotteryCategory', 'logic');
        $categoryList         = $lotteryCategoryLogic->getCategoryList();

        foreach($categoryList as &$info) {
            $info = $this->_packLotteryCategoryInfo($info);
        }

        return [
            'errorcode' => $lotteryCategoryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryCategoryLogic->errorcode],
            'data'      => output_format($categoryList),
        ];
    }

    /**
     * 编辑分类
     *
     * @param Request $request
     * @return array
     */
    public function editCategory(Request $request) {
        $params ['lottery_category_id']    = $request->param('id');
        $params ['lottery_category_name']  = $request->param('categoryName');
        $params ['lottery_category_model'] = $request->param('categoryModel');
        $params ['lottery_default_rebate'] = $request->param('defaultRebate');
        $params ['lottery_category_sort']  = $request->param('categorySort');

        $lotteryCategoryLogic = Loader::model('LotteryCategory', 'logic');
        $result               = $lotteryCategoryLogic->editCategory($params);

        return [
            'errorcode' => $lotteryCategoryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryCategoryLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取注单状态
     *
     * @param Request $request
     * @return array
     */
    public function getOrderStatusList(Request $request) {
        $lotteryLogic = Loader::model('Lottery', 'logic');

        $data = [];
        foreach(Config::get('status.lottey_order_status_name') as $key => $val) {
            $data [$key - 1] = [
                'id'    => $key,
                'value' => $val,
            ];
        }

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => output_format($data),
        ];
    }

    /**
     * 获取展示分类列表
     *
     * @param Request $request
     * @return array
     */
    public function getCategoryDisplayList(Request $request) {
        $categoryDisplayLogic = Loader::model('CategoryDisplay', 'logic');
        $categoryList         = $categoryDisplayLogic->getCategoryDisplayList();

        foreach($categoryList as &$info) {
            $info = $this->_packCategoryDisplayInfo($info);
        }

        return [
            'errorcode' => $categoryDisplayLogic->errorcode,
            'message'   => Config::get('errorcode') [$categoryDisplayLogic->errorcode],
            'data'      => output_format($categoryList),
        ];
    }

    /**
     * 编辑展示分类
     *
     * @param Request $request
     * @return array
     */
    public function editCategoryDisplay(Request $request) {
        $params ['category_display_id']           = $request->param('id');
        $params ['category_display_name']         = $request->param('name');
        $params ['category_display_image']        = $request->param('image');
        $params ['category_display_sort']         = $request->param('sort');
        $params ['category_display_hot']          = $request->param('hot');
        $params ['category_display_introduction'] = $request->param('introduction');

        $categoryDisplayLogic = Loader::model('CategoryDisplay', 'logic');
        $result               = $categoryDisplayLogic->editCategory($params);

        return [
            'errorcode' => $categoryDisplayLogic->errorcode,
            'message'   => Config::get('errorcode') [$categoryDisplayLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取彩种列表
     *
     * @param Request $request
     * @return array
     */
    public function getGameList(Request $request) {
        $params ['page'] = $request->param('page',1);
        $params ['num']  = $request->param('num',10);

        if($request->param('name') != '') {
            $params ['lottery_name'] = $request->param('name');
        }

        $lotteryLogic = Loader::model('Lottery', 'logic');
        $gameList     = $lotteryLogic->getGameList($params);

        foreach($gameList ['list'] as &$info) {
            $info = $this->_packGameInfo($info);
        }

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => output_format($gameList),
        ];
    }

    /**
     * 添加彩种
     *
     * @param Request $request
     * @return array
     */
    public function addGame(Request $request) {
        $params ['lottery_name']            = $request->param('name');
        $params ['lottery_category_id']     = $request->param('categoryId');
        $params ['category_display_id']     = $request->param('displayId');
        $params ['lottery_image_url']       = $request->param('image');
        $params ['lottery_introduction']    = $request->param('introduction');
        $params ['lottery_description']     = $request->param('description');
        $params ['lottery_message_explain'] = $request->param('messageExplain');
        $params ['lottery_is_hot']          = $request->param('hot');
        $params ['lottery_status']          = $request->param('status');
        $params ['lottery_sort']            = $request->param('sort');

        $lotteryLogic = Loader::model('Lottery', 'logic');
        $result       = $lotteryLogic->addGame($params);

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 编辑彩种
     *
     * @param Request $request
     * @return array
     */
    public function editGame(Request $request) {
        $params ['lottery_id']              = $request->param('id');
        $params ['lottery_category_id']     = $request->param('categoryId');
        $params ['category_display_id']     = $request->param('displayId');
        $params ['lottery_image_url']       = $request->param('image');
        $params ['lottery_introduction']    = $request->param('introduction');
        $params ['lottery_description']     = $request->param('description');
        $params ['lottery_message_explain'] = $request->param('messageExplain');
        $params ['lottery_is_hot']          = $request->param('hot');
        $params ['lottery_status']          = $request->param('status');
        $params ['lottery_sort']            = $request->param('sort');

        $lotteryLogic = Loader::model('Lottery', 'logic');
        $result       = $lotteryLogic->editGame($params);

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取玩法列表
     *
     * @param Request $request
     * @return array
     */
    public function getPlayList(Request $request) {
        $params ['page'] = $request->param('page',1);
        $params ['num']  = $request->param('num',10);

        if($request->param('categoryId') != '') {
            $params ['lottery_category_id'] = $request->param('categoryId');
        }

        $lotteryPlayLogic = Loader::model('LotteryPlay', 'logic');
        $playList         = $lotteryPlayLogic->getPlayList($params);

        foreach($playList ['list'] as &$info) {
            $info = $this->_packPlayInfo($info);
        }

        return [
            'errorcode' => $lotteryPlayLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryPlayLogic->errorcode],
            'data'      => output_format($playList),
        ];
    }

    /**
     * 编辑玩法
     *
     * @param Request $request
     * @return array
     */
    public function editPlay(Request $request) {
        $params ['play_id']      = $request->param('id');
        $params ['play_help']    = $request->param('help');
        $params ['play_example'] = $request->param('example');
        $params ['play_tips']    = $request->param('tips');

        $lotteryPlayLogic = Loader::model('LotteryPlay', 'logic');
        $result           = $lotteryPlayLogic->editPlay($params);

        return [
            'errorcode' => $lotteryPlayLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryPlayLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取彩种设置
     *
     * @param Request $request
     * @return array
     */
    public function getTypeConfigList(Request $request) {
        if($request->param('categoryId') != '') {
            $params ['lottery_category_id'] = $request->param('categoryId');
        }

        $lotteryTypeConfigLogic = Loader::model('LotteryTypeConfig', 'logic');
        $typeConfigList         = $lotteryTypeConfigLogic->getTypeConfigList($params);
        foreach($typeConfigList as &$info) {
            $info = $this->_packTypeConfigInfo($info);
        }

        return [
            'errorcode' => $lotteryTypeConfigLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryTypeConfigLogic->errorcode],
            'data'      => output_format($typeConfigList),
        ];
    }

    /**
     * 编辑彩种设置
     *
     * @param Request $request
     * @return array
     */
    public function editTypeConfig(Request $request) {
        $params ['configIds']  = $request->param('configIds/a');
        $params ['defaultIds'] = $request->param('defaultIds/a');
        $params ['sortIds']    = $request->param('sortIds/a');
        $params ['headIds']    = $request->param('headIds/a');

        $lotteryTypeConfigLogic = Loader::model('LotteryTypeConfig', 'logic');
        $result                 = $lotteryTypeConfigLogic->editTypeConfig($params);

        return [
            'errorcode' => $lotteryTypeConfigLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryTypeConfigLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取限额设置列表
     *
     * @param Request $request
     * @return array
     */
    public function getPlayConfigList(Request $request) {
        $params ['page'] = $request->param('page',1);
        $params ['num']  = $request->param('num',1000);

        if($request->param('categoryId') != '') {
            $params ['lottery_category_id'] = $request->param('categoryId');
        }

        $lotteryPlayLogic = Loader::model('LotteryPlay', 'logic');
        $playList         = $lotteryPlayLogic->getPlayConfigList($params);

        foreach($playList as &$info) {
            $info = $this->_packPlayConfigInfo($info);
        }

        return [
            'errorcode' => $lotteryPlayLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryPlayLogic->errorcode],
            'data'      => output_format($playList),
        ];
    }

    /**
     * 编辑限额设置
     *
     * @param Request $request
     * @return array
     */
    public function editPlayConfig(Request $request) {
        $params ['configIds'] = $request->param('configIds/a');

        $lotteryPlayLogic = Loader::model('LotteryPlay', 'logic');
        $result           = $lotteryPlayLogic->editPlayConfig($params);

        return [
            'errorcode' => $lotteryPlayLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryPlayLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取注单列表
     *
     * @param Request $request
     * @return array
     */
    public function getOrderList(Request $request) {
        $params ['page'] = $request->param('page',1);
        $params ['num']  = $request->param('num',10);

        if($request->param('orderId') != '') {
            $params ['order_id'] = $request->param('orderId');
        }

        if($request->param('orderNo') != '') {
            $params ['order_no'] = $request->param('orderNo');
        }

        if($request->param('username') != '') {
            $params ['user_name'] = $request->param('username');
        }

        if(!empty($request->param('lotteryId/a'))) {
            $params ['lottery_id'] = ['IN',$request->param('lotteryId/a')];
        }

        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d H:i:s', strtotime($request->param('startDate')));
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d H:i:s', strtotime($request->param('endDate')));
        }

        if($request->param('issueNo') != '') {
            $params ['issue_no'] = $request->param('issueNo');
        }

        if(!empty ($request->param('status/a'))) {
            $params ['status'] = $request->param('status/a');
        }

        if(!empty ($request->param('sortType'))) {
            $params ['sortType'] = $request->param('sortType');
        }

        if(!empty ($request->param('sortMode'))) {
            $params ['sortMode'] = $request->param('sortMode');
        }

        $lotterOrderLogic = Loader::model('LotteryOrder', 'logic');
        $orderList        = $lotterOrderLogic->getOrderList($params);

        foreach($orderList ['list'] as &$info) {
            $info = $this->_packOrderList($info);
        }

        return [
            'errorcode' => $lotterOrderLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotterOrderLogic->errorcode],
            'data'      => output_format($orderList),
        ];
    }

    /**
     * 获取注单详情
     *
     * @param Request $request
     * @return array
     */
    public function getOrderInfo(Request $request) {
        $id = $request->param('id');

        $lotterOrderLogic = Loader::model('LotteryOrder', 'logic');
        $orderInfo        = $lotterOrderLogic->getOrderInfo($id);
        if(!empty($orderInfo)){
            $orderInfo        = $this->_packOrderInfo($orderInfo);
        }

        return [
            'errorcode' => $lotterOrderLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotterOrderLogic->errorcode],
            'data'      => output_format($orderInfo),
        ];
    }

    /**
     * 获取开奖列表
     *
     * @param Request $request
     * @return array
     */
    public function getIssueList(Request $request) {
        $params ['page'] = $request->param('page',1);
        $params ['num']  = $request->param('num',10);

        if($request->param('lotteryId') != '') {
            $params ['lottery_id'] = $request->param('lotteryId');
        }

        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }

        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if($request->param('issueNo') != '') {
            $params ['issue_no'] = $request->param('issueNo');
        }

        if($request->param('status') != '') {
            $params ['issue_prize_status'] = $request->param('status');
        }

        if(!empty ($request->param('sortType'))) {
            $params ['sortType'] = $request->param('sortType');
        }

        if(!empty ($request->param('sortMode'))) {
            $params ['sortMode'] = $request->param('sortMode');
        }

        $lotterLogic = Loader::model('LotteryIssue', 'logic');
        $issueList   = $lotterLogic->getIssueList($params);

        foreach($issueList ['list'] as &$info) {
            $info = $this->_packIssueList($info);
        }

        return [
            'errorcode' => $lotterLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotterLogic->errorcode],
            'data'      => output_format($issueList),
        ];
    }

    /**
     * 编辑开奖
     *
     * @param Request $request
     * @return array
     */
    public function editIssue(Request $request) {
        $params ['lottery_issue_id']        = $request->param('id');
        $params ['lottery_issue_prize_num'] = $request->param('issuePrizeNum');

        $lotteryLogic = Loader::model('LotteryIssue', 'logic');
        $result       = $lotteryLogic->editIssue($params);

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取六合彩开奖列表
     *
     * @param Request $request
     * @return array
     */
    public function getLhcIssueList(Request $request) {

        Log::write('lhc.params:'.print_r($request->param(),true));

        $params ['page'] = $request->param('page',1);
        $params ['num']  = $request->param('num',10);

        $params ['lottery_id'] = Config::get('six.LHC_LOTTERY_ID');

        if($request->param('startDate') != '') {
            $params ['start_date'] = date('Y-m-d 00:00:00', strtotime($request->param('startDate')));
        }
        if($request->param('endDate') != '') {
            $params ['end_date'] = date('Y-m-d 23:59:59', strtotime($request->param('endDate')));
        }
        if($request->param('issueNo') != '') {
            $params ['issue_no'] = $request->param('issueNo');
        }
        if($request->param('prizeStatus') != '') {
            $params ['issue_prize_status'] = $request->param('prizeStatus');
        }

        $lotteryLogic = Loader::model('LotteryIssue', 'logic');
        $issueList    = $lotteryLogic->getIssueList($params);

        foreach($issueList ['list'] as &$info) {
            $info = $this->_packLhcIssueList($info);
        }

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => output_format($issueList),
        ];
    }

    /**
     * 添加六合彩开奖
     *
     * @param Request $request
     * @return array
     */
    public function addLhcIssue(Request $request) {
        $params ['lottery_issue_no']           = $request->param('issueNo');
        $params ['lottery_id']                 = Config::get('six.LHC_LOTTERY_ID');
        $params ['lottery_issue_start_time']   = $request->param('issueStartTime');
        $params ['lottery_issue_end_time']     = $request->param('issueEndTime');
        $params ['lottery_issue_prize_num']    = $request->param('issuePrizeNum');
        $params ['lottery_issue_prize_status'] = $request->param('issuePrizeStatus');
        $params ['lottery_issue_status']       = $request->param('issueStatus');

        $lotteryLogic = Loader::model('LotteryIssue', 'logic');
        $result       = $lotteryLogic->addLhcIssue($params);

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 编辑六合彩开奖
     *
     * @param Request $request
     * @return array
     */
    public function editLhcIssue(Request $request) {
        $params ['lottery_issue_id']           = $request->param('id');
        $params ['lottery_issue_no']           = $request->param('issueNo');
        $params ['lottery_id']                 = Config::get('six.LHC_LOTTERY_ID');
        $params ['lottery_issue_start_time']   = $request->param('issueStartTime');
        $params ['lottery_issue_end_time']     = $request->param('issueEndTime');
        $params ['lottery_issue_prize_num']    = $request->param('issuePrizeNum');
        $params ['lottery_issue_prize_status'] = $request->param('issuePrizeStatus');
        $params ['lottery_issue_status']       = $request->param('issueStatus');

        $lotteryLogic = Loader::model('LotteryIssue', 'logic');
        $result       = $lotteryLogic->editLhcIssue($params);

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取六合彩玩法列表
     *
     * @return array
     */
    public function getLhcTypeList() {
        $lotteryLogic = Loader::model('LhcType', 'logic');
        $typeList     = $lotteryLogic->getTypeList();

        foreach($typeList as &$info) {
            $info = $this->_packLhcTypeList($info);
        }

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => output_format($typeList),
        ];
    }

    /**
     * 获取六合彩赔率列表
     *
     * @param Request $request
     * @return array
     */
    public function getLhcOddsList(Request $request) {
        $params ['lhc_type_id'] = $request->param('id');
        $params ['lottery_id'] = $request->param('lotteryId',20);

        $lotteryLogic = Loader::model('LhcOdds', 'logic');
        $issueList    = $lotteryLogic->getOddsList($params);

        foreach($issueList as &$info) {
            $info = $this->_packLhcOddsList($info);
        }

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => output_format($issueList),
        ];
    }

    /**
     * 编辑六合彩赔率
     *
     * @param Request $request
     * @return array
     */
    public function editLhcOdds(Request $request) {
        $params ['configIds'] = $request->param('configIds/a');
        $params ['lottery_id'] = $request->param('lotteryId',20);

        $lotteryLogic = Loader::model('LhcOdds', 'logic');
        $result       = $lotteryLogic->editLhcOdds($params);

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取六合彩玩法列表
     *
     * @return array
     */
    public function getLhcTypeConfigList() {
        // 类型
        $lotteryTypeLogic = Loader::model('LhcType', 'logic');
        $typeList         = $lotteryTypeLogic->getTypeList();

        foreach($typeList as &$info) {
            $info = $this->_packLhcTypeConfigList($info);
        }

        // 配置
        $lotteryConfigLogic = Loader::model('LhcConfig', 'logic');
        $configList         = $lotteryConfigLogic->getConfigList();

        foreach($configList as &$info) {
            $info = $this->_packLhcConfigList($info);
        }

        $resultList = [
            'typeList'   => $typeList,
            'configList' => $configList,
        ];

        return [
            'errorcode' => $lotteryTypeLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryTypeLogic->errorcode],
            'data'      => output_format($resultList),
        ];
    }

    /**
     * 编辑六合彩配置
     *
     * @param Request $request
     * @return array
     */
    public function editLhcTypeConfig(Request $request) {
        $params ['configIds'] = $request->param('configIds/a');
        $params ['typeIds']   = $request->param('typeIds/a');

        $lotteryLogic = Loader::model('LhcConfig', 'logic');
        $result       = $lotteryLogic->editLhcTypeConfig($params);

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => $result,
        ];
    }

    /**
     * 获取pc蛋蛋配置
     *
     * @param Request $request
     * @return array
     */
    public function getPc28Config() {
        $lotteryLogic = Loader::model('Lottery', 'logic');
        $configList   = $lotteryLogic->getPc28Config();

        foreach($configList as &$info) {
            $info = $this->_packPc28Config($info);
        }

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => output_format($configList),
        ];
    }

    /**
     * 编辑PC28赔率
     *
     * @param Request $request
     * @return array
     */
    public function editPc28Config(Request $request) {
        $params ['configIds'] = $request->param('configIds/a');

        $lotteryLogic = Loader::model('Lottery', 'logic');
        $result       = $lotteryLogic->editPc28Config($params);

        return [
            'errorcode' => $lotteryLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryLogic->errorcode],
            'data'      => $result,
        ];
    }

    private function _packLotteryCategoryInfo($info) {
        return [
            'id'                => $info ['lottery_category_id'],
            'categoryName'      => $info ['lottery_category_name'],
            'categoryModel'     => $info ['lottery_category_model'],
            'categorMmaxRebate' => $info ['lottery_category_max_rebate'],
            'defaultRebate'     => $info ['lottery_default_rebate'],
            'categorySort'      => $info ['lottery_category_sort'],
        ];
    }

    private function _packCategoryDisplayInfo($info) {
        return [
            'id'           => $info ['category_display_id'],
            'name'         => $info ['category_display_name'],
            'image'        => $info ['category_display_image'],
            'sort'         => $info ['category_display_sort'],
            'hot'          => $info ['category_display_hot'],
            'introduction' => $info['category_display_introduction'],
        ];
    }

    private function _packGameInfo($info) {
        return [
            'id'             => $info ['lottery_id'],
            'name'           => $info ['lottery_name'],
            'categoryId'     => $info ['lottery_category_id'],
            'categoryName'   => $info ['lottery_category_name'],
            'displayId'      => $info ['category_display_id'],
            'displayName'    => $info ['category_display_name'],
            'introduction'   => $info ['lottery_introduction'],
            'description'    => $info ['lottery_description'],
            'messageExplain' => $info ['lottery_message_explain'],
            'image'          => $info ['lottery_image_url'],
            'hot'            => $info ['lottery_is_hot'],
            'status'         => $info ['lottery_status'],
            'sort'           => $info ['lottery_sort'],
        ];
    }

    private function _packPlayInfo($info) {
        return [
            'id'               => $info ['play_id'],
            'categoryName'     => $info ['lottery_category_name'],
            'typeName'         => $info ['lottery_type_name'],
            'groupName'        => $info ['play_group_name'],
            'playName'         => $info ['play_name'],
            'minOdds'          => $info ['play_min_odds'],
            'maxOdds'          => $info ['play_max_odds'],
            'stakeBetMinMoney' => $info ['play_stake_bet_min_money'],
            'stakeBetMaxMoney' => $info ['play_stake_bet_max_money'],
            'itemBetMaxMoney'  => $info ['play_item_bet_max_money'],
            'help'             => $info ['play_help'],
            'example'          => $info ['play_example'],
            'tips'             => $info ['play_tips'],
        ];
    }

    private function _packTypeConfigInfo($info) {
        return [
            'typeId'      => $info ['lottery_type_id'],
            'categoryId'  => $info ['lottery_category_id'],
            'typeName'    => $info ['lottery_type_name'],
            'lotteryId'   => $info ['lottery_id'],
            'lotteryName' => $info ['lottery_name'],
            'checked'     => $info ['checked'],
            'typeSort'    => $info ['lottery_type_sort'],
            'typeDefault' => $info ['lottery_type_default'],
        ];
    }

    private function _packPlayConfigInfo($info) {
        return [
            'id'                => $info ['play_id'],
            'groupName'         => $info ['play_group_name'],
            'playName'          => $info ['play_name'],
            'categoryMaxRebate' => $info ['lottery_category_max_rebate'],
            'minOdds'           => $info ['play_min_odds'],
            'maxOdds'           => $info ['play_max_odds'],
            'stakeBetMinMoney'  => $info ['play_stake_bet_min_money'],
            'stakeBetMaxMoney'  => $info ['play_stake_bet_max_money'],
            'itemBetMaxMoney'   => $info ['play_item_bet_max_money'],
        ];
    }

    private function _packOrderList($info) {
        return [
            'id'           => $info ['order_id'],
            'orderNo'      => $info ['order_no'],
            'username'     => $info ['user_name'],
            'lotteryName'  => $info ['lottery_name'],
            'issueNo'      => $info ['issue_no'],
            'typeName'     => $info ['lottery_type_name'],
            'betAmount'    => $info ['order_bet_amount'],
            'rebateAmount' => $info ['order_rebate_amount'],
            'betRebate'    => $info ['order_bet_rebate'],
            'winningBonus' => $info ['order_winning_bonus'],
            'realAmount'   => $info ['order_winning_bonus'] - $info ['order_bet_amount'],
            'followId'     => $info ['follow_id'],
            'winStop'     => $info ['win_stop'],
            'betContent'   => $info ['order_bet_content'],
            'createtime'   => $info ['order_createtime'],
            'status'       => Config::get('status.lottey_order_status_name') [$info ['order_status']],
        ];
    }

    private function _packOrderInfo($info) {
        return [
            'id'           => $info ['order_id'],
            'orderNo'      => $info ['order_no'],
            'issueNo'      => $info ['issue_no'],
            'lotteryName'  => $info ['lottery_name'],
            'typeName'     => $info ['lottery_type_name'],
            'betOdds'      => $info ['order_bet_odds'],
            'stakeCount'   => $info ['order_stake_count'],
            'stakePrice'   => $info ['order_stake_price'],
            'betAmount'    => $info ['order_bet_amount'],
            'betRebate'    => $info ['order_bet_rebate'],
            'rebateAmount' => $info ['order_rebate_amount'],
            'winningBonus' => $info ['order_winning_bonus'],
            'prizeTime'    => $info ['issueInfo'] ['lottery_issue_prize_time'],
            'betContent'   => $info ['order_bet_content'],
            'prizeNum'     => $info ['issueInfo'] ['lottery_issue_prize_num'],
            'createtime'   => $info ['order_createtime'],
        ];
    }

    private function _packIssueList($info) {
        return [
            'id'          => $info ['lottery_issue_id'],
            'issueNo'     => $info ['lottery_issue_no'],
            'lotteryId'   => $info ['lottery_id'],
            'lotteryName' => $info ['lottery_name'],
            'endTime'     => $info ['lottery_issue_end_time'],
            'nowTime'     => current_datetime(),
            'prizeTime'   => $info ['lottery_issue_prize_time'],
            'prizeNum'    => $info ['lottery_issue_prize_num'],
            'betAmount'   => $info ['lottery_issue_bet_amount'],
            'bonus'       => $info ['lottery_issue_bonus'],
            'realAmount'  => $info ['lottery_issue_bet_amount'] - $info ['lottery_issue_bonus'],
            'status'      => $info ['lottery_issue_prize_status'],
        ];
    }

    private function _packLhcIssueList($info) {
        return [
            'id'          => $info ['lottery_issue_id'],
            'issueNo'     => $info ['lottery_issue_no'],
            'lotteryId'   => $info ['lottery_id'],
            'lotteryName' => $info ['lottery_name'],
            'startTime'   => $info ['lottery_issue_start_time'],
            'endTime'     => $info ['lottery_issue_end_time'],
            'nowTime'     => current_datetime(),
            'prizeNum'    => $info ['lottery_issue_prize_num'],
            'betAmount'   => $info ['lottery_issue_bet_amount'],
            'bonus'       => $info ['lottery_issue_bonus'],
            'realAmount'  => $info ['lottery_issue_bet_amount'] - $info ['lottery_issue_bonus'],
            'prizeStatus' => $info ['lottery_issue_prize_status'],
            'status'      => $info ['lottery_issue_status'],
        ];
    }

    private function _packLhcTypeList($info) {
        return [
            'id'   => $info ['lhc_type_id'],
            'name' => $info ['lhc_type_name'],
        ];
    }

    private function _packLhcOddsList($info) {
        return [
            'id'     => $info ['lhc_odds_id'],
            'labelx' => $info ['lhc_odds_labelx'],
            'labely' => $info ['lhc_odds_labely'],
            'value'  => $info ['lhc_odds_value'],
        ];
    }

    private function _packLhcTypeConfigList($info) {
        return [
            'id'               => $info ['lhc_type_id'],
            'name'             => $info ['lhc_type_name'],
            'stakeBetMinMoney' => $info ['play_stake_bet_min_money'],
            'stakeBetMaxMoney' => $info ['play_stake_bet_max_money'],
            'itemBetMaxMoney'  => $info ['play_item_bet_max_money'],
            'status'           => $info ['lhc_type_status'],
        ];
    }

    private function _packLhcConfigList($info) {
        return [
            'id'    => $info ['lhc_config_id'],
            'name'  => $info ['lhc_config_name'],
            'value' => $info ['lhc_config_value'],
        ];
    }

    private function _packPc28Config($info) {
        return [
            'id'        => $info ['play_id'],
            'groupName' => $info ['play_group_name'],
            'name'      => $info ['play_name'],
            'odds'      => $info ['play_min_odds'],
        ];
    }


    /**
     * 手动开奖接口
     * @param Request $request
     * @return array
     */
    public function handlePrize(Request $request) {
        $lotteryId   = $request->param('lotteryId');
        $issueNo     = $request->param('issueNo');
        $prizeNumber = $request->param('prizeNumber');
        $apiUrl      = \think\Env::get('app.digital_api_url');
        $params = [
            'act'        => DIGITAL_HANDLE_PRIZE_ACTION,
            'lottery_id' => $lotteryId,
            'issue_no'   => $issueNo,
            'prize_num'  => $prizeNumber,
        ];

        $lotteryCenterLotteryId = Config::get('lottery.collect_lottery_id_map')[$lotteryId];
        $response =  \app\admin\service\LotteryHalper::checkPrizeNumberNew($lotteryCenterLotteryId, $prizeNumber);

        $ret = json_decode($response, true);

        if ($ret['errorcode'] != 200 ) {
            Log::write("checkPrizeNumberResponse:" . print_r($response, true) . "requestData:" . print_r([$lotteryCenterLotteryId, $prizeNumber], true) );
            return show_response(EC_AD_PRIZE_NUMBER_FORMAT_API_ERROR, Config::get('errorcode') [EC_AD_PRIZE_NUMBER_FORMAT_API_ERROR]);
        }

        if ($ret['data']['status'] !== 1) {
            return show_response(EC_AD_PRIZE_NUMBER_FORMAT_ERROR, Config::get('errorcode') [EC_AD_PRIZE_NUMBER_FORMAT_ERROR]);
        }

        $lotteryIssueLogic = Loader::model('lotteryIssue', 'logic');
        $lotteryIssueInfo        = $lotteryIssueLogic->getIssueDetail($lotteryId, $issueNo);
        $endTime = $lotteryIssueInfo['lottery_issue_end_time'];
        
        if(strtotime($endTime) > time()){
            return show_response(EC_AD_INIT_LOTTERY_ISSUE_UNFINISHED, Config::get('errorcode') [EC_AD_INIT_LOTTERY_ISSUE_UNFINISHED]);
        }

        if(!empty($lotteryIssueInfo['lottery_issue_prize_num']) && ( $prizeNumber !=$lotteryIssueInfo['lottery_issue_prize_num'] )  )
        {
            return show_response(EC_AD_INIT_LOTTERY_ISSUE_OPENED, Config::get('errorcode') [EC_AD_INIT_LOTTERY_ISSUE_OPENED]);
        }

        $updateResult = Loader::model('LotteryIssue')->updatePrizeNumber($lotteryId, $issueNo, $prizeNumber);
        if($updateResult== false){
            return show_response(EC_DATABASE_ERROR, Config::get('errorcode') [EC_DATABASE_ERROR]);
        }

        $sign = generate_digital_sign($params);
        $data = json_encode($params);
        $header = [
            'Content-Type: text/json',
            "Content-length: " . strlen($data),
            "Authorization: " . $sign,
        ];

        $curlRequest = new Curlrequest();
        $result      = $curlRequest->curlJsonPost($apiUrl, $data, $header);
        $result      = json_decode($result, true);
        if($result == false) {
            Log::write('api_url:'.$apiUrl);
            Log::write('request_data:'.print_r($data, true));
            return show_response(EC_AD_HANDLE_PRIZE_FAIL, Config::get('errorcode') [EC_AD_HANDLE_PRIZE_FAIL]);
        } else if($result['status'] == 0) {
            return show_response(EC_SUCCESS, Config::get('errorcode') [EC_SUCCESS]);
        } else {
            return show_response(EC_AD_HANDLE_PRIZE_FAIL, $result['message']);
        }
    }

    public function getPrizeNum(Request $request)
    {
        $lotteryId = $request->param('lotteryId');
        $issueNo   = $request->param('issueNo');

        //查看是否已开奖
        $lotteryIssueLogic = Loader::model('lotteryIssue', 'logic');
        $openInfo = $lotteryIssueLogic->getIssueDetail($lotteryId, $issueNo);
        $prizeNum = $openInfo['lottery_issue_prize_num'];
        if ( !empty($prizeNum) ) {
            $info['prizeTime'] = $openInfo['lottery_issue_prize_time'];
            $info['prizeNumber'] = $openInfo['lottery_issue_prize_num'];

            return show_response(EC_SUCCESS, Config::get('errorcode') [EC_SUCCESS], $info);
        }

        $apiUrl = 'http://digital.lotterycenter.kosun.cc/api/Index/getIssueInfo';
        //外网
        // $apiUrl = 'http://e-api.kosun.cc/api/Index/getIssueInfo';

        $collectLotteryId = Config::get('lottery.collect_lottery_id_map')[$lotteryId];

        $requestData = array(
            'lottery_id' => $collectLotteryId,
            'lottery_issue' => $issueNo,
        );

        $curlRequest = new Curlrequest();
        $response      = $curlRequest->post($apiUrl, $requestData);

        $ret = json_decode($response, true);

        if ($ret['errorcode'] != 200 ) {
            Log::write("getIssueInfo:" . print_r($response, true) );
            Log::write("requestData:" . print_r($requestData, true) );
            return show_response(EC_AD_GET_PRIZE_NUMBER_FAIL, Config::get('errorcode') [EC_AD_GET_PRIZE_NUMBER_FAIL]);
        }

        $openInfo = $ret['data'];

        $info['prizeTime'] = $openInfo['prize_time'];
        $info['prizeNumber'] = $openInfo['prize_num'];

        return show_response(EC_SUCCESS, Config::get('errorcode') [EC_SUCCESS], $info);

    }

    /**
     * 获取每期注单详情
     *
     * @param Request $request
     * @return array
     */
    public function reportByType(Request $request) {

        $params ['page'] = $request->param('page') ? $request->param('page') : 1;
        $params ['num']  = $request->param('num') ? $request->param('num') : 100;

        if($request->param('issueNo') != '') {
            $params ['issue_no'] = $request->param('issueNo');
        }
        if($request->param('lotteryId') != '') {
            $params ['lottery_id'] = $request->param('lotteryId');
        }


        $lotterOrderLogic = Loader::model('LotteryOrder', 'logic');
        $orderList        = $lotterOrderLogic->reportByType($params);

        foreach($orderList['list'] as &$info) {
            $info = $this->_packreportByType($info);
        }

        return [
            'errorcode' => $lotterOrderLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotterOrderLogic->errorcode],
            'data'      => output_format($orderList),
        ];
    }

    private function _packreportByType($info) {
        return [
            'orderId'     => $info ['order_id'],
            'lotteryName'  => $info ['lottery_name'],
            'issueNo'      => $info ['issue_no'],
            'typeName'     => $info ['type_name'],
            'betAmount'    => $info ['order_bet_amount'],
            'rebateAmount' => $info ['order_rebate_amount'],
            'betRebate'    => $info ['order_bet_rebate'],
            'winningBonus' => $info ['order_winning_bonus'],
            'realAmount'   => $info ['order_bet_amount'] - $info ['order_winning_bonus'] - $info ['order_rebate_amount'],
        ];
    }

    public function cancelOrder(Request $request) {
        $orderId = $request->param('orderId');

        $lotteryOrderLogic = Loader::model('LotteryOrder', 'logic');

        $lotteryOrderLogic->cancelOrder($orderId);

        return [
            'errorcode' => $lotteryOrderLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryOrderLogic->errorcode],
        ];
    }


    public function cancelNoPrizeOrders(Request $request){

        $lotteryId       = $request->param('lotteryId');
        $issueNo         = $request->param('issueNo');
        $currentCount    = $request->param('currentCount');
        $totalCount      = $request->param('totalCount');
        $count           = $request->param('count');

        $lotteryIssueLogic = Loader::model('LotteryIssue', 'logic');

        $result = $lotteryIssueLogic->cancelNoPrizeOrders($lotteryId,$issueNo,$currentCount,$totalCount,$count);

        return [
            'errorcode' => $lotteryIssueLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryIssueLogic->errorcode],
            'data'      => $result!=false?$result:[]
        ];
    }


    public function refreshOrder(Request $request){
        $orderId = $request->param('orderId');

        $lotteryOrderLogic = Loader::model('LotteryOrder', 'logic');

        $lotteryOrderLogic->refreshOrder($orderId);

        return [
            'errorcode' => $lotteryOrderLogic->errorcode,
            'message'   => Config::get('errorcode') [$lotteryOrderLogic->errorcode]
        ];

    }


}
