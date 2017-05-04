<?php

function &cache_server()
{
    import('cache.lib');
    static $CS = null;
    if ($CS === null)
    {
        switch (CACHE_SERVER)
        {
            case 'memcached':
                list($host, $port) = explode(':', CACHE_MEMCACHED);
                $CS = new MemcacheServer(array(
                    'host'  => $host,
                    'port'  => $port,
                ));
            break;
            default:
                $CS = new PhpCacheServer;
                $CS->set_cache_dir(ROOT_PATH . '/temp/caches');
            break;
        }
    }

    return $CS;
}

/**
 *    获取商品类型对象
 *
 *    @author    Garbin
 *    @param     string $type
 *    @param     array  $params
 *    @return    void
 */
function &gt($type, $params = array())
{
    static $types = array();
    if (!isset($types[$type]))
    {
        /* 加载订单类型基础类 */
        include_once(ROOT_PATH . '/includes/goods.base.php');
        include(ROOT_PATH . '/includes/goodstypes/' . $type . '.gtype.php');
        $class_name = ucfirst($type) . 'Goods';
        $types[$type]   =   new $class_name($params);
    }

    return $types[$type];
}

/**
 *    获取订单类型对象
 *
 *    @author    Garbin
 *    @param    none
 *    @return    void
 */
function &ot($type, $params = array())
{
    static $order_type = null;
    if ($order_type === null)
    {
        /* 加载订单类型基础类 */
        include_once(ROOT_PATH . '/includes/order.base.php');
        include(ROOT_PATH . '/includes/ordertypes/' . $type . '.otype.php');
        $class_name = ucfirst($type) . 'Order';
        $order_type = new $class_name($params);
    }

    return $order_type;
}

/**
 *    获取数组文件对象
 *
 *    @author    Garbin
 *    @param     string $type
 *    @param     array  $params
 *    @return    void
 */
function &af($type, $params = array())
{
    static $types = array();
    if (!isset($types[$type]))
    {
        /* 加载数据文件基础类 */
        include_once(ROOT_PATH . '/includes/arrayfile.base.php');
        include(ROOT_PATH . '/includes/arrayfiles/' . $type . '.arrayfile.php');
        $class_name = ucfirst($type) . 'Arrayfile';
        $types[$type]   =   new $class_name($params);
    }

    return $types[$type];
}

/**
 *    连接会员系统
 *
 *    @author    Garbin
 *    @return    Passport 会员系统连接接口
 */
function &ms()
{
    static $ms = null;
    if ($ms === null)
    {
        include(ROOT_PATH . '/includes/passport.base.php');
        include(ROOT_PATH . '/includes/passports/' . MEMBER_TYPE . '.passport.php');
        $class_name  = ucfirst(MEMBER_TYPE) . 'Passport';
        $ms = new $class_name();
    }

    return $ms;
}


/**
 *    获取用户头像地址
 *
 *    @author    Garbin
 *    @param     string $portrait
 *    @return    void
 */
function portrait($user_id, $portrait, $size = 'small')
{
    switch (MEMBER_TYPE)
    {
        case 'uc':
            return UC_API . '/avatar.php?uid=' . $user_id . '&amp;size=' . $size;
        break;
        default:
            return empty($portrait) ? Conf::get('default_user_portrait') : $portrait;
        break;
    }
}

/**
 *    获取环境变量
 *
 *    @author    Garbin
 *    @param     string $key
 *    @param     mixed  $val
 *    @return    mixed
 */
function &env($key, $val = null)
{
    !isset($GLOBALS['EC_ENV']) && $GLOBALS['EC_ENV'] = array();
    $vkey = $key ? strtokey("{$key}", '$GLOBALS[\'EC_ENV\']') : '$GLOBALS[\'EC_ENV\']';
    if ($val === null)
    {
        /* 返回该指定环境变量 */
        $v = eval('return isset(' . $vkey . ') ? ' . $vkey . ' : null;');

        return $v;
    }
    else
    {
        /* 设置指定环境变量 */
        eval($vkey . ' = $val;');

        return $val;
    }
}

