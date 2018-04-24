<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Config;
use curl\Curlrequest;
use think\Log;
// 应用公共文件

/**
 * 递归数组，下划线转驼峰函数；eg: go_to_school => goToSchool
 * @param $data array 需要格式化的数据
 * @param $valFormat bool 是否需要把value也格式化
 * @return mixed
 */
function output_format($data, $valFormat = false)
{
    if (is_object($data)) {
        $data = $data->toArray();
    }
    if (!is_array($data) || empty($data)) {
        return [];
    }
    foreach ($data as $key => $val) {
        //下划线转驼峰
        $newKey = lcfirst(str_replace(' ', '', ucwords(str_replace(['_'], ' ', $key))));

        //对象转数组
        if (is_object($val)) {
            $val = $val->toArray();
        }

        //递归val
        if (is_array($val)) {
            $formatData[$newKey] = output_format($val, $valFormat);
        } else {
            //接口不返回null
            if ($val === null) {
                $val = '';
            }
            $formatData[$newKey] = $val;
        }
    }
    return $formatData;
}

/**
 * 递归数组，驼峰转下划线函数；eg: goToSchool => go_to_school
 * @param $data array 需要格式化的数据
 * @param $valFormat bool 是否需要把value也格式化
 * @return mixed
 */
function input_format($data, $valFormat = false)
{
    if (!is_array($data) || empty($data)) {
        return [];
    }
    foreach ($data as $key => $val) {
        $newKey = preg_replace('/\s+/u', '', $key);
        $newKey = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . '_', $newKey), 'UTF-8');
        if (is_array($val)) {
            $formatData[$newKey] = input_format($val, $valFormat);
        } else {
            if ($valFormat && is_string($val)) {
                $formatData[$newKey] = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . '_', $val), 'UTF-8');
            } else {
                $formatData[$newKey] = $val;
            }
        }
    }
    return $formatData;
}

/**
 * 封装返回值
 * @param $errorCode
 * @param $data
 * @return array
 */
function return_result($errorCode, $data = []) {
    return [
        'errorcode' => $errorCode,
        'message'   => \think\Config::get('errorcode')[$errorCode],
        'data'      => $data
    ];
}

/**
 * 转换时间
 * @param $timeString
 * @return string
 */
function transfer_time($timeString) {
    $pattern = '/[1-2]{1}H [0-9]{2}/';
    if (preg_match($pattern, $timeString)) {
        $strArr = explode(' ', $timeString);
        if ($strArr[0] == '1H') {
            $timeString = '上半场 ' . $strArr[1] . '\'';
        } elseif ($strArr[0] == '2H') {
            $timeString = '下半场 ' . $strArr[1] . '\'';
        }
        return $timeString;
    } elseif ($timeString == '半场') {
        return $timeString;
    }
    return '';
}

/**
 * 封装返回值
 * @param string $data
 * @throws HttpResponseException
 */
function response_exception($data = '') {
    $type = \think\Config::get('default_ajax_return');
    $response = \think\Response::create($data, $type);
    \think\Hook::listen('Cross', $response);
    throw new think\exception\HttpResponseException($response);
}

/**
 * 生成指定长度的随机字符串
 * @param int $len 长度
 * @param string $type
 * @return string
 */
function random_string($len = 8, $type = 'str')
{
    if ($type == 'str') {
        $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    } elseif ($type == 'int') {
        $chars = '0123456789';
    } elseif ($type == 'onlystr') {
        $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
    }
    $chars = str_shuffle($chars);
    $str = substr($chars, 0, $len);
    return $str;
}

/**
 * 重新指定数组的索引
 * @param array $arr 数组
 * @param string $key 键名
 * @return array
 */
function reindex_array($arr, $key)
{
    if (!is_array($arr) || empty($arr) ) {
        return array();
    }

    $row = array_shift($arr);
    if (!array_key_exists($key, $row)) {
        return array();
    }
    array_unshift($arr, $row);

    $res = array();
    foreach ($arr as $row) {
        $res[$row[$key]] = $row;
    }

    return $res;
}


/**
 * 提取数组某一个key的值
 * @param array $arr
 * @param string $extract_key
 * @return array
 */
