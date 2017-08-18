<?php

/*
 * 人人商城V2
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
    exit('Access Denied');
}

//加载模型
if (!function_exists('m')) {
    function m($name = '')
    {
        static $_modules = array();
        if (isset($_modules[$name])) {

            return $_modules[$name];
        }
        $model = EWEI_SHOPV2_CORE . "model/" . strtolower($name) . '.php';

        if (!is_file($model)) {
            die(' Model ' . $name . ' Not Found!');
        }
        require_once $model;
        $class_name = ucfirst($name) . "_EweiShopV2Model";
        $_modules[$name] = new $class_name();
        return $_modules[$name];
    }
}

//加载数据缓存模型
if (!function_exists('d')) {
    function d($name = '')
    {
        static $_modules = array();
        if (isset($_modules[$name])) {
            return $_modules[$name];
        }
        $model = EWEI_SHOPV2_CORE . "data/" . strtolower($name) . '.php';
        if (!is_file($model)) {
            die(' Data Model ' . $name . ' Not Found!');
        }
        require_once EWEI_SHOPV2_INC . "data_model.php";
        require_once $model;
        $class_name = ucfirst($name) . "_EweiShopV2DataModel";
        $_modules[$name] = new $class_name();
        return $_modules[$name];
    }
}

//运行插件方法
if (!function_exists('plugin_run')) {
    function plugin_run($name = '')
    {
        $names = explode("::", $name);
        $plugin = p($names[0]);
        if (!$plugin) {
            return false;
        }
        if (!method_exists($plugin, $names[1])) {
            return false;
        }

        $func_args = func_get_args();
        $args = array_splice($func_args, 1);
        return call_user_func_array(array($plugin, $names[1]), $args);
    }
}

//加载插件方法
if (!function_exists('p')) {
    function p($name = '')
    {

        static $_plugins = array();
        if (isset($_plugins[$name])) {
            return $_plugins[$name];
        }
        $model = EWEI_SHOPV2_PLUGIN . strtolower($name) . '/core/model.php';
        if (!is_file($model)) {
            return false;
        }
        require_once EWEI_SHOPV2_CORE . "inc/plugin_model.php";
        require_once $model;
        $class_name = ucfirst($name) . 'Model';
        $_plugins[$name] = new $class_name($name);

        if (com_run('perm::check_plugin', $name)) {
            if ($name == 'seckill') {
                if (!function_exists('redis') || is_error(redis())) {
                    return false;
                }
            }

            return $_plugins[$name];
        }

        return false;
    }
}

//加载组建插件方法
if (!function_exists('com')) {
    function com($name = '')
    {


        static $_coms = array();
        if (isset($_coms[$name])) {
            return $_coms[$name];
        }
        $model = EWEI_SHOPV2_CORE . "com/" . strtolower($name) . '.php';


        if (!is_file($model)) {
            return false;
        }

        require_once EWEI_SHOPV2_CORE . "inc/com_model.php";
        require_once $model;
        $class_name = ucfirst($name) . '_EweiShopV2ComModel';
        $_coms[$name] = new $class_name($name);
        if ($name == 'perm') {

            return $_coms[$name];
        }

        if (com('perm')->check_com($name)) {
            return $_coms[$name];
        }
        return false;

    }
}

//运行插件方法
if (!function_exists('com_run')) {
    function com_run($name = '')
    {
        $names = explode("::", $name);
        $com = com($names[0]);
        if (!$com) {
            return false;
        }
        if (!method_exists($com, $names[1])) {
            return false;
        }

        $func_args = func_get_args();
        $args = array_splice($func_args, 1);
        return call_user_func_array(array($com, $names[1]), $args);
    }
}

if (!function_exists('byte_format')) {
    function byte_format($input, $dec = 0)
    {
        $prefix_arr = array(' B', 'K', 'M', 'G', 'T');
        $value = round($input, $dec);
        $i = 0;
        while ($value > 1024) {
            $value /= 1024;
            $i++;
        }
        $return_str = round($value, $dec) . $prefix_arr[$i];
        return $return_str;
    }
}

if (!function_exists('is_array2')) {
    function is_array2($array)
    {
        if (is_array($array)) {
            foreach ($array as $k => $v) {
                return is_array($v);
            }
            return false;
        }
        return false;
    }
}

/**
 * 设置图片路径
 * @param type $list
 * @param type $fields
 * @return type
 */
if (!function_exists('set_medias')) {
    function set_medias($list = array(), $fields = null)
    {

        if (empty($list)) {
            return '';
        }

        if (empty($fields)) {
            foreach ($list as &$row) {
                $row = tomedia($row);
            }
            return $list;
        }
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        if (is_array2($list)) {

            foreach ($list as $key => &$value) {

                foreach ($fields as $field) {
                    if (isset($list[$field])) {
                        $list[$field] = tomedia($list[$field]);
                    }
                    if (is_array($value) && isset($value[$field])) {
                        $value[$field] = tomedia($value[$field]);
                    }
                }
            }
            return $list;
        } else {
            foreach ($fields as $field) {
                if (isset($list[$field])) {
                    $list[$field] = tomedia($list[$field]);
                }
            }
            return $list;
        }
    }
}

/**
 * 获取月份最后一天
 */
