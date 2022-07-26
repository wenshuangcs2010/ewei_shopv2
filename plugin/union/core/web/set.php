<?php
if (!(defined('IN_IA'))) 
{
	exit('Access Denied');
}
class Set_EweiShopV2Page extends PluginWebPage 
{
	public function main() 
	{

		global $_W;
		global $_GPC;
		if ($_W['ispost']) 
		{
			$data = ((is_array($_GPC['data']) ? $_GPC['data'] : array()));
			$data['applycontent'] = m('common')->html_images($data['applycontent']);
			$data['regbg'] = save_media($data['regbg']);
			$data['cashalipay'] = (int) $data['cashalipay'];
			$data['cashcard'] = (int) $data['cashcard'];
			$data['withdrawcharge'] = (double) $data['withdrawcharge'];
			$data['mobile_phone'] = $data['mobile_phone'];
			m('common')->updatePluginset(array('union' => $data));
			plog('union.set.edit', '修改基本设置');
			show_json(1, array('url' => webUrl('union/set', array('tab' => str_replace('#tab_', '', $_GPC['tab'])))));
		}
		$form_list = false;
		if (p('diyform')) 
		{
			$form_list = p('diyform')->getDiyformList();
		}
		$data = m('common')->getPluginset('union');
		$url = $_W['siteroot'] . 'web/union.php?i=' . $_W['uniacid'];
		$qrcode = m('qrcode')->createQrcode($url);
		include $this->template();
	}
}
?>