function extract_array($arr, $extract_key)
{
    $res = array();
    if (is_array($arr)) {
        foreach ($arr as $row) {
            $res[] = $row[$extract_key];
        }
    }
    return $res;
}


function current_datetime()
{
    return date('Y-m-d H:i:s');
}

/***********************************将汉字转换为拼音 start*************************/

function Pinyin($_String, $_Code = 'gb2312')
{
    $_DataKey = "a|ai|an|ang|ao|ba|bai|ban|bang|bao|bei|ben|beng|bi|bian|biao|bie|bin|bing|bo|bu|ca|cai|can|cang|cao|ce|ceng|cha" .
        "|chai|chan|chang|chao|che|chen|cheng|chi|chong|chou|chu|chuai|chuan|chuang|chui|chun|chuo|ci|cong|cou|cu|" .
        "cuan|cui|cun|cuo|da|dai|dan|dang|dao|de|deng|di|dian|diao|die|ding|diu|dong|dou|du|duan|dui|dun|duo|e|en|er" .
        "|fa|fan|fang|fei|fen|feng|fo|fou|fu|ga|gai|gan|gang|gao|ge|gei|gen|geng|gong|gou|gu|gua|guai|guan|guang|gui" .
        "|gun|guo|ha|hai|han|hang|hao|he|hei|hen|heng|hong|hou|hu|hua|huai|huan|huang|hui|hun|huo|ji|jia|jian|jiang" .
        "|jiao|jie|jin|jing|jiong|jiu|ju|juan|jue|jun|ka|kai|kan|kang|kao|ke|ken|keng|kong|kou|ku|kua|kuai|kuan|kuang" .
        "|kui|kun|kuo|la|lai|lan|lang|lao|le|lei|leng|li|lia|lian|liang|liao|lie|lin|ling|liu|long|lou|lu|lv|luan|lue" .
        "|lun|luo|ma|mai|man|mang|mao|me|mei|men|meng|mi|mian|miao|mie|min|ming|miu|mo|mou|mu|na|nai|nan|nang|nao|ne" .
        "|nei|nen|neng|ni|nian|niang|niao|nie|nin|ning|niu|nong|nu|nv|nuan|nue|nuo|o|ou|pa|pai|pan|pang|pao|pei|pen" .
        "|peng|pi|pian|piao|pie|pin|ping|po|pu|qi|qia|qian|qiang|qiao|qie|qin|qing|qiong|qiu|qu|quan|que|qun|ran|rang" .
        "|rao|re|ren|reng|ri|rong|rou|ru|ruan|rui|run|ruo|sa|sai|san|sang|sao|se|sen|seng|sha|shai|shan|shang|shao|" .
        "she|shen|sheng|shi|shou|shu|shua|shuai|shuan|shuang|shui|shun|shuo|si|song|sou|su|suan|sui|sun|suo|ta|tai|" .
        "tan|tang|tao|te|teng|ti|tian|tiao|tie|ting|tong|tou|tu|tuan|tui|tun|tuo|wa|wai|wan|wang|wei|wen|weng|wo|wu" .
        "|xi|xia|xian|xiang|xiao|xie|xin|xing|xiong|xiu|xu|xuan|xue|xun|ya|yan|yang|yao|ye|yi|yin|ying|yo|yong|you" .
        "|yu|yuan|yue|yun|za|zai|zan|zang|zao|ze|zei|zen|zeng|zha|zhai|zhan|zhang|zhao|zhe|zhen|zheng|zhi|zhong|" .
        "zhou|zhu|zhua|zhuai|zhuan|zhuang|zhui|zhun|zhuo|zi|zong|zou|zu|zuan|zui|zun|zuo";
    $_DataValue = "-20319|-20317|-20304|-20295|-20292|-20283|-20265|-20257|-20242|-20230|-20051|-20036|-20032|-20026|-20002|-19990" .
        "|-19986|-19982|-19976|-19805|-19784|-19775|-19774|-19763|-19756|-19751|-19746|-19741|-19739|-19728|-19725" .
        "|-19715|-19540|-19531|-19525|-19515|-19500|-19484|-19479|-19467|-19289|-19288|-19281|-19275|-19270|-19263" .
        "|-19261|-19249|-19243|-19242|-19238|-19235|-19227|-19224|-19218|-19212|-19038|-19023|-19018|-19006|-19003" .
        "|-18996|-18977|-18961|-18952|-18783|-18774|-18773|-18763|-18756|-18741|-18735|-18731|-18722|-18710|-18697" .
        "|-18696|-18526|-18518|-18501|-18490|-18478|-18463|-18448|-18447|-18446|-18239|-18237|-18231|-18220|-18211" .
        "|-18201|-18184|-18183|-18181|-18012|-17997|-17988|-17970|-17964|-17961|-17950|-17947|-17931|-17928|-17922" .
        "|-17759|-17752|-17733|-17730|-17721|-17703|-17701|-17697|-17692|-17683|-17676|-17496|-17487|-17482|-17468" .
        "|-17454|-17433|-17427|-17417|-17202|-17185|-16983|-16970|-16942|-16915|-16733|-16708|-16706|-16689|-16664" .
        "|-16657|-16647|-16474|-16470|-16465|-16459|-16452|-16448|-16433|-16429|-16427|-16423|-16419|-16412|-16407" .
        "|-16403|-16401|-16393|-16220|-16216|-16212|-16205|-16202|-16187|-16180|-16171|-16169|-16158|-16155|-15959" .
        "|-15958|-15944|-15933|-15920|-15915|-15903|-15889|-15878|-15707|-15701|-15681|-15667|-15661|-15659|-15652" .
        "|-15640|-15631|-15625|-15454|-15448|-15436|-15435|-15419|-15416|-15408|-15394|-15385|-15377|-15375|-15369" .
        "|-15363|-15362|-15183|-15180|-15165|-15158|-15153|-15150|-15149|-15144|-15143|-15141|-15140|-15139|-15128" .
        "|-15121|-15119|-15117|-15110|-15109|-14941|-14937|-14933|-14930|-14929|-14928|-14926|-14922|-14921|-14914" .
        "|-14908|-14902|-14894|-14889|-14882|-14873|-14871|-14857|-14678|-14674|-14670|-14668|-14663|-14654|-14645" .
        "|-14630|-14594|-14429|-14407|-14399|-14384|-14379|-14368|-14355|-14353|-14345|-14170|-14159|-14151|-14149" .
        "|-14145|-14140|-14137|-14135|-14125|-14123|-14122|-14112|-14109|-14099|-14097|-14094|-14092|-14090|-14087" .
        "|-14083|-13917|-13914|-13910|-13907|-13906|-13905|-13896|-13894|-13878|-13870|-13859|-13847|-13831|-13658" .
        "|-13611|-13601|-13406|-13404|-13400|-13398|-13395|-13391|-13387|-13383|-13367|-13359|-13356|-13343|-13340" .
        "|-13329|-13326|-13318|-13147|-13138|-13120|-13107|-13096|-13095|-13091|-13076|-13068|-13063|-13060|-12888" .
        "|-12875|-12871|-12860|-12858|-12852|-12849|-12838|-12831|-12829|-12812|-12802|-12607|-12597|-12594|-12585" .
        "|-12556|-12359|-12346|-12320|-12300|-12120|-12099|-12089|-12074|-12067|-12058|-12039|-11867|-11861|-11847" .
        "|-11831|-11798|-11781|-11604|-11589|-11536|-11358|-11340|-11339|-11324|-11303|-11097|-11077|-11067|-11055" .
        "|-11052|-11045|-11041|-11038|-11024|-11020|-11019|-11018|-11014|-10838|-10832|-10815|-10800|-10790|-10780" .
        "|-10764|-10587|-10544|-10533|-10519|-10331|-10329|-10328|-10322|-10315|-10309|-10307|-10296|-10281|-10274" .
        "|-10270|-10262|-10260|-10256|-10254";
    $_TDataKey = explode('|', $_DataKey);
    $_TDataValue = explode('|', $_DataValue);
    $_Data = (PHP_VERSION >= '5.0') ? array_combine($_TDataKey, $_TDataValue) : _Array_Combine($_TDataKey, $_TDataValue);
    arsort($_Data);
    reset($_Data);
    if ($_Code != 'gb2312') $_String = _U2_Utf8_Gb($_String);
    $_Res = '';
    for ($i = 0; $i < strlen($_String); $i++) {
        $_P = ord(substr($_String, $i, 1));
        if ($_P > 160) {
            $_Q = ord(substr($_String, ++$i, 1));
            $_P = $_P * 256 + $_Q - 65536;
        }
        $_Res .= _Pinyin($_P, $_Data);
    }
    return preg_replace("/[^a-z0-9]*/", '', $_Res);
}