if (!function_exists('get_last_day')) {
    function get_last_day($year, $month)
    {
        return date('t', strtotime("{$year}-{$month} -1"));
    }

}

if (!function_exists('show_message')) {
    function show_message($msg = '', $url = '', $type = '')
    {
        $site = new Page();
        $site->message($msg, $url, $type);
        exit;
    }
}

/**
 * 返回JSON格式数据
 * @param type $status
 * @param type $return
 */
if (!function_exists('show_json')) {
    function show_json($status = 1, $return = null)
    {
        $ret = array(
            'status' => $status,
            'result' => $status == 1 ? array('url' => referer()) : array()
        );

        if (!is_array($return)) {
            if ($return) {
                $ret['result']['message'] = $return;
            }
            die(json_encode($ret));
        } else {
            $ret['result'] = $return;
        }

        if (isset($return['url'])) {
            $ret['result']['url'] = $return['url'];
        } else if ($status == 1) {
            $ret['result']['url'] = referer();
        }
        die(json_encode($ret));
    }
}

if (!function_exists('is_weixin')) {
    function is_weixin()
    {
        if (EWEI_SHOPV2_DEBUG) {
            return true;
        }
        if (empty($_SERVER['HTTP_USER_AGENT']) || strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false && strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') === false) {
            return false;
        }
        return true;
    }
}

if (!function_exists('is_h5app')) {
    function is_h5app()
    {
        if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'CK 2.0')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('is_ios')) {
    function is_ios()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            return true;
        }
        return false;
    }
}

if (!function_exists('is_mobile')) {
    function is_mobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            return true;
        }
        return false;
    }
}
if(!function_exists('is_qyweixin')){
    function is_qyweixin(){
        if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'wxwork') != false){
            return true;
        }
        return false;
    }
}
if (!function_exists('b64_encode')) {
    function b64_encode($obj)
    {
        if (is_array($obj)) {
            return urlencode(base64_encode(json_encode($obj)));
        }
        return urlencode(base64_encode($obj));
    }
}

if (!function_exists('b64_decode')) {
    function b64_decode($str, $is_array = true)
    {
        $str = base64_decode(urldecode($str));
        if ($is_array) {
            return json_decode($str, true);
        }
        return $str;
    }
}

if (!function_exists('create_image')) {
    function create_image($img)
    {
        $ext = strtolower(substr($img, strrpos($img, '.')));
        if ($ext == '.png') {
            $thumb = imagecreatefrompng($img);
        } else if ($ext == '.gif') {
            $thumb = imagecreatefromgif($img);
        } else {
            $thumb = imagecreatefromjpeg($img);
        }
        return $thumb;
    }
}

if (!function_exists('get_authcode')) {
    function get_authcode()
    {
        $auth = get_auth();
        return empty($auth['code']) ? '' : $auth['code'];
    }
}

if (!function_exists('get_auth')) {
    function get_auth()
    {
        global $_W;
        $set = pdo_fetch('select sets from ' . tablename('ewei_shop_sysset') . ' order by id asc limit 1');
        $sets = iunserializer($set['sets']);
        if (is_array($sets)) {
            return is_array($sets['auth']) ? $sets['auth'] : array();
        }
        return array();
    }
}

if (!function_exists('rc')) {
    function rc($plugin = '')
    {

        global $_W, $_GPC;
        $domain = trim(preg_replace("/http(s)?:\/\//", "", rtrim($_W['siteroot'], "/")));
        $ip = gethostbyname($_SERVER['HTTP_HOST']);
        $setting = setting_load('site');
        $id = isset($setting['site']['key']) ? $setting['site']['key'] : '0';
        $auth = get_auth();
        load()->func('communication');
        $resp = ihttp_request(EWEI_SHOPV2_AUTH_URL, array('ip' => $ip, 'id' => $id, 'code' => $auth['code'], 'domain' => $domain, 'plugin' => $plugin), null, 1);
        $result = @json_decode($resp['content'], true);
        if (!empty($result['status'])) {
            return true;
        }
        return false;
    }
}

if (!function_exists('shop_template_compile')) {
    function shop_template_compile($from, $to, $inmodule = false)
    {
        $path = dirname($to);
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        $content = shop_template_parse(file_get_contents($from), $inmodule);
        if (IMS_FAMILY == 'x' && !preg_match('/(footer|header|account\/welcome|login|register)+/', $from)) {
            $content = str_replace('微擎', '系统', $content);
        }
        file_put_contents($to, $content);
    }
}

if (!function_exists('shop_template_parse')) {
    function shop_template_parse($str, $inmodule = false)
    {
        global $_W;
        $str = template_parse($str, $inmodule);
        if (strexists($_W['siteurl'], 'merchant.php')) {
            //多商户分权
            if (p('merch')) {
                $str = preg_replace('/{ifp\s+(.+?)}/', '<?php if(mcv($1)) { ?>', $str);
                $str = preg_replace('/{ifpp\s+(.+?)}/', '<?php if(mcp($1)) { ?>', $str);
                $str = preg_replace('/{ife\s+(\S+)\s+(\S+)}/', '<?php if( mce($1 ,$2) ) { ?>', $str);
                return $str;
            }
        }

        //分权
        $str = preg_replace('/{ifp\s+(.+?)}/', '<?php if(cv($1)) { ?>', $str);
        $str = preg_replace('/{ifpp\s+(.+?)}/', '<?php if(cp($1)) { ?>', $str);
        $str = preg_replace('/{ife\s+(\S+)\s+(\S+)}/', '<?php if( ce($1 ,$2) ) { ?>', $str);

        return $str;
    }
}

