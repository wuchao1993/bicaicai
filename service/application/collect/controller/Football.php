<?php
/**
 * 足球采集
 * @createTime 2017/8/18 10:05
 */

namespace app\collect\controller;

use think\Loader;
use think\Request;

class Football {

    public function today() {
        Loader::model('Today', 'football')->collect();
    }

    public function early() {
        Loader::model('Early', 'football')->collect();
    }

    public function inPlayNow() {
        return Loader::model('InPlayNow', 'football')->collect();
    }

    public function outright() {
        Loader::model('Outright', 'football')->collect();
    }

    public function results(Request $request) {
        $date = $request->param('date');
        Loader::model('Results', 'football')->collect($date);
    }

    public function repairResults() {
        Loader::model('Results', 'football')->repair();
    }

    public function outrightResults(Request $request) {
        $date = $request->param('date');
        Loader::model('OutrightResults', 'football')->collect($date);
    }

    public function repairOutrightResults() {
        Loader::model('OutrightResults', 'football')->repair();
    }
}