function _Pinyin($_Num, $_Data)
{
    if ($_Num > 0 && $_Num < 160) return chr($_Num);
    elseif ($_Num < -20319 || $_Num > -10247) return '';
    else {
        foreach ($_Data as $k => $v) {
            if ($v <= $_Num) break;
        }
        return $k;
    }
}

function _U2_Utf8_Gb($_C)
{
    $_String = '';
    if ($_C < 0x80) $_String .= $_C;
    elseif ($_C < 0x800) {
        $_String .= chr(0xC0 | $_C >> 6);
        $_String .= chr(0x80 | $_C & 0x3F);
    } elseif ($_C < 0x10000) {
        $_String .= chr(0xE0 | $_C >> 12);
        $_String .= chr(0x80 | $_C >> 6 & 0x3F);
        $_String .= chr(0x80 | $_C & 0x3F);
    } elseif ($_C < 0x200000) {
        $_String .= chr(0xF0 | $_C >> 18);
        $_String .= chr(0x80 | $_C >> 12 & 0x3F);
        $_String .= chr(0x80 | $_C >> 6 & 0x3F);
        $_String .= chr(0x80 | $_C & 0x3F);
    }
    return iconv('UTF-8', 'GB2312', $_String);
}

function _Array_Combine($_Arr1, $_Arr2)
{
    for ($i = 0; $i < count($_Arr1); $i++) $_Res[$_Arr1[$i]] = $_Arr2[$i];
    return $_Res;
}