if (!function_exists('ce')) {
    function ce($permtype = '', $item = null)
    {
        $perm = com_run('perm::check_edit', $permtype, $item);
        return $perm;
    }
}

if (!function_exists('cv')) {
    function cv($permtypes = '')
    {
        $perm = com_run('perm::check_perm', $permtypes);
        return $perm;
    }
}

if (!function_exists('ca')) {
    function ca($permtypes = '')
    {
        global $_W;
        $err = '您没有权限操作，请联系管理员!';
        if (!cv($permtypes)) {
            if ($_W['isajax']) {
                show_json(0, $err);
            }
            show_message($err, '', 'error');
        }
    }
}

if (!function_exists('cp')) {
    function cp($pluginname = '')
    {
        $perm = com('perm');
        if ($perm) {
            return $perm->check_plugin($pluginname);
        }
        return true;
    }
}

if (!function_exists('cpa')) {
    function cpa($pluginname = '')
    {
        if (!cp($pluginname)) {
            show_message('您没有权限操作，请联系管理员!', '', 'error');
        }
    }
}

if (!function_exists('plog')) {
    function plog($type = '', $op = '')
    {
        com_run('perm::log', $type, $op);
    }
}

if (!function_exists('tpl_selector')) {
    function tpl_selector($name, $options = array())
    {

        $options['multi'] = intval($options['multi']);

        $options['buttontext'] = isset($options['buttontext']) ? $options['buttontext'] : '请选择';

        $options['items'] = isset($options['items']) && $options['items'] ? $options['items'] : array();


        $options['readonly'] = isset($options['readonly']) ? $options['readonly'] : true;
        $options['callback'] = isset($options['callback']) ? $options['callback'] : '';
        $options['key'] = isset($options['key']) ? $options['key'] : 'id';
        $options['text'] = isset($options['text']) ? $options['text'] : 'title';
        $options['thumb'] = isset($options['thumb']) ? $options['thumb'] : 'thumb';
        $options['preview'] = isset($options['preview']) ? $options['preview'] : true;
        $options['type'] = isset($options['type']) ? $options['type'] : 'image';
        $options['input'] = isset($options['input']) ? $options['input'] : true;
        $options['required'] = isset($options['required']) ? $options['required'] : false;
        $options['nokeywords'] = isset($options['nokeywords']) ? $options['nokeywords'] : 0;
        $options['placeholder'] = isset($options['placeholder']) ? $options['placeholder'] : '请输入关键词';
        $options['autosearch'] = isset($options['autosearch']) ? $options['autosearch'] : 0;
        if (empty($options['items'])) {
            $options['items'] = array();
        } else if (!is_array2($options['items'])) {
            $options['items'] = array($options['items']);
        }
        $options['name'] = $name;

        $titles = '';
        foreach ($options['items'] as $item) {
            $titles .= $item[$options['text']];
            if (count($options['items']) > 1) {
                $titles .= "; ";
            }
        }

        $options['value'] = isset($options['value']) ? $options['value'] : $titles;

        $readonly = $options['readonly'] ? 'readonly' : '';
        $required = $options['required'] ? ' data-rule-required="true"' : '';
        $callback = !empty($options['callback']) ? ", {$options['callback']}" : '';
        $id = $options['multi'] ? "{$name}[]" : $name;
        $html = "<div id='{$name}_selector' class='selector' 
                     data-type=\"{$options['type']}\"
                     data-key=\"{$options['key']}\"
                     data-text=\"{$options['text']}\"
                     data-thumb=\"{$options['thumb']}\"
                     data-multi=\"{$options['multi']}\"
                     data-callback=\"{$options['callback']}\"
                     data-url=\"{$options['url']}\",
                     data-nokeywords=\"{$options['nokeywords']}\"
                  data-autosearch=\"{$options['autosearch']}\"

                 >";
        if ($options['input']) {
            $html .= "<div class='input-group'>"
                . "<input type='text' id='{$name}_text' name='{$name}_text'  value='{$options['value']}' class='form-control text'  {$readonly}  {$required}/>"
                . "<div class='input-group-btn'>";
        }
        $html .= "<button class='btn btn-primary' type='button' onclick='biz.selector.select(" . json_encode($options) . ");'>{$options['buttontext']}</button>";
        if ($options['input']) {
            $html .= "</div>";
            $html .= "</div>";
        }


        $show = $options['preview'] ? '' : " style='display:none'";
        if ($options['type'] == 'image') {
            $html .= "<div class='input-group multi-img-details container' $show>";
        } else {
            $html .= "<div class='input-group multi-audio-details container' $show>";
        }

        foreach ($options['items'] as $item) {
            if ($options['type'] == 'image') {
                $html .= "<div class='multi-item' data-{$options['key']}='{$item[$options['key']]}' data-name='{$name}'>
                                      <img class='img-responsive img-thumbnail' src='" . tomedia($item[$options['thumb']]) . "'>
                                      <div class='img-nickname'>{$item[$options['text']]}</div>
                                     <input type='hidden' value='{$item[$options['key']]}' name='{$id}'>
                                     <em onclick='biz.selector.remove(this,\"{$name}\")'  class='close'>×</em>
                            <div style='clear:both;'></div>
                         </div>";
            } else {
                $html .= "<div class='multi-audio-item ' data-{$options['key']}='{$item[$options['key']]}' >
                       <div class='input-group'>
                       <input type='text' class='form-control img-textname' readonly='' value='{$item[$options['text']]}'>
                       <input type='hidden'  value='{$item[$options['key']]}' name='{$id}'> 
                       <div class='input-group-btn'><button class='btn btn-default' onclick='biz.selector.remove(this,\"{$name}\")' type='button'><i class='fa fa-remove'></i></button>
                       </div></div></div>";
            }
        }
        $html .= "</div></div>";

        return $html;
    }
}
//商品搜索新控件
if (!function_exists('tpl_selector_new')) {
    function tpl_selector_new($name, $options = array())
    {
        $options['multi'] = intval($options['multi']);
        $options['buttontext'] = isset($options['buttontext']) ? $options['buttontext'] : '请选择';
        $options['items'] = isset($options['items']) && $options['items'] ? $options['items'] : array();
        $options['readonly'] = isset($options['readonly']) ? $options['readonly'] : true;
        $options['callback'] = isset($options['callback']) ? $options['callback'] : '';
        $options['key'] = isset($options['key']) ? $options['key'] : 'id';
        $options['text'] = isset($options['text']) ? $options['text'] : 'title';
        $options['thumb'] = isset($options['thumb']) ? $options['thumb'] : 'thumb';
        $options['preview'] = isset($options['preview']) ? $options['preview'] : true;
        $options['type'] = isset($options['type']) ? $options['type'] : 'image';
        $options['input'] = isset($options['input']) ? $options['input'] : true;
        $options['optiontype']=isset($options['optiontype']) ? $options['optiontype'] : 1;
        $options['required'] = isset($options['required']) ? $options['required'] : false;
        $options['nokeywords'] = isset($options['nokeywords']) ? $options['nokeywords'] : 0;
        $options['placeholder'] = isset($options['placeholder']) ? $options['placeholder'] : '请输入关键词';
        $options['text'] = isset($options['text']) ? $options['text'] : '价格/分销佣金';
        $options['autosearch'] = isset($options['autosearch']) ? $options['autosearch'] : 0;
        $options['optionurl'] = isset($options['optionurl']) ? $options['optionurl'] : '';
        $options['selectorid'] = isset($options['selectorid']) ? $options['selectorid'] : '';
        if (empty($options['items'])) {
            $options['items'] = array();
        } else if (!is_array2($options['items'])) {
            $options['items'] = array($options['items']);
        }
        $options['name'] = $name;

        $titles = '';
        foreach ($options['items'] as $item) {
            $titles .= $item[$options['text']];
            if (count($options['items']) > 1) {
                $titles .= "; ";
            }
        }

        $options['value'] = isset($options['value']) ? $options['value'] : $titles;

        $readonly = $options['readonly'] ? 'readonly' : '';
        $required = $options['required'] ? ' data-rule-required="true"' : '';
        $callback = !empty($options['callback']) ? ", {$options['callback']}" : '';
        $id = $options['multi'] ? "{$name}[]" : $name;
        $html = "<div id='{$name}_selector' class='selector'
                     data-type=\"{$options['type']}\"
                     data-key=\"{$options['key']}\"
                     data-text=\"{$options['text']}\"
                     data-thumb=\"{$options['thumb']}\"
                     data-multi=\"{$options['multi']}\"
                     data-callback=\"{$options['callback']}\"
                     data-url=\"{$options['url']}\",
                     data-nokeywords=\"{$options['nokeywords']}\" 
                     data-autosearch=\"{$options['autosearch']}\"
                     data-optionurl=\"{$options['optionurl']}\"
                     data-selectorid=\"{$options['selectorid']}\"
 
                 >";
        if ($options['input']) {
            $html .= "<div class='input-group'>"
                . "<input type='text' id='{$name}_text' name='{$name}_text'  value='{$options['value']}' class='form-control text'  {$readonly}  {$required}/>"
                . "<div class='input-group-btn'>";
        }
        $html .= "<button class='btn btn-primary' type='button' onclick='biz.selector_new.select(" . json_encode($options) . ");'>{$options['buttontext']}</button>";
        if ($options['input']) {
            $html .= "</div>";
            $html .= "</div>";
        }


        $show = $options['preview'] ? '' : " style='display:none'";

        if ($options['type'] == 'image') {
            $html .= "<div class='input-group multi-img-details container' $show>";
        } else {


            $html .= "<div class='input-group multi-audio-details container' $show>
<table class='table' style='width:600px;'>
                    <thead>
                        <tr>
                            <th style='width:80px;'>商品名称</th>
                            <th style='width:220px;'></th>
                            <th>".$options['text']."</th>
                            <th style='width:50px;'>操作</th>
                        </tr>
                    </thead>
                    <tbody id='param-items{$options['selectorid']}' class='ui-sortable'>";
        }

        foreach ($options['items'] as $item) {
            if ($options['type'] == 'image') {
                $html .= "<div class='multi-item' data-{$options['key']}='{$item[$options['key']]}' data-name='{$name}'>
                                      <img class='img-responsive img-thumbnail' src='" . tomedia($item[$options['thumb']]) . "'>
                                      <div class='img-nickname'>{$item[$options['text']]}</div>
                                     <input type='hidden' value='{$item[$options['key']]}' name='{$id}'>
                                     <em onclick='biz.selector_new.remove(this,\"{$name}\")'  class='close'>×</em>
                         </div>";
            } elseif ($options['type'] == 'product') {
                if ($item['optiontitle']) {
                    $optiontitle = $item['optiontitle'][0]['title'] . "...";
                } else {
                    $optiontitle = "&yen;" . $item['packageprice'];
                }

                $html .= "
                    <tr class='multi-product-item' data-{$options['key']}='{$item['goodsid']}' >
                        <input type='hidden' class='form-control img-textname' readonly='' value='{$item[$options['text']]}'>
                       <input type='hidden'  value='{$item['goodsid']}' name='{$id}'>
                        <td style='width:80px;'>
                            <img src='" . tomedia($item[$options['thumb']]) . "' style='width:70px;border:1px solid #ccc;padding:1px'>
                        </td>
                        <td style='width:220px;'>{$item[$options['text']]}</td>
                        <td>";
                $optionurl = empty($options['optionurl']) ? 'sale/package/hasoption' : str_replace(".", "/", $options['optionurl']);
                if ($item['optiontitle']) {
                            $html .= "<a class='btn btn-default btn-sm' data-toggle='ajaxModal' href='" . webUrl($optionurl,
                                array('goodsid' => $item['goodsid'], 'pid' => $item['pid'], 'selectorid' => $options['selectorid'])) . "' id='{$options['selectorid']}optiontitle" . $item['goodsid'] . "'>" . $optiontitle . "</a>
                                <input type='hidden' id='{$options['selectorid']}packagegoods" . $item['goodsid'] . "' value='" . $item['option'] . "' name='{$options['selectorid']}packagegoods[" . $item['goodsid'] . "]'>";
                        foreach ($item['optiontitle'] as $option) {
                            $total = isset($option['total']) ? "," . $option['total'] : '';
                            $maxbuy = isset($option['maxbuy']) ? "," . $option['maxbuy'] : '';
                            $totalmaxbuy = isset($option['totalmaxbuy']) ? "," . $option['totalmaxbuy'] : '';

                            $html .= "<input type='hidden' value='" . $option['packageprice'] . "," . $option['commission1'] . "," . $option['commission2'] . "," . $option['commission3'] . "{$total}{$maxbuy}{$totalmaxbuy}'
                            name='{$options['selectorid']}packagegoodsoption" . $option['optionid'] . "' >";
                        }
                } else {

                    $total = isset($item['total']) ? "," . $item['total'] : '';
                    $maxbuy = isset($item['maxbuy']) ? "," . $item['maxbuy'] : '';
                    $totalmaxbuy = isset($item['totalmaxbuy']) ? "," . $item['totalmaxbuy'] : '';
                     if($options['optiontype']==1){
                        $html .= "<a class='btn btn-default btn-sm' data-toggle='ajaxModal' href='" . webUrl($optionurl,
                                array('goodsid' => $item['goodsid'], 'pid' => $item['pid'], 'selectorid' => $options['selectorid'])) . "' id='{$options['selectorid']}optiontitle" . $item['goodsid'] . "'>&yen;" . $item['packageprice'] . "</a>
                                <input type='hidden' id='{$options['selectorid']}packagegoods" . $item['goodsid'] . "' value='' name='{$options['selectorid']}packagegoods[" . $item['goodsid'] . "]'>
                        <input type='hidden' value='" . $item['packageprice'] . "," . $item['commission1'] . "," . $item['commission2'] . "," . $item['commission3'] . "{$total}{$maxbuy}{$totalmaxbuy}' name='{$options['selectorid']}packgoods" . $item['goodsid'] . "' >";
                    }elseif($options['optiontype']==2){
                        $html .= "{$item['num']}<input type='hidden' id='{$options['selectorid']}packagegoods" . $item['goodsid'] . "' value='{$item['value']}' name='{$options['selectorid']}packagegoods[" . $item['goodsid'] . "]'>
                        ";
                    }
                }
                $html .= "
                        </td>
                        <td><a href='javascript:void(0);' class='btn btn-default btn-sm' onclick='biz.selector_new.remove(this,\"{$name}\")' title='删除'>
                        <i class='fa fa-times'></i></a></td>
                    </tr>";
            } else {
                $html .= "<div class='multi-audio-item ' data-{$options['key']}='{$item[$options['c']]}' >
                       <div class='input-group'>
                       <input type='text' class='form-control img-textname' readonly='' value='{$item[$options['text']]}'>
                       <input type='hidden'  value='{$item[$options['key']]}' name='{$id}'>
                       <div class='input-group-btn'><button class='btn btn-default' onclick='biz.selector_new.remove(this,\"{$name}\")' type='button'><i class='fa fa-remove'></i></button>
                       </div></div></div>";
            }
        }
        $html .= "</tbody>
                </table></div></div>";

        return $html;
    }
}