/**
 *    获取订单状态相应的文字表述
 *
 *    @author    Garbin
 *    @param     int $order_status
 *    @return    string
 */
function order_status($order_status)
{
    $lang_key = '';
    switch ($order_status)
    {
        case ORDER_PENDING:
            $lang_key = 'order_pending';
        break;
        case ORDER_SUBMITTED:
            $lang_key = 'order_submitted';
        break;
        case ORDER_ACCEPTED:
            $lang_key = 'order_accepted';
        break;
        case ORDER_SHIPPED:
            $lang_key = 'order_shipped';
        break;
        case ORDER_FINISHED:
            $lang_key = 'order_finished';
        break;
        case ORDER_CANCELED:
            $lang_key = 'order_canceled';
        break;
    }

    return $lang_key  ? Lang::get($lang_key) : $lang_key;
}

/**
 *    获取第三方订单状态相应的文字表述
 */
function vendor_order_status($order_status)
{
    $lang_key = '';
    switch ($order_status)
    {
        case VENDOR_ORDER_UNHANDLED:
            $lang_key = 'vendor_order_unhandled';
        break;
        case VENDOR_ORDER_PENDING:
            $lang_key = 'vendor_order_pending';
        break;
        case VENDOR_ORDER_ACCEPTED:
            $lang_key = 'vendor_order_accepted';
        break;
        case VENDOR_ORDER_SHIPPED:
            $lang_key = 'vendor_order_shipped';
        break;
        case VENDOR_ORDER_SYNCED:
            $lang_key = 'vendor_order_synced';
        break;
    }

    return $lang_key ? Lang::get($lang_key) : $lang_key;
}

/**
 *    转换订单状态值
 *
 *    @author    Garbin
 *    @param     string $order_status_text
 *    @return    void
 */
function order_status_translator($order_status_text)
{
    switch ($order_status_text)
    {
        case 'canceled':    //已取消的订单
            return ORDER_CANCELED;
        break;
        case 'all':         //所有订单
            return '';
        break;
        case 'pending':     //待付款的订单
            return ORDER_PENDING;
        break;
        case 'submitted':   //已提交的订单
            return ORDER_SUBMITTED;
        break;
        case 'accepted':    //已确认的订单，待发货的订单
            return ORDER_ACCEPTED;
        break;
        case 'shipped':     //已发货的订单
            return ORDER_SHIPPED;
        break;
        case 'finished':    //已完成的订单
            return ORDER_FINISHED;
        break;
        default:            //所有订单
            return '';
        break;
    }
}

/**
 *    获取邮件内容
 *
 *    @author    Garbin
 *    @param     string $mail_tpl
 *    @param     array  $var
 *    @return    array
 */
function get_mail($mail_tpl, $var = array())
{
    $subject = '';
    $message = '';

    /* 获取邮件模板 */
    $model_mailtemplate =& af('mailtemplate');
    $tpl_info   =   $model_mailtemplate->getOne($mail_tpl);
    if (!$tpl_info)
    {
        return false;
    }

    /* 解析其中变量 */
    $tpl =& v(true);
    $tpl->direct_output = true;
    $tpl->assign('site_name', Conf::get('site_name'));
    $tpl->assign('site_url', SITE_URL);
    $tpl->assign('mail_send_time', local_date('Y-m-d H:i', gmtime()));
    foreach ($var as $key => $val)
    {
        $tpl->assign($key, $val);
    }
    $subject = $tpl->fetch('str:' . $tpl_info['subject']);
    $message = $tpl->fetch('str:' . $tpl_info['content']);

    /* 返回邮件 */

    return array(
        'subject'   => $subject,
        'message'   => $message
    );
}

/**
 *    获取消息内容
 *
 *    @author    Garbin
 *    @param     string $msg_tpl
 *    @param     array  $var
 *    @return    string
 */
function get_msg($msg_tpl, $var = array())
{
    /* 获取消息模板 */
    $ms = &ms();
    $msg_content = Lang::get($msg_tpl);
    $var['site_url'] = SITE_URL; // 给短消息模板中设置一个site_url变量
    $search = array_keys($var);
    $replace = array_values($var);

    /* 解析其中变量 */
    array_walk($search, create_function('&$str', '$str = "{\$" . $str. "}";'));
    $msg_content = str_replace($search, $replace, $msg_content);
    return $msg_content;
}