/***********************************将汉字转换为拼音 end*************************/

function get_cross_headers()
{
    $host_name = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : "*";
    $headers = [
        "Access-Control-Allow-Origin" => $host_name,
        "Access-Control-Allow-Credentials" => 'true',
        "Access-Control-Allow-Headers" => "x-token,x-uid,x-token-check,x-requested-with,content-type,Host,auth-token,Authorization",
        "Access-Control-Expose-Headers" => 'auth-token'
    ];

    return $headers;
}

function build_order_no($i=0){
    if(strlen($i) >= 6){
        $i = substr($i,-6);
        $str =  date('YmdHis') .$i;
    }else{
        $str=date('YmdHis') . str_pad($i.mt_rand(1, 9), 6, '0', STR_PAD_LEFT);
    }
    return $str;
}

/**
 * 新的订单生成方法
 * @return string
 */
function generate_order_number()
{
    $order = date('Y') . date('m') . date('d') . substr(time(), -5);
    $order .= substr(microtime(), 2, 5) . sprintf('%03d', mt_rand(0, 999));
    return $order;
}

function is_test_user($user_name = '')
{
    $test_pre = \think\Config::get('common.test_user_name_pre');
    $pos = strpos(strtolower($user_name), strtolower($test_pre));
    if ($pos === 0) {
        return true;
    } else {
        return false;
    }
}


function show_response($errorcode, $message, $data = '')
{
    if (is_array($data)) {
        $response = ['errorcode' => $errorcode, 'message' => $message, 'data' =>$data];
    } else {
        $response = ['errorcode' => $errorcode, 'message' => $message];
    }
    return $response;
}


function get_full_url()
{
    $https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;
    return
        ($https ? 'https://' : 'http://') .
        (!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] . '@' : '') .
        (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] .
            ($https && $_SERVER['SERVER_PORT'] === 443 ||
            $_SERVER['SERVER_PORT'] === 80 ? '' : ':' . $_SERVER['SERVER_PORT']))) .
        substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
}

function build_website($path)
{
    if ($path) {
        if (strpos($path, 'http') === 0) {
            return $path;
        } else {
            return get_full_url() . $path;
        }
    }
}


