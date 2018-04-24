<?php

/**
 * 广告相关业务逻辑
 * @author paulli
 */
namespace app\admin\logic;
use think\Loader;
use think\Model;
use think\Config;

class Advertising extends Model {
    
    /**
     * 错误变量
     *
     * @var
     *
     */
    public $errorcode = EC_AD_SUCCESS;

    /**
     * 获取广告列表信息
     * @param $params
     * @return array
     */
    public function getList($params) {
        if($params['status']) {
            $where['advertising_status'] = $params['status'];
        }else{
            $where['advertising_status'] = ['neq', Config::get('status.advertising_status')['deleted'] ];
        }
        if($params['siteType']) {
            $where['advertising_site_type'] = $params['siteType'];
        }
        if($params['terminal']) {
            $where['advertising_terminal'] = $params['terminal'];
        }
        if($params['name']) {
            $where['advertising_name'] = $params['name'];
        }
        $field = [
            'advertising_id'           => 'id',
            'advertising_name'         => 'name',
            'advertising_sketch_image' => 'sketchImage',
            'advertising_size'         => 'size',
            'advertising_remark'       => 'remark',
            'advertising_web_image'    => 'webImage',
            'advertising_link'         => 'link',
            'advertising_status'       => 'status',
            'advertising_site_type'    => 'siteType',
            'advertising_terminal'     => 'terminal',
            'advertising_identifier'   => 'identifier',
        ];
        $order = 'advertising_create_time desc';
        $advertisingData = Loader::model('Advertising')->field($field)
                                                       ->where($where)
                                                       ->page($params['page'])
                                                       ->limit($params['num'])->order($order)
                                                       ->select();

        $totalCount = Loader::model('Advertising')->where($where)->count();
        return ['list' => $advertisingData , 'totalCount' => $totalCount ];
    }

    /**
     * 添加广告信息
     * @param $params
     * @return bool
     */
    public function  addAdvertising($params) {
        $checkFlag = $this->_checkAdvertisingName($params['name']);
        if($checkFlag) {
            return false ;
        }
        $checkFlag = $this->_checkAdvertisingIdentifier($params['identifier']);
        if($checkFlag) {
            return false ;
        }
        $insertData['advertising_name']         = $params['name'];
        $insertData['advertising_sketch_image'] = urldecode($params['sketchImage']);
        $insertData['advertising_size']         = $params['size'];
        $insertData['advertising_remark']       = $params['remark'];
        $insertData['advertising_web_image']    = urldecode($params['webImage']);
        $insertData['advertising_link']         = $params['link'];
        $insertData['advertising_status']       = $params['status'];
        $insertData['advertising_site_type']    = $params['siteType'];
        $insertData['advertising_terminal']     = $params['terminal'];
        $insertData['advertising_create_time']  = date('Y-m-d H:i:s');
        $insertData['advertising_modify_time']  = date('Y-m-d H:i:s');
        $insertData['advertising_identifier']   = $params['identifier'];
        $flag = Loader::model('Advertising')->save($insertData);
        if(!$flag) {
            $this->errorcode = EC_ADVERTISING_ADD_FAIL;
            return false;
        }else {
            return true;
        }
    }

    /**
     * 编辑广告示意图信息
     * @param $params
     * @return bool
     */
    public function editAdvertising($params) {
        $checkFlag = $this->_checkAdvertisingName($params['name'],$params['id']);
        if($checkFlag) {
            return false ;
        }
        $checkFlag = $this->_checkAdvertisingIdentifier($params['identifier'],$params['id']);
        if($checkFlag) {
            return false ;
        }
        $updateData = [
            'advertising_name'         => $params['name'],
            'advertising_sketch_image' => urldecode($params['sketchImage']),
            'advertising_size'         => $params['size'],
            'advertising_remark'       => $params['remark'],
            'advertising_web_image'    => urldecode($params['webImage']),
            'advertising_link'         => $params['link'],
            'advertising_status'       => $params['status'],
            'advertising_site_type'    => $params['siteType'],
            'advertising_terminal'     => $params['terminal'],
            'advertising_modify_time'  => date('Y-m-d H:i:s'),
            'advertising_identifier'   => $params['identifier'],
        ];
        $where = [
            'advertising_id' => $params['id'],
        ];
        $flag = Loader::model('Advertising')->where($where)->update($updateData);
        return $flag;
    }

    /**
     * 获取广告 详情
     * @param $id
     * @return mixed
     */
    public function getAdvertisingInfo($id) {
        $advertisingData = Loader::model('Advertising')->getAdvertisingInfo($id);
        return $advertisingData;
    }

    /**
     * 删除功能
     * @param $id
     * @return mixed
     */
    public function deleteAdvertising($id) {
        $where['advertising_id'] = $id;
        $updateData = [
            'advertising_status'      => Config::get('status.advertising_status')['deleted'],
            'advertising_modify_time' => date('Y-m-d H:i:s'),
        ];
        $flag = Loader::model('Advertising')->where($where)->update($updateData);
        return $flag;
    }

    /**
     * 检查广告名称的唯一性
     * @param $name
     * @return bool
     */
    private function _checkAdvertisingName($name, $id = 0) {
        $where['advertising_name'] = $name;
        $advertisingData           = Loader::model('Advertising')->where($where)
                                                                 ->field('advertising_name,advertising_id')
                                                                 ->find();
        if(!$advertisingData) {
            return false;
        }
        if($id != $advertisingData['advertising_id']) {
            $this->errorcode = EC_ADVERTISING_NAME_EXISTS;
            return true;
        }
        if($id != 0  || $id = $advertisingData['advertising_id']) {
            return false;
        }
        if($advertisingData['advertising_name']) {
            $this->errorcode = EC_ADVERTISING_NAME_EXISTS;
            return true;
        }
    }

    private function _checkAdvertisingIdentifier($name, $id = 0) {
        $where['advertising_identifier'] = $name;
        $advertisingData           = Loader::model('Advertising')->where($where)
            ->field('advertising_identifier,advertising_id')
            ->find();
        if(!$advertisingData) {
            return false;
        }
        if($id != $advertisingData['advertising_id']) {
            $this->errorcode = EC_ADVERTISING_IDENTIFIER_EXISTS;
            return true;
        }
        if($id != 0  || $id = $advertisingData['advertising_id']) {
            return false;
        }
        if($advertisingData['advertising_identifier']) {
            $this->errorcode = EC_ADVERTISING_IDENTIFIER_EXISTS;
            return true;
        }
    }

}