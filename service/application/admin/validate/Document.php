<?php


namespace app\admin\validate;

use app\admin\model\Config;
use think\Validate;

class Document extends  Validate
{
    /**
     * 规则定义
     * @var array
     */
    protected $rule = [
        'title'         => 'chsDash|require',
        'content'       => 'require',
        'description'   => 'require|chsDash',
        'image'         => 'require',
        'sort'          => 'require|number|between:0,100',
        'status'        => 'require|number|in:1,2',
        'id'            => 'require|number',
        'type'          => 'in:1',
    ];

    /**
     * 提示信息定义
     * @var array
     */
    protected $message = [
        'title.require'         => '标题不能为空',
        'content.require'       => '内容不能为空',
        'description.require'   => '描述不能为空',
        'image.require'         => '图片地址不能为空',
        'sort.require'          => '排序不能为空',
        'title.chsDash'         => '标题只能是汉字、字母、数字和下划线_及破折号-',
        'description.chsDash'   => '描述只能是汉字、字母、数字和下划线_及破折号-',
        'sort.number'           => '排序必须是数字',
        'sort.between'          => '排序不在取值范围',
        'status.require'        => '状态不能为空',
        'status.number'         => '状态请求必须是数字',
        'status.in'             => '状态请求不在1,2之间范围',
        'id.require'            => 'id请求不能为空',
        'id.number'             => 'id请求必须是数字',
        'type.in'               => '文档类型取值只能是1',
    ];

    /**
     * 场景定义
     * @var array
     */
    public $scene = [
        'addDocument'                  => ['title','description','sort','image','content'],
        'editDocument'                 => ['id','status','title','description','sort','image','content','type'],
        'deleteDocument'               => ['id'],
        'getDocument'                  => ['type'],
        'getDocumentById'              => ['id'],

    ];



}