/**
 *    获取邮件发送网关
 *
 *    @author    Garbin
 *    @return    object
 */
function &get_mailer()
{
    static $mailer = null;
    if ($mailer === null)
    {
        /* 使用mailer类 */
        import('mailer.lib');
        $sender     = Conf::get('site_name');
        $from       = Conf::get('email_addr');
        $protocol   = Conf::get('email_type');
        $host       = Conf::get('email_host');
        $port       = Conf::get('email_port');
        $username   = Conf::get('email_id');
        $password   = Conf::get('email_pass');
        $mailer = new Mailer($sender, $from, $protocol, $host, $port, $username, $password);
    }

    return $mailer;
}

/**
 *    模板列表
 *
 *    @author    Garbin
 *    @param     strong $who
 *    @return    array
 */
function list_template($who)
{
    $theme_dir = ROOT_PATH . '/themes/' . $who;
    $dir = dir($theme_dir);
    $array = array();
    while (($item  = $dir->read()) !== false)
    {
        if (in_array($item, array('.', '..')) || $item{0} == '.' || $item{0} == '$')
        {
            continue;
        }
        $theme_path = $theme_dir . '/' . $item;
        if (is_dir($theme_path))
        {
            if (is_file($theme_path . '/theme.info.php'))
            {
                $array[] = $item;
            }
        }
    }

    return $array;
}

/**
 *    列表风格
 *
 *    @author    Garbin
 *    @param     string $who
 *    @return    array
 */
function list_style($who, $template = 'default')
{
    $style_dir = ROOT_PATH . '/themes/' . $who . '/' . $template . '/styles';
    $dir = dir($style_dir);
    $array = array();
    while (($item  = $dir->read()) !== false)
    {
        if (in_array($item, array('.', '..')) || $item{0} == '.' || $item{0} == '$')
        {
            continue;
        }
        $style_path = $style_dir . '/' . $item;
        if (is_dir($style_path))
        {
            if (is_file($style_path . '/style.info.php'))
            {
                $array[] = $item;
            }
        }
    }

    return $array;
}


/**
 *    获取挂件列表
 *
 *    @author    Garbin
 *    @return    array
 */
function list_widget()
{
    $widget_dir = ROOT_PATH . '/external/widgets';
    static $widgets    = null;
    if ($widgets === null)
    {
        $widgets = array();
        if (!is_dir($widget_dir))
        {
            return $widgets;
        }
        $dir = dir($widget_dir);
        while (false !== ($entry = $dir->read()))
        {
            if (in_array($entry, array('.', '..')) || $entry{0} == '.' || $entry{0} == '$')
            {
                continue;
            }
            if (!is_dir($widget_dir . '/' . $entry))
            {
                continue;
            }
            $info = get_widget_info($entry);
            $widgets[$entry] = $info;
        }
    }

    return $widgets;
}

/**
 *    获取挂件信息
 *
 *    @author    Garbin
 *    @param     string $id
 *    @return    array
 */
function get_widget_info($name)
{
    $widget_info_path = ROOT_PATH . '/external/widgets/' . $name . '/widget.info.php';

    return include($widget_info_path);
}

function i18n_code()
{
    $code = 'zh-CN';
    $lang_code = substr(LANG, 0, 2);
    switch ($lang_code)
    {
        case 'sc':
            $code = 'zh-CN';
        break;
        case 'tc':
            $code = 'zh-TW';
        break;
        default:
            $code = 'zh-CN';
        break;
    }

    return $code;
}

/**
 *    从字符串获取指定日期的结束时间(24:00)
 *
 *    @author    Garbin
 *    @param     string $str
 *    @return    int
 */
function gmstr2time_end($str)
{
    return gmstr2time($str) + 86400;
}

/**
 *    获取URL地址
 *
 *    @author    Garbin
 *    @param     mixed $query
 *    @param     string $rewrite_name
 *    @return    string
 */