if (!function_exists('tpl_daterange')) {
    function tpl_daterange($name, $value = array(), $time = false)
    {
        global $_GPC;
        $placeholder = isset($value['placeholder']) ? $value['placeholder'] : '';
        $s = '';

        if (empty($time) && !defined('TPL_INIT_DATERANGE_DATE')) {
            $s = '
<script type="text/javascript">
	require(["daterangepicker"], function($){
		$(function(){
			$(".daterange.daterange-date").each(function(){
         
				var elm = this;
                                        var container =$(elm).parent().prev(); 
				$(this).daterangepicker({
					 
					format: "YYYY-MM-DD"
				}, function(start, end){
					$(elm).find(".date-title").html(start.toDateStr() + " 至 " + end.toDateStr());
					container.find(":input:first").val(start.toDateTimeStr());
					container.find(":input:last").val(end.toDateTimeStr());
				});
			});
		});
	});
</script> 
';
            define('TPL_INIT_DATERANGE_DATE', true);
        }

        if (!empty($time) && !defined('TPL_INIT_DATERANGE_TIME')) {
            $s = '
<script type="text/javascript">
	require(["daterangepicker"], function($){
		$(function(){
			$(".daterange.daterange-time").each(function(){
               
				var elm = this;
                                       var container =$(elm).parent().prev(); 
				$(this).daterangepicker({
				
					format: "YYYY-MM-DD HH:mm",
					timePicker: true,
					timePicker12Hour : false,
					timePickerIncrement: 1,
					minuteStep: 1
				}, function(start, end){
					$(elm).find(".date-title").html(start.toDateTimeStr() + " 至 " + end.toDateTimeStr());
					container.find(":input:first").val(start.toDateTimeStr());
					container.find(":input:last").val(end.toDateTimeStr());
				});
			});
		});
	});
     function clearTime(obj){
         
              $(obj).prev().html("<span class=date-title>" + $(obj).attr("placeholder") + "</span>");
              $(obj).parent().prev().find("input").val("");
    }
</script>
';
            define('TPL_INIT_DATERANGE_TIME', true);
        }

        $str = $placeholder;
        $small = isset($value['sm']) ? $value['sm'] : true;
        $value['starttime'] = isset($value['starttime']) ? $value['starttime'] : ($_GPC[$name]['start'] ? $_GPC[$name]['start'] : '');
        $value['endtime'] = isset($value['endtime']) ? $value['endtime'] : ($_GPC[$name]['end'] ? $_GPC[$name]['end'] : '');

        if ($value['starttime'] && $value['endtime']) {
            if (empty($time)) {
                $str = date('Y-m-d', strtotime($value['starttime'])) . "至 " . date('Y-m-d', strtotime($value['endtime']));
            } else {
                $str = date('Y-m-d H:i', strtotime($value['starttime'])) . " 至 " . date('Y-m-d  H:i', strtotime($value['endtime']));
            }
        }
        $s .= '<div style="float:left">
	<input name="' . $name . '[start]' . '" type="hidden" value="' . $value['starttime'] . '" />
	<input name="' . $name . '[end]' . '" type="hidden" value="' . $value['endtime'] . '" />
           </div>
          <div class="btn-group ' . ($small ? 'btn-group-sm' : '') . '" style="padding-right:0;"  >
          
	<button style="width:240px" class="btn btn-default daterange ' . (!empty($time) ? 'daterange-time' : 'daterange-date') . '"  type="button"><span class="date-title">' . $str . '</span></button>
        <button class="btn btn-default ' . ($small ? 'btn-sm' : '') . '" " type="button" onclick="clearTime(this)" placeholder="' . $placeholder . '"><i class="fa fa-remove"></i></button>
         </div>
	';
        return $s;
    }
}

