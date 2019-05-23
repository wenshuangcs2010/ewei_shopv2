<?php

if (!defined('IN_IA')) {
	exit('Access Denied');
}

class UnionModel extends PluginModel
{
	const APPLY = 'apply';
	const CHECKED = 'checked';
	const APPLY_CLEARING = 'apply_clearing';
	const PAY = 'pay';
	const PAY_CASHIER = 'pay_cashier';
	const PAY_CASHIER_USER = 'pay_cashier_user';

	static public $paytype = array(0 => '微信', 1 => '支付宝', 2 => '商城余额', 3 => '现金收款', 101 => '系统微信', 102 => '系统支付宝');
	public $setmeal = array('标准套餐', '豪华套餐');
	static public $UserSet = array();

	static public function perm()
	{
		$perm = array('index' => '我要收款', 'goods' => '商品收款', 'order' => '收款订单', 'statistics' => '收银统计', 'sysset' => '设置', 'sale' => '营销', 'clearing' => '提现', 'goodsmanage' => '商品管理');

		if (empty($_W['cashieruser']['can_withdraw'])) {
			unset($perm['clearing']);
		}

		return $perm;
	}

	public function show_json($code,$return){
        header('Content-Type:application/json; charset=utf-8');
        return show_json($code,$return);
	}
    //工会下线
    public function getUnionList($unionid){
        $members= pdo_fetchall("select * from ".tablename("ewei_shop_union_members")." where union_id=:union_id",array(':union_id'=>$unionid));
        $members=array(
            'count'=>count($members),
            'members'=>$members,
        );
        return $members;
    }
    public function egtUionMembercount($unionid,$conton){
		$newaddmembers=0;//新曾会员
		$newactivimember=0;//新申请的会员
	}

