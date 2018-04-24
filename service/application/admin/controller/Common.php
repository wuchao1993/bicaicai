<?php
/**
 * 公共控制器
 * @author jesse.lin.989@gmail.com
 */
namespace app\admin\controller;
use think\Request;
use think\Loader;
use think\Config;
class Common
{
    /**
     * @var \think\Request Request实例
     */
    protected $request;
    /**
     * 前置操作方法列表（按key先后顺序）
     * @var array $beforeActionList = [
     * 'init',
     * 'before_list'=>['only'=>'getList'],
     * 'before_add' =>['only'=>'add'],
     * ]
     */
    protected $beforeActionList = [];
    /**
     * 当前控制器业务模型
     * @var Object
     */
    protected $logic;
    /**
     * 请求参数
     * @var mixed
     */
    protected $param;

    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct(Request $request)
    {
        if (is_null($request)) {
            $request = Request::instance();
        }

        define('MODULE_NAME',$request->module());
        define('CONTROLLER_NAME',$request->controller());
        define('ACTION_NAME',$request->action());

        $this->request  = $request;
        $this->param    = $request->param();
        $this->logic    = $this->logic();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                    $this->beforeAction($options) :
                    $this->beforeAction($method, $options);
            }
        }else{
            if(method_exists($this,"before_".ACTION_NAME)){
                call_user_func([$this, "before_".ACTION_NAME]);
            }
        }
    }


    /**
     * 前置操作
     * @access protected
     * @param string $method  前置操作方法名
     * @param array  $options 调用参数 ['only'=>[...]] 或者['except'=>[...]]
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }
            if (!in_array(ACTION_NAME, $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }
            if (in_array(ACTION_NAME, $options['except'])) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 响应数据
     * @param $errorCode
     * @param $message
     * @param $data
     * @return array
     */
    protected function _response($errorCode,$data,$message = ''){

        if(is_object($data)||is_array($data))
            $data    = output_format ( $data );

        if(empty($message))
            $message = Config::get ( 'errorcode' ) [$errorCode];

        return [
            'errorcode' => $errorCode,
            'message'   => $message,
            'data'      => $data
        ];
    }

    /**
     * 获取业务层模型
     * @param $modelName
     * @return Object
     */
    protected function logic($modelName = ''){

        if(empty($modelName)){
            $modelName = CONTROLLER_NAME;
        }

        return Loader::model ( $modelName, 'logic' );
    }

    /**
     * 获取表层模型
     * @param $modelName
     * @return Object
     */
    protected function model($modelName = ''){

        if(empty($modelName)){
            $modelName = CONTROLLER_NAME;
        }

        return Loader::model ( $modelName, 'model' );
    }


    /**
     * 获取列表
     */
    public function getList()
    {
        $this->param['page'] = $this->request->param( 'page',1 );
        $this->param['num']  = $this->request->param( 'num',10 );

        $list = $this->logic->getList ( $this->param );

        return $this->_response($this->logic->errorCode, $list);
    }

    /**
     * 获取详情
     */
    public function getInfo()
    {
        $info = $this->logic->getInfo ($this->param['id']);

        return $this->_response($this->logic->errorCode, $info);
    }

    /**
     * 新增
     */
    public function add()
    {
        $info = $this->logic->add ($this->param);

        return $this->_response($this->logic->errorCode, $info);
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $result = $this->logic->edit ( $this->param );

        return $this->_response($this->logic->errorCode, $result);
    }

    /**
     * 删除
     */
    public function del()
    {
        $result = $this->logic->del ( $this->param['id'] );

        return $this->_response($this->logic->errorCode, $result);
    }

    /**
     * 变更状态
     */
    public function changeStatus()
    {
        $result    = $this->logic->changeStatus($this->param);

        return $this->_response($this->logic->errorCode, $result);
    }


}