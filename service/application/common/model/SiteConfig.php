<?php
namespace app\common\model;

use think\Env;
use think\Model;
use think\Config;

class SiteConfig extends Model {
    /**
     * 定义主键
     * @var string
     */
    public $pk = 'sc_id';

    /**
     * 读取配置
     * @param $lotteryType  彩票类型，digital, sports
     * @param $group  分组
     * @param string || array $name  配置名称
     * @return bool|array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getConfig($lotteryType, $group, $name = '') {
        //读取配置
        $where = [
            'sc_lottery_type' => Config::get('status.site_config_lottery_type')[$lotteryType],
            'sc_group'        => Config::get('status.site_config_group')[$group],
            'sc_status'       => Config::get('status.site_config_status')['enable'],
        ];
        if (is_array($name)) {
            $where['sc_name'] = ['IN', $name];
        } elseif(!empty($name)) {
            $where['sc_name'] = $name;
        }
        $result = $this->where($where)->field('sc_name,sc_type,sc_value')->select();

        $config = [];
        if ($result) {
            foreach($result as $item) {
                if (in_array($item->sc_type, [
                    Config::get('status.site_config_type')['array'],
                    Config::get('status.site_config_type')['enumeration'],
                ])) {
                    $config[$item['sc_name']] = explode(',', $item['sc_value']);
                } elseif($item->sc_type == Config::get('status.site_config_type')['json']) {
                    $config[$item['sc_name']] = json_decode($item['sc_value'], true);
                } else {
                    $config[$item['sc_name']] = $item['sc_value'];
                }
            }
        }

        //返回系统环境配置
        $config['site_name'] = Env::get('app.site_name');
        $config['passport_url'] = Env::get('passport.external_url');

        return $config;
    }
}