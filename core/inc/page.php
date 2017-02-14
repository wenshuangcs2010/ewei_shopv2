<?php

/*
 * 人人商城V2
 *
 * @author ewei 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require MODULE_ROOT. '/dispage.php';
class Page extends WeModuleSite {

	public function runTasks() {
		global $_W;
		load()->func('communication');
		$lasttime = strtotime(m('cache')->getString('receive', 'global'));
		$interval = intval(m('cache')->getString('receive_time', 'global'));
		if (empty($interval)) {
			$interval = 60;
		}
		$interval*=60;

		//如果上次收货时间小
		$current = time();
		if ($lasttime + $interval <= $current) {
			m('cache')->set('receive', date('Y-m-d H:i:s', $current), 'global');
			ihttp_request( EWEI_SHOPV2_TASK_URL . "order/receive.php", null, null, 1);
		}

		//自动关闭订单
		$lasttime = strtotime(m('cache')->getString('closeorder', 'global'));
		$interval = intval(m('cache')->getString('closeorder_time', 'global'));
		if (empty($interval)) {
			$interval = 60;
		}
		$interval*=60;

		//如果上次自动关闭时间小
		$current = time();
		if ($lasttime + $interval <= $current) {
			m('cache')->set('closeorder', date('Y-m-d H:i:s', $current), 'global');
			ihttp_request(EWEI_SHOPV2_TASK_URL . "order/close.php", null, null, 1);
		}

		//优惠券自动返利
		if (com('coupon')) {
			$lasttime = strtotime(m('cache')->getString('couponback', 'global'));
		    $interval = intval(m('cache')->getString('couponback_time', 'global'));
			if (empty($interval)) {
				$interval = 60;
			}
			$interval*=60;
			//如果上次执行时间小
			$current = time();
			if ($lasttime + $interval <= $current) {
				m('cache')->set('couponback', date('Y-m-d H:i:s', $current), 'global');
				ihttp_request(EWEI_SHOPV2_TASK_URL . "coupon/back.php", null, null, 1);
			}
		}

		if (p('groups')) {
			/*
             * 拼团未付款订单自动取消
             * */
			$groups_order_lasttime = strtotime(m('cache')->getString('groups_order_cancelorder', 'global'));
			$groups_order_interval = intval(m('cache')->getString('groups_order_cancelorder_time', 'global'));
			if (empty($groups_order_interval)) {
				$groups_order_interval = 60;
			}

			$groups_order_interval *= 60;
			//如果上次自动关闭时间小
			$groups_order_current = time();
			if ($groups_order_lasttime + $groups_order_interval <= $groups_order_current) {
				m('cache')->set('groups_order_cancelorder', date('Y-m-d H:i:s', $groups_order_current), 'global');
				ihttp_request($_W['siteroot'] . "addons/ewei_shopv2/plugin/groups/task/order.php", null, null, 1);
			}
			/*
             * 拼团失败自动退款
             * */
			$groups_team_lasttime = strtotime(m('cache')->getString('groups_team_refund', 'global'));
			$groups_team_interval = intval(m('cache')->getString('groups_team_refund_time', 'global'));
			if (empty($groups_team_interval)) {
				$groups_team_interval = 60;
			}

			$groups_team_interval *= 60;
			//如果上次自动关闭时间小
			$groups_team_current = time();
			if ($groups_team_lasttime + $groups_team_interval <= $groups_team_current) {
				m('cache')->set('groups_team_refund', date('Y-m-d H:i:s', $groups_team_current), 'global');
				ihttp_request($_W['siteroot'] . "addons/ewei_shopv2/plugin/groups/task/refund.php", null, null, 1);
			}
			/*
             * 拼团发货自动收货
             * */
			$groups_receive_lasttime = strtotime(m('cache')->getString('groups_receive', 'global'));
			$groups_receive_interval = intval(m('cache')->getString('groups_receive_time', 'global'));
			if (empty($groups_receive_interval)) {
				$groups_receive_interval = 60;
			}

			$groups_receive_interval *= 60;
			//如果上次自动关闭时间小
			$groups_receive_current = time();
			if ($groups_receive_lasttime + $groups_receive_interval <= $groups_receive_current) {
				m('cache')->set('groups_receive', date('Y-m-d H:i:s', $groups_receive_current), 'global');
				ihttp_request($_W['siteroot'] . "addons/ewei_shopv2/plugin/groups/task/receive.php", null, null, 1);
			}
		}

		if(p('seckill')){
            $lasttime = strtotime(m('cache')->getString('seckill_delete_lasttime', 'global'));
            $interval = 5 * 60;
            //如果上次执行时间小
            $current = time();
            if ($lasttime + $interval <= $current) {
                m('cache')->set('seckill_delete_lasttime', date('Y-m-d H:i:s', $current), 'global');
                ihttp_request($_W['siteroot'] . "addons/ewei_shopv2/plugin/seckill/task/receive.php", null, null, 1);
            }
        }

		exit('run finished.');
	}

	public function template($filename = '', $type = TEMPLATE_INCLUDEPATH, $account=false) {

		global $_W, $_GPC;

		if (empty($filename)) {
			$filename = str_replace(".", "/", $_W['routes']);
		}

		if ($_GPC['do'] == 'web') {
			$filename = str_replace("/add", "/post", $filename);
			$filename = str_replace("/edit", "/post", $filename);
			$filename = 'web/' . $filename;

		} else if ($_GPC['do'] == 'mobile') {

		}

		$name = 'ewei_shopv2';
		$moduleroot = IA_ROOT . "/addons/ewei_shopv2";
		if (defined('IN_SYS')) {

			$compile = IA_ROOT . "/data/tpl/web/{$_W['template']}/{$name}/{$filename}.tpl.php";
			$source = $moduleroot . "/template/{$filename}.html";
			if (!is_file($source)) {
				$source = $moduleroot . "/template/{$filename}/index.html";
			}
			if (!is_file($source)) {

				$explode = array_slice(explode('/', $filename), 1);
				$temp = array_slice($explode, 1);
				$source = $moduleroot . "/plugin/" . $explode[0] . "/template/web/" . implode('/', $temp) . ".html";
				if (!is_file($source)) {
					$source = $moduleroot . "/plugin/" . $explode[0] . "/template/web/" . implode('/', $temp) . "/index.html";
				}
			}
		} else {

			if($account){
				$template = $_W['shopset']['wap']['style'];
				if (empty($template)) {
					$template = "default";
				}
				if (!is_dir($moduleroot . "/template/account/" . $template)) {
					$template = "default";
				}
				$compile = IA_ROOT . "/data/tpl/app/{$name}/{$template}/account/{$filename}.tpl.php";
				$source = IA_ROOT . "/addons/{$name}/template/account/{$template}/{$filename}.html";

				if (!is_file($source)) {
					$source = IA_ROOT . "/addons/{$name}/template/account/default/{$filename}.html";
				}

				if (!is_file($source)) {
					$source = IA_ROOT . "/addons/{$name}/template/account/default/{$filename}/index.html";
				}
			}else{
				$template = m('cache')->getString('template_shop');
				if (empty($template)) {
					$template = "default";
				}
				if (!is_dir($moduleroot . "/template/mobile/" . $template)) {
					$template = "default";
				}
				$compile = IA_ROOT . "/data/tpl/app/{$name}/{$template}/mobile/{$filename}.tpl.php";
				$source = IA_ROOT . "/addons/{$name}/template/mobile/{$template}/{$filename}.html";
				if (!is_file($source)) {
					$source = IA_ROOT . "/addons/{$name}/template/mobile/{$template}/{$filename}/index.html";
				}
				if (!is_file($source)) {
					$source = IA_ROOT . "/addons/{$name}/template/mobile/default/{$filename}.html";
				}
				if (!is_file($source)) {
					$source = IA_ROOT . "/addons/{$name}/template/mobile/default/{$filename}/index.html";
				}
			}


			if (!is_file($source)) {
				//如果还没有就是插件的
				$names = explode('/', $filename);
				$pluginname = $names[0];
				$ptemplate = m('cache')->getString('template_' . $pluginname);
				if (empty($ptemplate) || $pluginname=='creditshop') {
					$ptemplate = "default";
				}
				if (!is_dir($moduleroot . "/plugin/" . $pluginname . "/template/mobile/" . $ptemplate)) {
					$ptemplate = "default";
				}
				unset($names[0]);
				$pfilename = implode('/',$names);

                $compile = IA_ROOT . "/data/tpl/app/{$name}/plugin/{$pluginname}/{$ptemplate}/mobile/{$filename}.tpl.php";

				$source = $moduleroot . "/plugin/" . $pluginname . "/template/mobile/" . $ptemplate . "/{$pfilename}.html";
				if (!is_file($source)) {
					$source = $moduleroot . "/plugin/" . $pluginname . "/template/mobile/" . $ptemplate . "/".$pfilename."/index.html";
				}

			}
		}
		if (!is_file($source)) {
			exit("Error: template source '{$filename}' is not exist!");
		}


		if (DEVELOPMENT || !is_file($compile) || filemtime($source) > filemtime($compile)) {
			shop_template_compile($source, $compile, true);
		}
		return $compile;
	}

	function message($msg,$redirect = '' ,$type = ''){
		global $_W;
		$title = "";
		$buttontext = "";
		$message = $msg;
        $buttondisplay = true;
		if(is_array($msg)){
			$message = isset($msg['message'])?$msg['message']:'';
			$title =  isset($msg['title'])?$msg['title']:'';
			$buttontext =  isset($msg['buttontext'])?$msg['buttontext']:'';
			$buttondisplay =  isset($msg['buttondisplay'])?$msg['buttondisplay']:true;
		}
		if(empty($redirect)){
			$redirect = 'javascript:history.back(-1);';
		}
		elseif($redirect=='close'){
			$redirect = 'javascript:WeixinJSBridge.call("closeWindow")';
		}elseif($redirect=='exit'){
		    $redirect = "";
        }
		include $this->template('_message');
		exit;
	}
}