if (!function_exists('mobileUrl')) {
    function mobileUrl($do = '', $query = null, $full = false)
    {
        global $_W, $_GPC;

        !$query && $query = array();
        $dos = explode('/', trim($do));

        $routes = array();

        $routes[] = $dos[0];
        if (isset($dos[1])) {
            $routes[] = $dos[1];
        }
        if (isset($dos[2])) {
            $routes[] = $dos[2];
        }
        if (isset($dos[3])) {
            $routes[] = $dos[3];
        }

        $r = implode(".", $routes);
        if (!empty($r)) {
            $query = array_merge(array('r' => $r), $query);
        }
        $query = array_merge(array('do' => 'mobile'), $query);
        $query = array_merge(array('m' => "ewei_shopv2"), $query);

        if (empty($query['mid'])) {
            $mid = intval($_GPC['mid']);
            if (!empty($mid)) {
                $query['mid'] = $mid;
            }

            if (!empty($_W['openid']) && !is_weixin() && !is_h5app()) {
                $myid = m('member')->getMid();
                if (!empty($myid)) {
                    $member = pdo_fetch("select id,isagent,status from" . tablename('ewei_shop_member') . 'where id=' . $myid);
                    if (!empty($member['isagent']) && !empty($member['status'])) {
                        $query['mid'] = $member['id'];
                    }
                }
            }
        }

        if (empty($query['merchid'])) {
            $merchid = intval($_GPC['merchid']);
            if (!empty($merchid)) {
                $query['merchid'] = $merchid;
            }
        } else {
            if ($query['merchid'] < 0) {
                unset($query['merchid']);
            }
        }
        if ($full) {
            return $_W['siteroot'] . 'app/' . substr(murl('entry', $query, true), 2);
        }
        return murl('entry', $query, true);
    }
}