//密码生成方法
function encrypt_password($password, $salt)
{
    $password = trim($password);
    return md5($password . $salt);
}


function array_ksort($list){
    ksort($list);
    foreach ($list as $k=>$v){
        if(is_array($v)){
            $list[$k] = array_ksort($v);
        }
    }
    return $list;
}


function generate_digital_sign($request)
{
    $request = array_ksort($request);
    $signList = [];
    foreach ($request as $key => $val) {
        if (is_array($val)) {
            $val = json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        $signList[] = $key . '=' . $val;
    }

    return  md5(implode('&', $signList) . 'kosun.net');
}


function call_to_digital($params){
    $apiUrl = \think\Env::get('app.digital_api_url');

    $sign = generate_digital_sign($params);
    $data = json_encode($params);

    $header = [
        'Content-Type: text/json',
        "Content-length: " . strlen($data),
        "Authorization: " . $sign
    ];

    $curlRequest = new Curlrequest();
    $result = $curlRequest->curlJsonPost($apiUrl, $data, $header);
    return json_decode($result, true);
}



function array_subtotal($list, $field = []){
    $subtotal = [];
    foreach ($list as $info)
    {
        if(is_object($info)){
            $info = $info->toArray();
        }

        if(!is_array($info)){
            return;
        }

        foreach ($info as $k => $v)
        {
            if(in_array($k, $field)){
                if(isset($subtotal[$k])){
                    $subtotal[$k] = bcadd($subtotal[$k], $v) ;
                }else{
                    $subtotal[$k] = $v;
                }
            }
        }
    }

    return $subtotal;
}


/**
 * 计算两个日期之间相差的天数
 */
function count_days($a, $b) {
    return round(abs(strtotime($a) - strtotime($b)) / 86400);
}

/**
 * 二维数组按照某字段排序
 * $array 待排序数组
 * $key 排序的键名
 * $sort 1 - 默认，按升序排列。2 - 按降序排列。(Z-A)
 * return 排序后的数组
 */
function array_sort_bykey($array = [], $key = '', $sort = 1)
{
    $keyArray = array();
    foreach ($array as $v) {
      $keyArray[] = $v[$key];
    }

    $sort = ($sort == 1) ? SORT_ASC : SORT_DESC;
    array_multisort($keyArray, $sort, $array);

    return $array;
}

/**
 * 判断数组是否二维数组
 * @param $data
 * @return bool
 */
function is_multi_array($data){
    if(count($data) == count($data,COUNT_RECURSIVE)){
        return false;
    }else{
        return true;
    }
}

function redis_init()
{
    $config = \think\Config::get('cache.default');
    $select = \think\Env::get('redis.select');

    try{
        if(class_exists('Redis')){
            $redis = new \Redis();
        }else{
            throw new \Exception('Class Redis not found');
        }
        $connected = $redis->pconnect($config['host'], $config['port']);
        if(!$connected){
            throw new \Exception('connect redis fail');
        }

        $redis->auth($config['password']);

        $redis->select(isset($select)?$select:0);
        return $redis;

    }catch (\Exception $e){
        error_log($e->getMessage(), 3, './redis_error_'.date('Ymd').'_log.txt');
    }
}

function format_redis_key($key)
{
    return  \think\Env::get('redis.prefix').'_formal'. '_' . $key;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key  加密密钥
 * @param int $expire  过期时间 单位 秒
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_encrypt($data, $key = '', $expire = 0) {
    $key  = md5($key);
    $data = base64_encode($data);
    $x    = 0;
    $len  = strlen($data);
    $l    = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time():0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
    }
    return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
}


/**
 * 切换到试玩库
 */
function try_db(){
    $GLOBALS['auth_identity'] = 'guest';

    $dbFilename = CONF_PATH . 'extra' . DS . 'database' . CONF_EXT;
    $cacheFilename = CONF_PATH . 'extra' . DS . 'cache' . CONF_EXT;
    Config::load($dbFilename, 'database');
    Config::load($cacheFilename, 'cache');
}

/**
 * 获取冷数据表-年月信息
 * @param $startDate
 * @param $endDate
 * @return array
 */
function get_cold_data_ym($startDate,$endDate){

    $sTime = strtotime($startDate);
    $eTime = strtotime($endDate);

    $m = date("m",$sTime);
    $y  = date("Y",$sTime);

    $sym = date("Ym",$sTime);
    $eym = date("Ym",$eTime);

    $ym = [];
    $i = 0;

    $_startDate = $startDate;
    $_endDate   = date("Y-m-01 0:00:00",mktime(0,0,0,$m+1,1,$y));

    while ($sym<=$eym){

        $tmp = [];
        $tmp['ym'] = $sym;
        $tmp['sdate'] = $_startDate;
        $tmp['edate'] = $_endDate;
        $ym[] = $tmp;

        $i++;
        $m1time =  mktime(0,0,0,$m+$i,1,$y);

        $sym = date("Ym",$m1time);
        $_startDate = date("Y-m-01 0:00:00",$m1time);
        $_endDate = ($sym==$eym)?$endDate:date("Y-m-01 0:00:00",mktime(0,0,0,$m+$i+1,1,$y));
    }

    return $ym;
}

/**
 * 判断表是否存在
 * @param $talbeName
 * @return mixed
 */
function table_is_exist($talbeName){

    $dbName = \think\Env::get('mysql.database','dscp');

    $sql = "SELECT `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = '{$dbName}' AND `TABLE_NAME` = '{$talbeName}'";

    $query = new \think\db\Query();
    $isExist = $query->query($sql);

    return empty($isExist)?false:true;
}

function send_response($result, $errorcode, $errorMessage = null)
{
    $response = [];
    if (is_array($result)) {
        $response['data'] = output_format($result);
    }
    $response['errorcode'] = $errorcode;
    $response['message'] = $errorMessage ? $errorMessage : Config::get('errorcode')[$errorcode];

    return $response;
}




/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0,$adv=true) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($adv){
        if (isset($_SERVER['HTTP_CLIENT_IP']))
        {
            $ip     =   $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos    =   array_search('unknown',$arr);
            if(false !== $pos) unset($arr[$pos]);
            $ip     =   trim($arr[0]);
        }
        elseif (isset($_SERVER['REMOTE_ADDR']))
        {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);

    return $ip[$type];
}