function url($query, $rewrite_name = null)
{
    $re_on  = Conf::get('rewrite_enabled');
    $url = '';
    if (!$re_on)
    {
        /* Rewrite未开启 */
        $url = 'index.php?' . $query;
    }
    else
    {
        /* Rewrite已开启 */
        $re =& rewrite_engine();
        $rewrite = $re->get($query, $rewrite_name);

        $url = ($rewrite !== false) ? $rewrite : 'index.php?' . $query;
    }

    return str_replace('&', '&amp;', $url);
}

/**
 *    获取rewrite engine
 *
 *    @author    Garbin
 *    @return    Object
 */
function &rewrite_engine()
{
    $re_name= Conf::get('rewrite_engine');
    static $re = null;
    if ($re === null)
    {
        include(ROOT_PATH . '/includes/rewrite.base.php');
        include(ROOT_PATH . '/includes/rewrite_engines/' . $re_name . '.rewrite.php');
        $re_class_name = ucfirst($re_name) . 'Rewrite';
        $re = new $re_class_name();
    }

    return $re;
}

/**
 *    转换团购活动状态值
 *
 *    @author    Garbin
 *    @param     string $status_text
 *    @return    void
 */
function groupbuy_state_translator($state_text)
{
    switch ($state_text)
    {
        case 'all':         //全部团购活动
            return '';
        break;
        case 'on':         //进行中的团购活动
            return GROUP_ON;
        break;
        case 'canceled':    //已取消的团购活动
            return GROUP_CANCELED;
        break;
        case 'pending':     //未发布的团购活动
            return GROUP_PENDING;
        break;
        case 'finished':     //已完成的团购活动
            return GROUP_FINISHED;
        break;
        case 'end':     //已完成的团购活动
            return GROUP_END;
        break;
        default:            //全部团购活动
            return '';
        break;
    }
}

/**
 *    获取团购状态相应的文字表述
 *
 *    @author    Garbin
 *    @param     int $group_state
 *    @return    string
 */
function group_state($group_state)
{
    $lang_key = '';
    switch ($group_state)
    {
        case GROUP_PENDING:
            $lang_key = 'group_pending';
        break;
        case GROUP_ON:
            $lang_key = 'group_on';
        break;
        case GROUP_CANCELED:
            $lang_key = 'group_canceled';
        break;
        case GROUP_FINISHED:
            $lang_key = 'group_finished';
        break;
        case GROUP_END:
            $lang_key = 'group_end';
        break;
    }

    return $lang_key  ? Lang::get($lang_key) : $lang_key;
}


/**
 *    计算剩余时间
 *
 *    @author    Garbin
 *    @param     string $format
 *    @param     int $time;
 *    @return    string
 */
function lefttime($time, $format = null)
{
    $lefttime = $time - gmtime();
    if ($lefttime < 0)
    {
        return '';
    }
    if ($format === null)
    {
        if ($lefttime < 3600)
        {
            $format = Lang::get('lefttime_format_1');
        }
        elseif ($lefttime < 86400)
        {
            $format = Lang::get('lefttime_format_2');
        }
        else
        {
            $format = Lang::get('lefttime_format_3');
        }
    }
    $d = intval($lefttime / 86400);
    $lefttime -= $d * 86400;
    $h = intval($lefttime / 3600);
    $lefttime -= $h * 3600;
    $m = intval($lefttime / 60);
    $lefttime -= $m * 60;
    $s = $lefttime;

    return str_replace(array('%d', '%h', '%i', '%s'),array($d, $h,$m, $s), $format);
}


/**
 * 多维数组排序（多用于文件数组数据）
 *
 * @author Hyber
 * @param array $array
 * @param array $cols
 * @return array
 *
 * e.g. $data = array_msort($data, array('sort_order'=>SORT_ASC, 'add_time'=>SORT_DESC));
 */