if (!function_exists('webUrl')) {
    function webUrl($do = '', $query = array(), $full = true)
    {
        global $_W, $_GPC;

        if (!empty($_W['plugin'])) {
            if ($_W['plugin'] == 'merch') {
                if (function_exists('merchUrl')) {
                    return merchUrl($do, $query, $full);
                }
            }
        }

        $dos = explode('/', trim($do));

        $routes = array();
        $routes[] = $dos[0];
        if (isset($dos[1])) {
            $routes[] = $dos[1];
        }
        if (isset($dos[2])) {
            $routes[] = $dos[2];
        }
        if (isset($dos[3])) {
            $routes[] = $dos[3];
        }
        $r = implode(".", $routes);
        if (!empty($r)) {
            $query = array_merge(array('r' => $r), $query);
        }

        $query = array_merge(array('do' => 'web'), $query);
        $query = array_merge(array('m' => "ewei_shopv2"), $query);
        if ($full) {
            return $_W['siteroot'] . 'web/' . substr(wurl('site/entry', $query), 2);
        }
        return wurl('site/entry', $query);
    }
}


/**
 * 有格式展示查看var_dump内容
 * by:Yang
 */
if (!function_exists('dump')) {
    function dump()
    {
        $args = func_get_args();
        foreach ($args as $val) {
            echo '<pre style="color: red">';
            var_dump($val);
            echo '</pre>';
        }
    }
}