/**
 * 批量更新
 * @param string $tableName 表名（含前缀）
 * @param array $data 数据
 * @param string $field 主键字段
 * @return bool|int
 */
function batch_update($tableName='',$data=array(),$field=''){
    if(!$tableName||!$data||!$field){
        return false;
    }else{
        $sql='UPDATE '.$tableName;
    }
    $con    = [];
    $conSql= [];
    $fields = [];
    foreach ($data as $key => $value) {
        $x=0;
        foreach ($value as $k => $v) {
            if($k!=$field&&!isset($con[$x]) &&$x==0){
                $con[$x]=" set {$k} = (CASE {$field} ";
            }elseif($k!=$field &&!isset($con[$x]) &&$x>0){
                $con[$x]=" {$k} = (CASE {$field} ";
            }
            if($k!=$field){
                $temp=$value[$field];
                $conSql[$x] = !isset($conSql[$x])? " WHEN '{$temp}' THEN '{$v}' ": $conSql[$x]. " WHEN '{$temp}' THEN '{$v}' " ;
                $x++;
            }
        }
        $temp=$value[$field];
        if(!in_array($temp,$fields)){
            $fields[]=$temp;
        }
    }
    $num=count($con)-1;
    foreach ($con as $key => $value) {
        foreach ($conSql as $k => $v) {
            if($k==$key&&$key<$num){
                $sql.=$value.$v.' end),';
            }elseif($k==$key&&$key==$num){
                $sql.=$value.$v.' end)';
            }
        }
    }
    $str=implode(',',$fields);
    $sql.=" where {$field} in({$str})";

    $query = new \think\db\Query();
    $result = $query->execute($sql);

    return $result;
}


/**
 * 多条件批量更新
 * @param string $tableName 表名（含前缀）
 * @param array $data 数据
 * @param string $whereField 条件字段
 * @param string $whereIndexField 主键索引字段
 * @return bool|int
 */