function array_msort($array, $cols)
{
    $colarr = array();
    foreach ($cols as $col => $order) {
        $colarr[$col] = array();
        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower($row[$col]); }
    }
    $eval = 'array_multisort(';
    foreach ($cols as $col => $order) {
        $eval .= '$colarr[\''.$col.'\'],'.$order.',';
    }
    $eval = substr($eval,0,-1).');';
    eval($eval);
    $ret = array();
    foreach ($colarr as $col => $arr) {
        foreach ($arr as $k => $v) {
            $k = substr($k,1);
            if (!isset($ret[$k])) $ret[$k] = $array[$k];
            $ret[$k][$col] = $array[$k][$col];
        }
    }
    return $ret;
}

/**
 * 短消息过滤
 *
 * @return string
 */
function short_msg_filter($string)
{
    $ms = & ms();
    return $ms->pm->msg_filter($string);
}

/**
 * 生成二维码
 * @param unknown $chl
 * @param string $widhtHeight
 * @param string $EC_level
 * @param string $margin
 */
function generateQRfromGoogle($chl,$widhtHeight ='100',$EC_level='L',$margin='0')
{
    $chl = urlencode($chl);
    return '<img src="http://chart.apis.google.com/chart?chs='.$widhtHeight.'x'.$widhtHeight.'&cht=qr&chld='.$EC_level.'|'.$margin.'&chl='.$chl.'" alt="QR code" widhtHeight="'.$size.'" widhtHeight="'.$size.'"/>';
}

/**
 * 简单根据内容生成
 * @param 生成二维码的内容 $txt
 * @param 二维码图片名称区分标记 $fileflag
 */
function generateQRfromQRCode($txt,$fileflag)
{
    $PNG_TEMP_DIR = ROOT_PATH.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'qrcode'.DIRECTORY_SEPARATOR;
    $PNG_WEB_DIR = 'data/qrcode/';
    import('qrlib');
    if(!file_exists($PNG_TEMP_DIR))
        mkdir($PNG_TEMP_DIR);
    $errorCorrectionLevel = 'H';//L,M,Q,H
    $matrixPointSize = 5;//1-10
    $data='weixin://contacts/profile/'.trim($txt);
    if(empty($data))
        return false;
        //die('qrdata cannot be empty!');
    $filename = $PNG_TEMP_DIR.'zwd51_'.$fileflag.'.png';
    QRcode::png($data,$filename,$errorCorrectionLevel,$matrixPointSize,2);
    return true;
}

/**
 *   文件大小换算 tiq
 */
function filesize_caculate($filesize)
{
    if($filesize >= 1073741824)
    {
        $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
    }
    elseif($filesize >= 1048576)
    {
        $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
    }
    elseif($filesize >= 1024)
    {
        $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
    }
    else
    {
        $filesize = $filesize . ' Bytes';
    }
    return $filesize;
}
/*根据现有价格计算出淘宝价*/
function make_price($price, $seePrice, $title = null)
{
    $finalPrice = $rawPrice = floatval ( $price );
    if (strpos ( $seePrice, '减半' ) !== false)
    {
        $finalPrice = $rawPrice * 2;
    }
    else if (strpos ( $seePrice, 'P' ) !== false || $seePrice == '减P' || $seePrice == '减p')
    {
        $regexP = '/[Pp](\d+)/';
        $regexF = '/[Ff](\d+)/';
        if (preg_match ( $regexP, $title, $matches ) == 1)
        {
            $finalPrice = floatval ( $matches [1] );
        }
        else if (preg_match ( $regexF, $title, $matches ) == 1)
        {
            $finalPrice = floatval ( $matches [1] );
        }
    }
    else if (strpos ( $seePrice, '减' ) === 0)
    {
        $finalPrice = $rawPrice + floatval ( mb_substr ( $seePrice, 1, mb_strlen ( $seePrice, 'utf-8' ) - 1, 'utf-8' ) );
    }
    else if (strpos ( $seePrice, '实价' ) !== false)
    {
        $finalPrice = $rawPrice;
    }
    else if (strpos ( $seePrice, '*' ) === 0)
    {
        $finalPrice = $rawPrice / floatval ( mb_substr ( $seePrice, 1, mb_strlen ( $seePrice, 'utf-8' ) - 1, 'utf-8' ) );
    }
    else if (strpos ( $seePrice, '打' ) === 0)
    {
        $finalPrice = $rawPrice / (floatval ( mb_substr ( $seePrice, 1, mb_strlen ( $seePrice, 'utf-8' ) - 1, 'utf-8' ) ) / 10);
    }
    else if (strpos ( $seePrice, '折' ) === mb_strlen ( $seePrice, 'utf-8' ) - 1)
    {
        $finalPrice = $rawPrice / (floatval ( mb_substr ( $seePrice, 0, mb_strlen ( $seePrice, 'utf-8' ) - 1, 'utf-8' ) ) / 10);
    }
    if (is_numeric ( $finalPrice ))
    {
        return $finalPrice;
    }
    else
    {
        return $price;
    }
}