if (!function_exists('my_scandir')) {
    $my_scenfiles = array();
    function my_scandir($dir)
    {
        global $my_scenfiles;
        if ($handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if ($file != ".." && $file != ".") {
                    if (is_dir($dir . "/" . $file)) {
                        my_scandir($dir . "/" . $file);
                    } else {
                        $my_scenfiles[] = $dir . "/" . $file;
                    }
                }
            }
            closedir($handle);
        }
    }
}

if (!function_exists('cut_str')) {
    function cut_str($string, $sublen, $start = 0, $code = 'UTF-8')
    {
        if ($code == 'UTF-8') {
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
            preg_match_all($pa, $string, $t_string);
            if (count($t_string[0]) - $start > $sublen)
                return join('', array_slice($t_string[0], $start, $sublen));
            return join('', array_slice($t_string[0], $start, $sublen));
        } else {
            $start = $start * 2;
            $sublen = $sublen * 2;
            $strlen = strlen($string);
            $tmpstr = '';
            for ($i = 0; $i < $strlen; $i++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    if (ord(substr($string, $i, 1)) > 129) {
                        $tmpstr .= substr($string, $i, 2);
                    } else {
                        $tmpstr .= substr($string, $i, 1);
                    }
                }
                if (ord(substr($string, $i, 1)) > 129)
                    $i++;
            }
            return $tmpstr;
        }
    }
}

if (!function_exists('save_media')) {
    function save_media($url, $enforceQiniu = false)
    {
        global $_W;
        //保存媒体 同时开OSS和七牛，商城优先七牛，其他不管，不使用七牛则使用系统
        static $com;
        if (!$com) {
            $com = com('qiniu');
        }

        if ($com) {
            $qiniu_url = $com->save($url, null, $enforceQiniu);
            if (!empty($qiniu_url)) {
                return $qiniu_url;
            }
        }

        $ext = strrchr($url, ".");
        if ($ext != ".jpeg" && $ext != ".gif" && $ext != ".jpg" && $ext != ".png") {
            return $url;
        }
        if (!empty($_W['setting']['remote']['type']) && !empty($url) && !(strexists($url, 'http:') || strexists($url, 'https:'))) {
            if (is_file(ATTACHMENT_ROOT . $url)) {
                load()->func('file');
                $remotestatus = file_remote_upload($url, false);
                if (!is_error($remotestatus)) {
                    $remoteurl = $_W['attachurl_remote'] . $url;  // 远程图片的访问URL
                    return $remoteurl;
                }
            }
        }
        return $url;
    }
}