    /**
     * 根据文件后缀获取文件MINE
     * @param array $exts 文件后缀
     * @param array $mine 文件后缀MINE信息
     * @return string
     */
    public  function getFileMine($exts, $mine = [])
    {
        $mines = require_once EWEI_SHOPV2_PLUGIN . 'union/core/inc/mines.php';

        foreach (is_string($exts) ? explode(',', $exts) : $exts as $_e) {
            if (isset($mines[strtolower($_e)])) {
                $_exinfo = $mines[strtolower($_e)];
                $mine[] = is_array($_exinfo) ? join(',', $_exinfo) : $_exinfo;
            }
        }
        return join(',', $mine);
    }
    /**
     * 检查文件是否已经存在
     * @param string $filename
     * @param string|null $storage
     * @return bool
     */
    public  function hasFile($filename, $storage = null)
    {
        global $_W;

        switch (empty($storage) ? "local" : $storage) {
            case 'local':
                $path = IA_ROOT . '/' . $_W['config']['upload']['attachdir'];

                return file_exists($path . '/union/'.$_W['uniacid'] .'/'. $filename);
//            case 'qiniu':
//                $auth = new Auth(sysconf('storage_qiniu_access_key'), sysconf('storage_qiniu_secret_key'));
//                $bucketMgr = new BucketManager($auth);
//                list($ret, $err) = $bucketMgr->stat(sysconf('storage_qiniu_bucket'), $filename);
//                return $err === null;
//            case 'oss':
//                $ossClient = new OssClient(sysconf('storage_oss_keyid'), sysconf('storage_oss_secret'), self::getBaseUriOss(), true);
//                return $ossClient->doesObjectExist(sysconf('storage_oss_bucket'), $filename);
        }
        return false;
    }
    /**
     * 获取文件当前URL地址
     * @param string $filename
     * @param string|null $storage
     * @return bool|string
     */
    public  function getFileUrl($filename, $storage = null)
    {
        if ($this->hasFile($filename, $storage) === false) {
            return false;
        }
        switch (empty($storage) ? "local" : $storage) {
            case 'local':
                return $this->getBaseUriLocal() . $filename;
            case 'qiniu':
              //  return self::getBaseUriQiniu() . $filename;
            case 'oss':
              //  return self::getBaseUriOss() . $filename;
        }
        return false;
    }
    /**
     * 根据配置获取到七牛云文件上传目标地址
     * @return string
     */
    public static function getUploadLocalUrl()
    {
        return unionUrl('upfile/plugs/upload');
    }
    function json($data){
        header('Content-Type:application/json; charset=utf-8');
        die(json_encode($data));
    }
    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param mixed     $data 要返回的数据
     * @param integer   $code 返回的code
     * @param mixed     $msg 提示信息
     * @param string    $type 返回数据格式
     * @param array     $header 发送的Header信息
     * @return void
     */
    function result($data, $code = 0, $msg = '')
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => $_SERVER['REQUEST_TIME'],
            'data' => $data,
        ];
        header('Content-Type:application/json; charset=utf-8');
        die(json_encode($result));
    }
    /**
     * 获取服务器URL前缀
     * @return string
     */
    public static function getBaseUriLocal()
    {
        global $_W;
        //$src = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/' . $src;
        return $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/union/'.$_W['uniacid'] .'/';
    }
	public function getUserSet($name = '', $unionid)
	{
		global $_W;

		if (!isset(static::$UserSet[$unionid])) {
			$user = $this->userInfo($unionid);

			$set = (empty($user['set']) ? array() : json_decode($user['set'], true));
			static::$UserSet[$unionid] = $set;
		}

		if (empty($name)) {
			return static::$UserSet[$unionid];
		}

		return isset(static::$UserSet[$unionid][$name]) ? static::$UserSet[$unionid][$name] : '';
	}

	public function updateUserSet($data = array(), $unionid)
	{
		global $_W;
		$user = $this->userInfo($unionid);
		$set = (empty($user['set']) ? array() : json_decode($user['set'], true));
		$set = json_encode(array_merge($set, $data));
		return pdo_query('UPDATE ' . tablename('ewei_shop_union_user') . ' SET `set`=:set WHERE `uniacid` = :uniacid AND `id` = :id', array(':uniacid' => $_W['uniacid'], ':id' => $unionid, ':set' => $set));
	}





	public function savaUser(array $params, $diyform = array())
	{
		global $_W;
		$diyform_flag = 0;
		if (empty($params['title'])) {
			show_json(0, '请填写工会名称!');
		}

		if (empty($params['manageopenid'])) {
			show_json(0, '请填写管理微信号!');
		}

		if (empty($params['name'])) {
			show_json(0, '请填写联系人!');
		}

		if (empty($params['mobile'])) {
			show_json(0, '请填写联系电话!');
		}

		if (empty($params['username'])) {
			show_json(0, '请填写后台登录用户名!');
		}

		if (!empty($params['password'])) {
			$params['salt'] = random(8);
			$params['password'] = md5(trim($params['password']) . $params['salt']);
		}
		else {
			unset($params['password']);
		}

		$params['storeid'] = intval($params['storeid']);
		$params['merchid'] = intval($params['merchid']);
		$params['isopen_commission'] = intval($params['isopen_commission']);
		$params['title'] = trim($params['title']);
		$params['logo'] = trim($params['logo']);
		$params['openid'] = trim($params['openid']);
		$params['manageopenid'] = trim($params['manageopenid']);
		$params['name'] = trim($params['name']);
		$params['mobile'] = trim($params['mobile']);
		$params['username'] = trim($params['username']);
		$params['withdraw'] = floatval($params['withdraw']);
		$params['wechat_status'] = intval($params['wechat_status']);
		$params['alipay_status'] = intval($params['alipay_status']);
		$params['parent_id'] = intval($params['parent_id']);

		if (isset($params['deleted'])) {
			$params['deleted'] = intval($params['deleted']);
		}

		if (!isset($params['id'])) {
			$params['createtime'] = TIMESTAMP;
			$params['deleted'] = 0;
			pdo_insert('ewei_shop_union_user', $params);
			$params['id'] = pdo_insertid();
		}
		else {
			pdo_update('ewei_shop_union_user', $params, array('id' => $params['id'], 'uniacid' => $params['uniacid']));
		}

		return $params;
	}

	public function userInfo($openid)
	{
		global $_W;
		$id = intval($openid);
		$sql = 'SELECT * FROM ' . tablename('ewei_shop_union_user') . ' WHERE uniacid=:uniacid AND deleted=0';
		$params = array(':uniacid' => $_W['uniacid']);

		if ($id == 0) {
			$sql .= ' AND openid=:openid';
			$params[':openid'] = $openid;
		}
		else {
			$sql .= ' AND id=:id';
			$params[':id'] = $id;
		}

		$res = pdo_fetch($sql . ' LIMIT 1', $params);
		return $res;
	}

	public function sendMessage($params, $type, $openid = NULL)
	{
		global $_W;

		if (isset($params['createtime'])) {
			$params['createtime'] = date('Y-m-d H:i:s', $params['createtime']);
		}

		if (isset($params['paytime'])) {
			$params['paytime'] = date('Y-m-d H:i:s', $params['paytime']);
		}

		$data = m('common')->getPluginset('cashier');
		$notice = $data['notice'];

		if (empty($notice[$type])) {
			return false;
		}

		switch ($type) {
		case self::APPLY:
			$datas = array('[联系人]' => $params['name'], '[联系电话]' => $params['mobile'], '[申请时间]' => date('Y-m-d H:i:s', $params['createtime']));
			break;

		case self::CHECKED:
			$datas = array('[联系人]' => $params['name'], '[联系电话]' => $params['mobile'], '[审核状态]' => $params['status'], '[审核时间]' => $params['createtime'], '[驳回原因]' => $params['reason']);
			break;

		case self::APPLY_CLEARING:
			$datas = array('[联系人]' => $params['name'], '[联系电话]' => $params['mobile'], '[申请时间]' => $params['createtime'], '[申请金额]' => $params['money']);
			break;

		case self::PAY:
			$datas = array('[联系人]' => $params['name'], '[联系电话]' => $params['mobile'], '[申请时间]' => $params['createtime'], '[打款时间]' => $params['paytime'], '[申请金额]' => $params['money'], '[打款金额]' => $params['realmoney']);
			break;

		case self::PAY_CASHIER:
			$datas = array('[订单编号]' => $params['logno'], '[付款金额]' => $params['money'], '[余额抵扣]' => $params['deduction'], '[付款时间]' => $params['paytime'], '[工会名称]' => $params['cashier_title']);
			break;

		case self::PAY_CASHIER_USER:
			$datas = array('[订单编号]' => $params['logno'], '[付款金额]' => $params['money'], '[余额抵扣]' => $params['deduction'], '[付款时间]' => $params['paytime'], '[工会名称]' => $params['cashier_title']);
			break;

		default:
			break;
		}

		$datas = (isset($datas) ? $datas : array());
		$notice['openid'] = is_null($openid) ? $notice['openid'] : $openid;
		return $this->sendNotice($notice, $type, $datas);
	}

	protected function sendNotice($notice, $tag, $datas)
	{
		global $_W;

		if (!empty($notice[$tag])) {
			$advanced_template = pdo_fetch('select * from ' . tablename('ewei_shop_member_message_template') . ' where id=:id and uniacid=:uniacid limit 1', array(':id' => $notice[$tag], ':uniacid' => $_W['uniacid']));

			if (!empty($advanced_template)) {
				$url = (!empty($advanced_template['url']) ? $this->replaceArray($datas, $advanced_template['url']) : '');
				$advanced_message = array(
					'first'  => array('value' => $this->replaceArray($datas, $advanced_template['first']), 'color' => $advanced_template['firstcolor']),
					'remark' => array('value' => $this->replaceArray($datas, $advanced_template['remark']), 'color' => $advanced_template['remarkcolor'])
					);
				$data = iunserializer($advanced_template['data']);

				foreach ($data as $d) {
					$advanced_message[$d['keywords']] = array('value' => $this->replaceArray($datas, $d['value']), 'color' => $d['color']);
				}

				if (!empty($notice['openid'])) {
					$notice['openid'] = is_array($notice['openid']) ? $notice['openid'] : explode(',', $notice['openid']);

					foreach ($notice['openid'] as $openid) {
						if (!empty($notice[$tag]) && !empty($advanced_template['template_id'])) {
							$res=m('message')->sendTplNotice($openid, $advanced_template['template_id'], $advanced_message, $url);
						}
						else {
							m('message')->sendCustomNotice($openid, $advanced_message, $url);
						}
					}
				}
			}
		}

		return false;
	}
    public function is_perm($text)
    {
        global $_W;

        if (isset($_W['unionuser']['operator'])) {
            $perm = json_decode($_W['unionuser']['operator']['perm'], true);
            $routes = explode('.', $text);

            if (!in_array($routes[0], $perm)) {
                return false;
            }
        }

        return true;
    }
    public function categoryAll($status = 1)
    {
        global $_W;
        $status = intval($status);
        $condition = ' and uniacid=:uniacid  and status=' . intval($status);
        $params = array(':uniacid' => $_W['uniacid']);
        $item = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_union_category') . ' WHERE 1 ' . $condition . '  ORDER BY displayorder desc, id DESC', $params);
        return $item;
    }
	function get_union_welfare_config($union_id){
        global $_W;
        return pdo_fetch('select * from '.tablename("ewei_shop_union_welfare_config")." where uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],'union_id'=>$union_id));
	}
	//用户进行场馆预订的时候

	function bookvenue($openid,$start_itme,$end_time,$venue_id){
        global $_W;

		if($start_itme<time()){
            return error(-1,"开始时间必须比现在时间大");
		}
        if($start_itme>$end_time){
            return error(-1,"开始时间必须比结束时间小");
        }
        if($start_itme==$end_time){
            return error(-1,"预订时间不得低于半小时");
        }
		//如果开始时间是在已经预订的时间内的
		$sql="select id from ".tablename("ewei_shop_union_venue_bookedlist")." where start_time<=:start_time and end_time>:start_time and status =1 and is_delete=0";


		$id=pdo_fetch($sql,array(':start_time'=>$start_itme));
		if($id){
			return error(-1,"当前时间已经被其他用户预订");
		}

		// 如果结束时间在已经预订的时间内
        $sql="select id from ".tablename("ewei_shop_union_venue_bookedlist")." where start_time<=:end_time and end_time>=:end_time and status =1 and is_delete=0";
        $id=pdo_fetch($sql,array(":end_time"=>$end_time));
        if($id){
            return error(-1,"当前时间已经被其他用户预订");
        }

        $data=array(
        	'start_time'=>$start_itme,
			'end_time'=>$end_time,
			'uniacid'=>$_W['uniacid'],
			'union_id'=>$_W['unionid'],
			'openid'=>$openid,
			'create_time'=>time(),
			'venue_id'=>$venue_id,
			'status'=>1,
		);
        pdo_insert("ewei_shop_union_venue_bookedlist",$data);
		return pdo_insertid();
	}

	//获取福利配置
	function get_config($union_id){
        global $_W;
    	return pdo_fetch("select * from ".tablename("ewei_shop_union_welfare_config")." where uniacid=:uniacid and union_id=:union_id",array(":uniacid"=>$_W['uniacid'],':union_id'=>$union_id));
	}
	function get_mobile_member($mobile,$union_id){
        global $_W;
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':mobile_phone'=>$mobile,
			':union_id'=>$union_id,
        );
        $coution=" where  uniacid=:uniacid and mobile_phone=:mobile_phone and isdelete=0 and union_id=:union_id" ;
        $sql="select * from ".tablename("ewei_shop_union_members").$coution;
        return pdo_fetch($sql,$params);
	}

	//用户默认绑定的用户
	function get_member($openid,$union_id=0){
        global $_W;
        $params=array(
            ':uniacid'=>$_W['uniacid'],
            ':openid'=>$openid,
        );
        $coution=" where  uniacid=:uniacid and openid=:openid and activate=1 and status=1 and isdelete=0";

        if($union_id){
            $coution.=" and union_id=:union_id ";
            $params[':union_id']=$union_id;
        }
        if(empty($union_id)){
            $coution.=" and is_default=1 ";
        }
        $sql="select * from ".tablename("ewei_shop_union_members").$coution;
        return pdo_fetch($sql,$params);
    }

    //获取员工风采列表的数据
	function get_personnelmien($arg=array()){
        global $_W;
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $order = !empty($args['order']) ? $args['order'] : ' create_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $condition = ' and `uniacid` = :uniacid AND `is_delete` = 0 and union_id=:union_id';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);
        $sql="select id,title,teamname,department_id,create_time,header_imageurl from ".tablename("ewei_shop_union_personnelmien")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_personnelmien')." where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
		$department=pdo_fetchall("select * from ".tablename('ewei_shop_union_department')." where uniacid=:uniacid and union_id=:union_id",$params,"id");
		foreach ($list as &$row){
			$row['department']=isset($department[$row['department_id']]['name']) ? $department[$row['department_id']]['name']:'';
			$row['create_time']=date("Y-m-d H:i",$row['create_time']);
		}
		unset($row);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
	}
	//单个风采数据的查询
	function get_info_personnelmien($id){
		$sql="select * from ".tablename("ewei_shop_union_personnelmien")." where id=:id";
		$info=pdo_fetch($sql,array(':id'=>$id));
        return array("info"=>$info);
	}


	//单身查询分页
	function get_friendship_list($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $order = !empty($args['order']) ? $args['order'] : ' f.add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );

        $condition = ' and f.uniacid = :uniacid AND f.is_delete = 0 and f.union_id=:union_id';
        $params_count=$params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);
		$user_openid=!empty($args['openid']) ? trim($args['openid']) : "";
		if(!empty($user_openid)){
            $condition.=" AND f.openid =:openid";
            $params[':openid']=$user_openid;
		}
		$keywords=!empty($args['keywords']) ? $args['keywords'] : '';
		if(!empty($keywords)){
            $condition.=" AND (f.name like ':keywords%' or f.work like ':keywords%' or f.other like ':keywords%' or f.additional like ':keywords%' )";
            $params[':keywords']=$keywords;
		}
        $address=!empty($args['address']) ? $args['address'] : '';
        if(!empty($keywords)){
            $condition.=" AND f.address like ':address%'";
            $params[':address']=$address;
        }
        $sex = !empty($args['sex']) ? intval($args['sex']) : '';
        if(is_numeric($sex)){
            $condition.=" AND f.sex =:sex";
            $params[':sex']=$sex;
		}
        $sql="select f.*,IFNULL(ff.follow,0) as follow,IFNULL(ffd.fabulous,0) as fabulous from ".tablename("ewei_shop_union_friendship")." as f "
            ." LEFT JOIN ".tablename("ewei_shop_union_friendship_follow")." as ff ON ff.uniacid=f.uniacid and ff.union_id=f.union_id and ff.openid='{$openid}'"//是否关注
			." LEFT JOIN ".tablename("ewei_shop_union_friendship_fabulous")." as ffd ON ffd.uniacid=f.uniacid and ffd.union_id=f.union_id and ffd.openid='{$openid}'"//是否点赞
			." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_friendship')." f where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
	}
    //场馆预订
    function get_venue_list($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $condition = ' and uniacid = :uniacid AND is_delete = 0 and union_id=:union_id';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);
        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $sql="select * from ".tablename("ewei_shop_union_venue")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;;
        $countsql="select count(*) from ".tablename('ewei_shop_union_venue')." f where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);

        foreach ($list as &$row){
			//查询每单的预订结果
			$sql="select count(*) from ".tablename("ewei_shop_union_venue_bookedlist")." where start_time>:times_start and status=1 ";
            $row['bookcount']=pdo_fetchcolumn($sql,array(":times_start"=>time()));
		}
		unset($row);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
    }

    //查询场馆预订的人数和场馆数据
	function get_venue_info($venue_id){
        global $_W;
        $unionid=$_W['unionid'];
        $params=array(
        	':union_id'=>$unionid,
			':uniacid'=>$_W['uniacid'],
			':id'=>$venue_id,
		);
        $venue_info=pdo_fetch("select * from ".tablename("ewei_shop_union_venue")." where id=:id and union_id=:unionid and uniacid=:uniacid",$params);
        return $venue_info;
	}

	function check_venue_status($venue_id){
        $sql="select id from ".tablename("ewei_shop_union_venue_bookedlist")." where venue_id=:venue_id and  start_time<=:start_time and end_time>:start_time and status =1 and is_delete=0";
        $id=pdo_fetch($sql,array(':start_time'=>time(),":venue_id"=>$venue_id));

        if($id){
        	return 1;
		}else{
        	return 0;
		}

	}


	//查询预订的人数和数量
	function get_venue_bookedlist($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 6;
        $condition = ' and b.uniacid = :uniacid AND b.is_delete = 0 and b.status=1 and b.union_id=:union_id';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);
		$vunue_id=!empty($args['vunue_id']) ? intval($args['vunue_id']) : 0;
        $order = !empty($args['order']) ? $args['order'] : ' b.create_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
		if(!empty($vunue_id)){
            $condition.=" AND b.venue_id =:venue_id";
            $params[':venue_id']=$vunue_id;
		}
        $user_openid=!empty($args['openid']) ? trim($args['openid']) : "";
        if(!empty($user_openid)){//查自己的预订
            $condition.=" AND b.openid =:openid";
            $params[':openid']=$user_openid;
        }

        $sql="select b.*,v.title from ".tablename("ewei_shop_union_venue_bookedlist")." as b ".
			"LEFT JOIN ".tablename("ewei_shop_union_venue")." as v  ON  v.id=b.venue_id "
			." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;
        $countsql="select count(*) from ".tablename('ewei_shop_union_venue_bookedlist')." as b where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        foreach ($list as &$row){
			$row['date']=date("Y-m-d",$row['start_time']);
			$row['start_time']=date("H:i",$row['start_time']);
			$row['end_time']=date("H:i",$row['end_time']);
			$row['create_time']=date("m-d",$row['create_time']);
		}
		unset($row);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
	}
	//文体协会列表
	function get_association($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $condition = ' and uniacid = :uniacid AND is_delete = 0 and union_id=:union_id';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);

        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $sql="select * from ".tablename("ewei_shop_union_association")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;;
        $countsql="select count(*) from ".tablename('ewei_shop_union_association')."where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
    }
    // 文体协会的参与人员
    function get_association_member($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $status = !empty($args['status']) ? intval($args['status']) : '';
        $association_id = !empty($args['association_id']) ? intval($args['association_id']) : '';


        $condition = ' and uniacid = :uniacid AND  union_id=:union_id';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);

        if(is_numeric($status) && !empty($status)){
        	$condition.=" AND status=:status";
        	$params[':status']=$status;
		}
        if(is_numeric($association_id) && !empty($association_id)){
            $condition.=" AND association_id=:association_id";
            $params[':association_id']=$association_id;
        }
        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $sql="select * from ".tablename("ewei_shop_union_association_member")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;;
        $countsql="select count(*) from ".tablename('ewei_shop_union_association_member')."where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
    }
    // 文体协会的消息通知
    function get_association_message($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $association_id = !empty($args['association_id']) ? intval($args['association_id']) : '';


        $condition = ' and uniacid = :uniacid AND   union_id=:union_id AND is_delete = 0 ';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);


        if(is_numeric($association_id) && !empty($association_id)){
            $condition.=" AND association_id=:association_id";
            $params[':association_id']=$association_id;
        }
        $order = !empty($args['order']) ? $args['order'] : ' create_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $sql="select * from ".tablename("ewei_shop_union_notice")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;;
        $countsql="select count(*) from ".tablename('ewei_shop_union_notice')."where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
    }
    //公文管理
	function get_document_list($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;

        $condition = ' and uniacid = :uniacid AND   union_id=:union_id AND isdelete = 0 ';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);

		if($arg['keywords']){
			$condition.=" and title like :keywords ";
            $params[':keywords']="%".$arg['keywords']."%";
		}

        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        $sql="select * from ".tablename("ewei_shop_union_document")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;;

        $countsql="select count(*) from ".tablename('ewei_shop_union_document')."where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);

        foreach ($list as &$value){
			$value['datetime']=date("Y-m-d",$value['add_time']);
		}
		unset($value);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
	}
	//公文单个查询
	function get_document_info($id){
        global $_W;
        $info=array();
		$sql="select * from ".tablename("ewei_shop_union_document")." where id=:id";
        $info=pdo_fetch($sql,array(":id"=>$id));
        return array("info"=>$info);
	}

	//活动管理
	function get_activitylist($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;

        $condition = ' and uniacid = :uniacid AND   union_id=:union_id AND is_delete = 0 ';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);

        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );

        $sql="select * from ".tablename("ewei_shop_union_activity")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;;
        $countsql="select count(*) from ".tablename('ewei_shop_union_activity')."where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
	}

	//福利管理列表选择

    function get_welfarelist($arg=array()){
        global $_W;
        $openid=$_W['openid'];
        $page = !empty($args['page']) ? intval($args['page']) : 1;
        $pagesize = !empty($args['pagesize']) ? intval($args['pagesize']) : 10;
        $type = !empty($args['type']) ? intval($args['type']) : ''; // 默认


        $condition = ' and uniacid = :uniacid AND   union_id=:union_id AND is_delete = 0 ';
        $params = array(':uniacid' => $_W['uniacid'],':union_id'=>$_W['unionid']);

        $order = !empty($args['order']) ? $args['order'] : ' add_time';
        $orderby = empty($args['order']) ? 'desc' : (!empty($args['by']) ? $args['by'] : '' );
        if(is_numeric($type) && !empty($type)){
            $condition.=" AND type=:type";
            $params['type']=$type;
        }
        $user_openid=!empty($args['openid']) ? trim($args['openid']) : "";
        if(!empty($user_openid)){//查自己的福利申请
            $condition.=" AND openid =:openid";
            $params[':openid']=$user_openid;
        }
		$status=!empty($args['status']) ? intval($args['status']) : ''; // 状态筛选
        if(is_numeric($status) && !empty($status)){
            $condition.=" AND status=:status";
            $params['status']=$status;
        }


        $sql="select * from ".tablename("ewei_shop_union_welfare")." where 1 {$condition} ORDER BY {$order} {$orderby} LIMIT " . ($page - 1) * $pagesize . ',' . $pagesize;;
        $countsql="select count(*) from ".tablename('ewei_shop_union_welfare')."where 1 ".$condition;
        $total = pdo_fetchcolumn($countsql,$params);
        $list = pdo_fetchall($sql, $params);
        return array("list"=>$list,"total"=>$total,'pagesize'=>$pagesize);
    }

    function down_phpzip($enclosure_urllist,$zipfilename){
        global $_W;

		$dir=ATTACHMENT_ROOT.$_W['uniacid']."/zip";
		if(!is_dir($dir)){
			load()->func("file");
			mkdirs($dir,0755);
		}
        $handle = opendir($dir);
		 while(($fl = readdir($handle)) !== false){
		   if($fl!='.' && $fl != '..'){
               $filetime =filemtime($dir . "/" . $fl);
               if(time()-$filetime>604800){
               	@unlink($dir . "/" . $fl);
			   }
		   }
		 }
         $zipfilename=md5($_W['openid']).'document.zip';
		 if(is_file($dir.'/'.$zipfilename)){
			return $dir.'/'.$zipfilename;
		 }
        $zip = new ZipArchive();//使用本类，linux需开启zlib，windows需取消php_zip.dll前的注释

        if ($zip->open($dir.'/'.$zipfilename, ZIPARCHIVE::CREATE)!==TRUE) {
            exit('无法打开文件，或者文件创建失败');
        }
        foreach($enclosure_urllist as $val){
            if(file_exists($val)){

                $zip->addFile( $val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
            }
        }
        $zip->close();//关闭

        return $dir.'/'.$zipfilename;
	}
    static $phpzip=null;
	function get_phpzipmodal(){
        if(isset(self::$phpzip)){
        	return self::$phpzip;
		}
        $phpzip_url=EWEI_SHOPV2_TAX_CORE.'/phpzip/phpzip.php';
        if(is_file($phpzip_url)){
            require_once $phpzip_url;
            self::$phpzip = new phpzip();
            return self::$phpzip;
		}
        return NULL;

	}

	//获取工会信息
	function get_union_info($id){
		return pdo_fetch("select * from ".tablename("ewei_shop_union_user").' where id=:id',array(":id"=>$id));
	}

	//查询用户部门
	function get_department_info($uniacid,$union_id,$id){
		$sql="select * from ".tablename("ewei_shop_union_department")." where uniacid=:uniacid and union_id=:union_id and id=:id";
		return pdo_fetch($sql,array(':uniacid'=>$uniacid,":union_id"=>$union_id,':id'=>$id));
	}

    function lazy($html = '')
    {

        global $_W;
        $html = preg_replace_callback("/<img.*?src=[\\\'| \\\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg]?))[\\\'|\\\"].*?[\/]?>/", function ($matches) use ($_W) {
            $images = $matches[0];
            $attachurl = str_replace(array('https://','http://'),'',$_W['attachurl_local']);
            if (strexists($images, $attachurl)){
                $image = $matches[1];
                $image = str_replace(array('https://','http://'),'',$image);
                $image = str_replace($attachurl,'',$image);
                $images = str_replace(array('https://','http://'),'',$images);
                $images = str_replace($attachurl,'',$images);
                $images = str_replace($image,tomedia($image),$images);

            }
            if(!strexists($images,"width")){
                $leng=strexists($images,"img");
                $images=$this->str_insert($images,$leng," width=100%");
			}
            $images = preg_replace('/ (?:width)=(\'|").*?\\1/',' width="100%"',$images);
            $images = preg_replace('/ (?:height)=(\'|").*?\\1/',' ',$images);
            $images =preg_replace('/(?<=style="width:).+?px;/i', '100%', $images);
            $images=preg_replace("/src=/", "data-lazy=", $images);
            return $images;
        }, $html);
        return $html;
    }

    function str_insert($str, $i, $substr)
    {
        $startstr='';
        $laststr="";
        $startstr=substr($str,0,$i+3);
        $laststr=substr($str,$i+3,strlen($str));
        $str = ($startstr . $substr . $laststr);
        return $str;
    }

    function readmember_insert($openid,$type){
        global $_W;
		$tablename="ewei_shop_union_readmembers";
		$uniacid=$_W['uniacid'];
		$unionid=$_W['unionid'];
		$params=array(
			':uniacid'=>$uniacid,
			':union_id'=>$unionid,
			':openid'=>$openid,
			':type'=>$type,
		);
		$sql="select count(*) from ".tablename($tablename)." where uniacid =:uniacid and union_id=:union_id and openid =:openid and type=:type";
		$count=pdo_fetchcolumn($sql,$params);

		if(!$count){
			$data=array(
				'uniacid'=>$uniacid,
				'union_id'=>$unionid,
				'openid'=>$openid,
				'type'=>$type,
				'createtime'=>time(),
			);
			pdo_insert($tablename,$data);
		}
	}
	function readcount($type){
		global $_W;
		$returndata=array();
        $tablename="ewei_shop_union_readmembers";
        $uniacid=$_W['uniacid'];
        $unionid=$_W['unionid'];
        $params=array(
            ':uniacid'=>$uniacid,
            ':union_id'=>$unionid,
            ':type'=>$type,
        );
        $sql="select count(*) from ".tablename($tablename)." where uniacid =:uniacid and union_id=:union_id  and type=:type";
        $count=pdo_fetchcolumn($sql,$params);
        $returndata['count']=$count;
        $sql="select m.realname from ".tablename($tablename)." as rdm ".
            "LEFT JOIN ".tablename("ewei_shop_member")." as m ON rdm.openid = m.openid and m.uniacid=rdm.uniacid"
            ." where rdm.uniacid =:uniacid and rdm.union_id=:union_id  and rdm.type=:type";
        if($count && $count>5){
        	//获取随机几个代表
			$sql.=" ORDER BY RAND() LIMIT 5";
		}else{
            $sql.=" LIMIT 5";
		}
        $list=pdo_fetchall($sql,$params);
        $returndata['memberlist']=$list;

		return $returndata;
	}

    /**
     * @param $union_id
	 * 查询当前公司是终极还是上级公司
     */
	function checkunion($union_id=0){
        global $_W;
        if(empty($union_id)){
            $unionid=$_W['unionid'];
		}
		$sql="select count(*) from ".tablename("ewei_shop_union_user")." where ";

	}

}

?>
