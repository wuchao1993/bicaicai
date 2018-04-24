<?php
/**
 * 网球采集
 * @createTime 2017/9/26 10:05
 */

namespace app\collect\controller;

use think\Loader;
use think\Request;

class Tennis {

    public function today() {
        Loader::model('Today', 'tennis')->collect();
    }

    public function early() {
        Loader::model('Early', 'tennis')->collect();
    }

    public function inPlayNow() {
        return Loader::model('InPlayNow', 'tennis')->collect();
    }

    public function outright() {
        Loader::model('Outright', 'tennis')->collect();
    }

    public function results(Request $request) {
        $date = $request->param('date');
        Loader::model('Results', 'tennis')->collect($date);
    }

    public function repairResults() {
        Loader::model('Results', 'tennis')->repair();
    }

    public function outrightResults(Request $request) {
        $date = $request->param('date');
        Loader::model('OutrightResults', 'tennis')->collect($date);
    }

    public function repairOutrightResults() {
        Loader::model('OutrightResults', 'tennis')->repair();
    }
}