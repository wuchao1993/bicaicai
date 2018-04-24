<?php
/**
 * 篮球采集
 * @createTime 2017/8/18 10:05
 */

namespace app\collect\controller;

use think\Loader;
use think\Request;

class Basketball {

    public function today() {
        Loader::model('Today', 'basketball')->collect();
    }

    public function early() {
        Loader::model('Early', 'basketball')->collect();
    }

    public function inPlayNow() {
        return Loader::model('InPlayNow', 'basketball')->collect();
    }

    public function outright() {
        Loader::model('Outright', 'basketball')->collect();
    }

    public function results(Request $request) {
        $date = $request->param('date');
        Loader::model('Results', 'basketball')->collect($date);
    }

    public function repairResults() {
        Loader::model('Results', 'basketball')->repair();
    }

    public function outrightResults(Request $request) {
        $date = $request->param('date');
        Loader::model('OutrightResults', 'basketball')->collect($date);
    }

    public function repairOutrightResults() {
        Loader::model('OutrightResults', 'basketball')->repair();
    }
}