if (!function_exists('tpl_form_field_category_3level')) {
    function tpl_form_field_category_3level($name, $parents, $children, $parentid, $childid, $thirdid)
    {
        $html = '
<script type="text/javascript">
	window._' . $name . ' = ' . json_encode($children) . ';
</script>';
        if (!defined('TPL_INIT_CATEGORY_THIRD')) {
            $html .= '	
<script type="text/javascript">
	  function renderCategoryThird(obj, name){
		var index = obj.options[obj.selectedIndex].value;
		require([\'jquery\', \'util\'], function($, u){
			$selectChild = $(\'#\'+name+\'_child\');
                                                      $selectThird = $(\'#\'+name+\'_third\');
			var html = \'<option value="0">请选择二级分类</option>\';
                                                      var html1 = \'<option value="0">请选择三级分类</option>\';
			if (!window[\'_\'+name] || !window[\'_\'+name][index]) {
				$selectChild.html(html); 
                                                                        $selectThird.html(html1);
				return false;
			}
			for(var i=0; i< window[\'_\'+name][index].length; i++){
				html += \'<option value="\'+window[\'_\'+name][index][i][\'id\']+\'">\'+window[\'_\'+name][index][i][\'name\']+\'</option>\';
			}
			$selectChild.html(html);
                                                    $selectThird.html(html1);
		});
	}
        function renderCategoryThird1(obj, name){
		var index = obj.options[obj.selectedIndex].value;
		require([\'jquery\', \'util\'], function($, u){
			$selectChild = $(\'#\'+name+\'_third\');
			var html = \'<option value="0">请选择三级分类</option>\';
			if (!window[\'_\'+name] || !window[\'_\'+name][index]) {
				$selectChild.html(html);
				return false;
			}
			for(var i=0; i< window[\'_\'+name][index].length; i++){
				html += \'<option value="\'+window[\'_\'+name][index][i][\'id\']+\'">\'+window[\'_\'+name][index][i][\'name\']+\'</option>\';
			}
			$selectChild.html(html);
		});
	}
</script>
			';
            define('TPL_INIT_CATEGORY_THIRD', true);
        }

        $html .=
            '<div class="row row-fix tpl-category-container">
	<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
		<select class="form-control tpl-category-parent" id="' . $name . '_parent" name="' . $name . '[parentid]" onchange="renderCategoryThird(this,\'' . $name . '\')">
			<option value="0">请选择一级分类</option>';
        $ops = '';
        foreach ($parents as $row) {
            $html .= '
			<option value="' . $row['id'] . '" ' . (($row['id'] == $parentid) ? 'selected="selected"' : '') . '>' . $row['name'] . '</option>';
        }
        $html .= '
		</select>
	</div>
	<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
		<select class="form-control tpl-category-child" id="' . $name . '_child" name="' . $name . '[childid]" onchange="renderCategoryThird1(this,\'' . $name . '\')">
			<option value="0">请选择二级分类</option>';
        if (!empty($parentid) && !empty($children[$parentid])) {
            foreach ($children[$parentid] as $row) {
                $html .= '
			<option value="' . $row['id'] . '"' . (($row['id'] == $childid) ? 'selected="selected"' : '') . '>' . $row['name'] . '</option>';
            }
        }
        $html .= '
		</select> 
	</div> 
                  <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
		<select class="form-control tpl-category-child" id="' . $name . '_third" name="' . $name . '[thirdid]">
			<option value="0">请选择三级分类</option>';
        if (!empty($childid) && !empty($children[$childid])) {
            foreach ($children[$childid] as $row) {
                $html .= '
			<option value="' . $row['id'] . '"' . (($row['id'] == $thirdid) ? 'selected="selected"' : '') . '>' . $row['name'] . '</option>';
            }
        }
        $html .= '</select>
	</div>
</div>';
        return $html;
    }
}

if (!function_exists('array_column')) {
    function array_column($input, $column_key, $index_key = null)
    {
        $arr = array();
        foreach ($input as $d) {
            if (!isset($d[$column_key])) {
                return null;
            }
            if ($index_key !== null) {
                return array($d[$index_key] => $d[$column_key]);
            }
            $arr[] = $d[$column_key];
        }
        if ($index_key !== null) {
            $tmp = array();
            foreach ($arr as $ar) {
                $tmp[key($ar)] = current($ar);
            }
            $arr = $tmp;
        }
        return $arr;
    }
}

if (!function_exists('is_utf8')) {
    function is_utf8($str)
    {
        return preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs', $str);
    }
}
if (!function_exists('price_format')) {

    function price_format($price)
    {
        $prices = explode('.', $price);
        if (intval($prices[1]) <= 0) {
            $price = $prices[0];
        } else {
            if (isset($prices[1][1]) && $prices[1][1] <= 0) {
                $price = $prices[0] . '.' . $prices[1][0];
            }
        }
        return $price;
    }
}

if (!function_exists("redis")) {


    /**
     * 获取 Redis 实例
     * @return null|Redis
     */
    function redis()
    {

        global $_W;

        static $redis;

        if (is_null($redis)) {

            if (!extension_loaded('redis')) {
                return error(-1, 'PHP 未安装 redis 扩展');
            }
            if (!isset($_W['config']['setting']['redis'])) {
                return error(-1, '未配置 redis, 请检查 data/config.php 中参数设置');
            }

            $config = $_W['config']['setting']['redis'];

            if (empty($config['server'])) {
               $config['server'] = '127.0.0.1';
            }
            if (empty($config['port'])) {
                $config['port'] = '6379';
            }
            $redis_temp = new Redis();

            if ($config['pconnect']) {
                $connect = $redis_temp->pconnect($config['server'], $config['port'], $config['timeout']);
            } else {
                $connect = $redis_temp->connect($config['server'], $config['port'], $config['timeout']);
            }
            if (!$connect) {
                return error(-1, "redis 连接失败, 请检查 data/config.php 中参数设置");
            }
            if (!empty($config['requirepass'])) {
                $redis_temp->auth($config['requirepass']);
            }

            $ping = $redis_temp->ping();
            if ($ping != '+PONG') {
                return error(-1, "redis 无法正常工作，请检查 redis 服务");
            }
            $redis = $redis_temp;
        }
        return $redis;

    }


}

if (!function_exists("logg")) {
    /**
     * 在网站根目录创建log
     */
    function logg($name,$data)
    {
        global $_W;
        $data = is_array($data) ? json_encode($data,JSON_UNESCAPED_UNICODE) : $data;
        file_put_contents(IA_ROOT.'/'.$name,$data);
    }
}


/*wsq*/
