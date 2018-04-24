<?php
/**
 * 公共业务逻辑模型类
 * @author jesse.lin.989@gmail.com
 */
namespace app\admin\logic;
use think\Loader;
use think\Model;
class Common extends Model
{
    /**
     * 错误变量
     */
    public $errorCode = EC_AD_SUCCESS;

    /**
     * 查询条件
     */
    public $condition = [];

    /**
     * 排序
     */
    public $orderBy = '';

    /**
     * 接口字段映射配置
     * @var array $fieldMapConf = [
     *      '表字段名' => ['api字段名','回调方法名']
     * ]
     */
    Public $fieldMapConf = [];

    /**
     * 获取模型
     * @param $modelName
     * @return Object
     */
    public function getModel($modelName = '')
    {
        if(empty($modelName)){
            $modelName = CONTROLLER_NAME;
        }

        return Loader::model ( $modelName);
    }

    /**
     * 设置查询条件
     */
    public function setCondition(&$params){}

    /**
     * 获取查询条件
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * 设置排序
     */
    public function setOrder(){}

    /**
     * 获取排序
     */
    public function getOrder()
    {
        return $this->orderBy;
    }

    /**
     * 初始化处理
     * @access protected
     * @return void
     */
    protected static function init()
    {
    }

    /**
     * 查询响应数据，字段映射
     * @param $data = ['表字段'=>"值"]
     * @return array
     */
    public function fieldMap($data)
    {

        if(!empty($this->fieldMapConf)){

            $api_data = [];
            foreach ($this->fieldMapConf as $field => $conf){
                if(isset($conf[1]) && method_exists($this,$conf[1])){
                    $api_data[$conf[0]] = call_user_func([$this,$conf[1]],$data[$field]);
                }else{
                    $api_data[$conf[0]] = $data[$field];
                }
            }

            return $api_data;
        }

        return $data;
    }

    /**
     * 请求数据，字段映射
     * @param $params
     * @return array
     */
    public function toTableField($params)
    {
        $data = [];
        if($this->fieldMapConf){
            foreach ($this->fieldMapConf as $field=>$conf){
                if($params[$conf[0]]){
                    $data[$field] = $params[$conf[0]];
                }
            }
        }else{
            $data = $params;
        }

        return $data;
    }

    /**
     * 获取列表
     * @param $params
     * @return array
     */
    public function getList($params)
    {
        $this->setCondition($params);
        $condition = $this->getCondition();

        $count = $this->where ( $condition )->count ();

        $this->setOrder();
        if(isset($params ['num']) && isset($params ['page'])){
            $list = $this->where ( $condition )->order ( $this->getOrder() )->limit ( $params ['num'] )->page ( $params ['page'] )->select ();
        }

        if(!empty($list)){
            foreach ($list as $key=>$info){
                $list[$key] = $this->fieldMap($info);
            }
        }

        $returnArr = array (
            'totalCount' => $count,
            'list' => $list
        );

        return $returnArr;
    }

    /**
     * 获取详情
     * @param $id
     * @return array
     */
    public function getInfo($id)
    {
        $pk    = $this->getPk();
        $condition = [$pk => $id];

        $info = $this->where ( $condition )->find ()->toArray ();

        if(!empty($info)){
            $info = $this->fieldMap($info);
        }

        return $info;
    }


    /**
     * 新增
     * @param $params
     * @return bool/array
     */
    public function add($params)
    {
        $data   = $this->toTableField($params);
        $ret    = $this->save ( $data );

        if ($ret) {
            return ['id' => $this->rtg_id];
        }

        $this->errorCode = EC_AD_ADD_ERROR;
        return false;
    }

    /**
     * 编辑
     * @param $params
     * @return bool
     */
    public function edit($params)
    {
        $data  = $this->toTableField($params);
        $pk    = $this->getPk();
        $ret   = $this->save ( $data ,[$pk=>$params['id']]);

        return true;
    }

    /**
     * 删除
     * @param $id
     * @return bool
     */
    public function del($id)
    {
        $pk     = $this->getPk();
        $ret    = $this->where ( [$pk => $id])->delete ();

        if(!$ret){
            $this->errorCode = EC_AD_DEL_ERROR;
            return false;
        }

        return true;
    }

    /**
     * 变更状态
     * @param $params
     * @return bool
     */
    public function changeStatus($params)
    {
        $pk     = $this->getPk();
        $data   = $this->toTableField(['status'=>$params['status']]);
        $ret    = $this->save($data, [$pk => $params['id']]);

        if(!$ret){
            $this->errorCode = EC_AD_CHANGE_ERROR;
            return false;
        }

        return true;
    }


    function __destruct()
    {

    }


}