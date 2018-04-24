<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;
use app\admin\model\CoinsModel;

class CoinsController extends AdminBaseController
{


    public function index()
    {

        return $this->fetch();
    }


    public function add()
    {
        if ($this->request->isPost()) {
            $data           = $this->request->param();
            $data['add_time'] = time();
            $coinsModel = new CoinsModel();
            $result         = $coinsModel->validate(true)->save($data);
            if ($result === false) {
                $this->error($coinsModel->getError());
            }
            $this->success("添加成功！", url("slide/index"));
        }else{
            return $this->fetch();            
        }
    }
}
