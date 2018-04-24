<?php
namespace app\admin\logic;

class PayCenterBase{

    protected function _buildResponse($data){
        if(empty($data)){
            return [];
        }
        $list = $data['list'];
        foreach ($list as $key => $info){
            if(isset($info['tag'])){
                $list[$key]['ulId'] = explode(',', $info['tag']);
                unset($list[$key]['tag']);
            }
            unset($list[$key]['appId']);
        }
        $data['list'] = $list;

        return $data;
    }

}