function batch_update_multi_key($tableName='',$data=[],$whereField='',$whereIndexField=''){
    if(!$tableName||!$data||!$whereField){
        return false;
    }else{
        $sql='UPDATE '.$tableName. ' set ';
    }

    $whenFields = [];
    if(is_array($whereField)){
        $whenFields = $whereField;
    }elseif(is_string($whereField)&&strpos($whereField,",")){
        $whenFields = explode(",",$whereField);
    }elseif(is_string($whereField)){
        $whenFields = [$whereField];
    }

    $con        = [];
    $conSql     = [];
    $default    = [];
    $where      = [];

    foreach ($data as $key => $row) {
        $x=0;
        $when_exp = "";

        foreach ($row as $field=>$value){
            if(in_array($field,$whenFields)){
                $when_exp = $when_exp?$when_exp." and {$field}={$value}":"{$field}={$value}";
            }
        }

        foreach($row as $k=>$v){
            if(!in_array($k,$whenFields)&&!$con[$x]){
                $con[$x]=" {$k} = (CASE ";
            }
            if(!in_array($k,$whenFields) && !$default[$x] ){
                $default[$x] = " ElSE {$k} ";
            }
            if(!in_array($k,$whenFields)){
                $conSql[$x].=" WHEN {$when_exp} THEN '{$v}' ";
                $x++;
            }
        }

        $tmp = $row[$whereIndexField];
        if($whereIndexField && !in_array($tmp,$where)){
            $where[] = $tmp;
        }
    }

    $num=count($con)-1;
    foreach ($con as $key => $value) {
        foreach ($conSql as $k => $v) {
            if($k==$key&&$key<$num){
                $sql.=$value.$v.$default[$key].' end),';
            }elseif($k==$key&&$key==$num){
                $sql.=$value.$v.$default[$key].' end)';
            }
        }
    }

    if(!empty($where)){
        $whereStr=implode(',',$where);
        $sql.=" where {$whereIndexField} in({$whereStr})";
    }

    $query = new \think\db\Query();
    $result = $query->execute($sql);

    return $result;
}

/**
 * 生成签名结果
  @param array $params 已排序要签名的数组
  @param string $signKey 加密key
  @return string 签名结果字符串
 */
function build_request_sign($params, $signKey)
{
    $linkString = build_link_string($params);
    return md5($linkString . $signKey);
}

/**
 * 生成待签名字符串
  对数组里的每一个值从a到z的顺序排序，若遇到相同首字母，则看第二个字母以此类推。
  排序完成后，再把所有数组值以‘&’字符连接起来
  @param  array $params 待签名参数
  @return string
 */
function build_link_string($params)
{
    //sign和空值不参与签名
    $paramsFilter = array();
    while (list($key, $val) = each($params)) {
        if ($key == 'sign' || $val == '') {
            continue;
        } else {
            $paramsFilter[$key] = $params[$key];
        }
    }

    //对待签名参数数组排序a-z
    ksort($paramsFilter);
    reset($paramsFilter);

    //生成签名结果
    //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
    $query = '';
    while (list($key, $val) = each($paramsFilter)) {
        $query .= $key . '=' . $val . '&';
    }
    //去掉最后一个&字符
    $query = substr($query, 0, count($query) - 2);

    //如果存在转义字符，那么去掉转义
    if (get_magic_quotes_gpc()) {
        $query = stripslashes($query);
    }

    return $query;
}


function call_pay_center_api($apiUrl, $requestData = false){
    $appConfig = Config::get('pay.app_config');
    $signKey = $appConfig['sign_key'];
    $params['appId'] = $appConfig['app_id'];
    $params['nonce'] = random_string(32);
    if(is_array($requestData)){
        $params = array_merge($params, $requestData);
    }
    $sign = build_request_sign($params, $signKey);
    $params['sign'] = $sign;
    Log::write($apiUrl."请求的参数:". print_r($params, true));
    $result = Curlrequest::post($apiUrl, $params);
    Log::write($apiUrl."返回的数据:". print_r($result, true));
    if(empty($result)){
        return false;
    }

    return json_decode($result, true);
}