/**
 * 根据商品标题得到货号
 * @param unknown $title 商品标题
 * @param string $propsName 查下attr表，看看有没有attr id为13021751的属性值，只拿其中的数字部分
 * @return unknown
 */
function getHuoHao($title, $propsName = null) {
    $huoHao = '';
    $kuanHaoRegex='/[A-Z]?\d+/';
    preg_match_all($kuanHaoRegex,$title,$kuanHao);
    $pKhnum=count($kuanHao[0]);
    if($pKhnum>0) {
        for($i=0;$i < $pKhnum;$i++) {
            if(strlen($kuanHao[0][$i])==3 || (strlen($kuanHao[0][$i])==4 && substr($kuanHao[0][$i], 0,3)!= "201")) {
                $huoHao = $kuanHao[0][$i];
                break;
            }
        }
    }
    if (!$huoHao && $propsName != null) {
        if (strpos(''.$propsName, '13021751') !== false) {
            $parts = explode(';', ''.$propsName);
            $count = count($parts);
            for ($i = 0; $i < $count; $i++) {
                if (strpos($parts[$i], '13021751') !== false) {
                    $values = explode(':', $parts[$i]);
                    $huoHao = $values[3];
                }
            }
        }
    }
    return $huoHao;
}

/**
 * 生成商家编码，确保ecm_store(shop_mall:富丽，address:A01);
 * @param 店铺 $store_id
 */
function generate_storeBM($store_id)
{
    sp_db()->query("call build_outer_iid(".intval($store_id).",".(intval($store_id) + 1).")");
}
/**
 *  获取拼音信息
 *
 * @access    public
 * @param     string  $str  字符串
 * @param     int  $ishead  是否为首字母
 * @param     int  $isclose  解析后是否释放资源
 * @return    string
 */
function GetPinyin($str, $ishead=0, $isclose=1)
{
    global $pinyins;
    $restr = '';
    $str = trim($str);
    $slen = strlen($str);
    if($slen < 2)
    {
        return $str;
    }
    if(count($pinyins) == 0)
    {
        $fp = fopen(ROOT_PATH.'/includes/codetable/pinyin.dat', 'r');
        while(!feof($fp))
        {
            $line = trim(fgets($fp));
            $pinyins[$line[0].$line[1]] = substr($line, 3, strlen($line)-3);
        }
        fclose($fp);
    }
    for($i=0; $i<$slen; $i++)
    {
    if(ord($str[$i])>0x80)
    {
    $c = $str[$i].$str[$i+1];
        $i++;
        if(isset($pinyins[$c]))
        {
        if($ishead==0)
        {
        $restr .= $pinyins[$c];
        }
            else
            {
            $restr .= $pinyins[$c][0];
            }
            }else
            {
            $restr .= "_";
            }
            }else if( preg_match("/[a-z0-9]/i", $str[$i]) )
                {
                    $restr .= $str[$i];
            }
            else
                    {
                    $restr .= "_";
        }
    }
    if($isclose==0)
        {
            unset($pinyins);
    }
    return $restr;
    }
    function getSphinxAddress(){
        $cache_server = & cache_server();
        $current = $cache_server->get('currentSphinx');
        if($current){
            return $current;
        }else{
            return "127.0.0.1";
        }
    }

    include_once ROOT_PATH.'/includes/customize/common.lib.php';
?>
