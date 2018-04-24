<?php
/**
 * 公告业务逻辑
 * @createTime 2017/4/3 16:14
 */

namespace app\api\logic;

use think\Config;
use think\Loader;
use think\Model;

class Notice extends Model {
    /**
     * 错误代码变量
     * @var
     */
    public $errorcode = EC_SUCCESS;

    /**
     * 每页显示数量
     * @var int
     */
    public $pageSize = 30;

    /**
     * 获取大厅首页公告列表
     * @return array
     */
    public function getHomeNotice() {
        $where = [
            'notice_status'       => Config::get('status.notice_status')['yes'],
            'notice_marquee'      => Config::get('status.notice_marquee')['yes'],
            'notice_type'         => Config::get('status.notice_type')['new'],
            'notice_lottery_type' => Config::get('status.notice_lottery_type')['sports'],
            'notice_createtime'   => ['<=', date('Y-m-d, H:i:s')],
        ];
        
        $orderBy = ['notice_sort' => 'desc', 'notice_createtime' => 'desc'];
        $list = Loader::model('Notice')
            ->where($where)
            ->field('notice_id,notice_type AS type_id,notice_title,notice_content,notice_createtime')
            ->order($orderBy)->select();

        return $list ?: [];
    }

    /**
     * 返回公告类型
     * @return mixed
     */
    public function getNoticeTypes() {
        $list = Config::get('status.sports_notice_type_list');
        $iconPath = Config::get('oss_sports_url') . '/wlxe/upload/sportsNotice/';
        foreach($list as $key => $item) {
            $list[$key]['type_icon'] = $iconPath . 'sports_notice_' . $item['type_id'] . '.png';
        }
        return $list;
    }

    /**
     * 根据公告类型获取公告列表
     * @param $typeId
     * @param $page
     * @return array
     */
    public function getListByTypeId($typeId, $page) {
        empty($page) && $page = 1;
        $where = [
            'notice_status' => Config::get('status.notice_status')['yes'],
            'notice_type' => $typeId,
            'notice_lottery_type' => Config::get('status.notice_lottery_type')['sports'],
            'notice_createtime' => ['<=', date('Y-m-d, H:i:s')],
        ];

        //计算总数
        $total = Loader::model('Notice')->where($where)->count();
        if (!$total) {
            return ['total_page' => 0, 'result' => []];
        }

        $field = 'notice_id AS id,notice_title AS title,notice_content AS content,notice_createtime AS time';
        $orderBy = ['notice_sort' => 'desc', 'notice_createtime' => 'desc'];
        $list = Loader::model('Notice')
            ->where($where)
            ->field($field)
            ->order($orderBy)->select();
        if (!$list) {
            return ['total_page' => 0, 'result' => []];
        }
        return ['total_page' => ceil($total / $this->pageSize), 'result' => $list];
    }
}