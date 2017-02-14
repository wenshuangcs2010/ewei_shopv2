<?php

/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('IN_IA')) {
	exit('Access Denied');
}
if (!class_exists('ArticleModel')) {

	class ArticleModel extends PluginModel {

		public function doShare($aid, $shareid, $myid) {
			global $_W, $_GPC;
			if (empty($aid) || empty($shareid) || empty($myid) || $shareid == $myid) {
				return;
			}
			$article = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_article') . " WHERE id=:aid and article_state=1 and uniacid=:uniacid limit 1 ", array(':aid' => $aid, ':uniacid' => $_W['uniacid']));
			if(empty($article)){
				return;
			}
			// 获取 分享者 与访问者的信息
			$profile = m('member')->getMember($shareid);
			$myinfo = m('member')->getMember($myid);
			// 如果为空 则返回
			if (empty($myinfo) || empty($profile)) {
				return;
			}
			// 获取商城设置以及文章设置
			$shopset = $_W['shopset'];
			$givecredit = intval($article['article_rule_credit']); //积分奖励
			$givemoney = floatval($article['article_rule_money']); //余额奖励
			//奖励记录
			$my_click = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_article_share') . " WHERE aid=:aid and click_user=:click_user and uniacid=:uniacid ", array(':aid' => $article['id'], ':click_user' => $myid, ':uniacid' => $_W['uniacid']));
			if (!empty($my_click)) {
				//如果属于重复阅读
				$givecredit = intval($article['article_rule_credit2']); //积分奖励
				$givemoney = floatval($article['article_rule_money2']); //余额奖励
			}

			//奖励到期时间
			if (!empty($article['article_hasendtime']) && time() > $article['article_endtime']) {
				return;
			}

			//次数限制
			$readtime = $article['article_readtime'];
			if ($readtime <= 0) {
				$readtime = 4;
			}
			
			$clicktime = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_article_share') . " WHERE aid=:aid and share_user=:share_user and click_user=:click_user and uniacid=:uniacid ", array(':aid' => $article['id'], ':share_user' => $shareid, ':click_user' => $myid, ':uniacid' => $_W['uniacid']));
			if ($clicktime >= $readtime) {
				return;
			}

			//所有奖励次数
			$all_click = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_article_share') . " WHERE aid=:aid and share_user=:share_user and uniacid=:uniacid ", array(':aid' => $article['id'], ':share_user' => $shareid, ':uniacid' => $_W['uniacid']));
			if ($all_click >= $article['article_rule_allnum']) {
				//总奖励次数超出限制，不奖励
				$givecredit = 0;
				$givemoney = 0;
			} else {
				//每天奖励次数
				$day_start = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
				$day_end = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
				$day_click = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_article_share') . " WHERE aid=:aid and share_user=:share_user and click_date>:day_start and click_date<:day_end and uniacid=:uniacid ", array(':aid' => $article['id'], ':share_user' => $shareid, ':day_start' => $day_start, ':day_end' => $day_end, ':uniacid' => $_W['uniacid']));
				if ($day_click >= $article['article_rule_daynum']) { //如果小于当天奖励次数限制
					//每天奖励次数超出限制，不奖励
					$givecredit = 0;
					$givemoney = 0;
				}
			}

			//互刷情况
			$toto = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('ewei_shop_article_share') . " WHERE aid=:aid and share_user=:click_user and click_user=:share_user and uniacid=:uniacid ", array(':aid' => $article['id'], ':share_user' => $shareid, ':click_user' => $myid, ':uniacid' => $_W['uniacid']));
			if (!empty($toto)) {
				return;
			}

			if ($article['article_rule_credittotal'] > 0 || $article['article_rule_moneytotal'] > 0) {
				$creditlast = 0; //剩余积分
				$moneylast = 0; //剩余余额
				//首次阅读次数
				$firstreads = pdo_fetchcolumn('select count(distinct click_user) from ' . tablename('ewei_shop_article_share') . ' where aid=:aid and uniacid=:uniacid limit 1', array(':aid' => $article['id'], ':uniacid' => $_W['uniacid']));
				//总阅读次数
				$allreads = pdo_fetchcolumn('select count(*) from ' . tablename('ewei_shop_article_share') . ' where aid=:aid and uniacid=:uniacid limit 1', array(':aid' => $article['id'], ':uniacid' => $_W['uniacid']));
				//重复阅读次数
				$secreads = $allreads - $firstreads;
				//奖励剩余
				if ($article['article_rule_credittotal'] > 0) {
					if (!empty($article['article_advance'])) {
						//如果开启高级模式
						$creditlast = $article['article_rule_credittotal'] - ( $firstreads + ($article['article_virtualadd']?$article['article_readnum_v']:0) ) * $article['article_rule_creditm'] - $secreads * $article['article_rule_creditm2'];
					} else {
						$creditout = pdo_fetchcolumn('select sum(add_credit) from ' . tablename('ewei_shop_article_share') . ' where aid=:aid and uniacid=:uniacid limit 1', array(':aid' => $article['id'], ':uniacid' => $_W['uniacid']));
						$creditlast = $article['article_rule_credittotal'] - $creditout;
					}
				}
				if ($article['article_rule_moneytotal'] > 0) {
					if (!empty($article['article_advance'])) {
						//如果开启高级模式
						$moneylast = $article['article_rule_moneytotal'] - ( $firstreads + ($article['article_virtualadd']?$article['article_readnum_v']:0) ) * $article['article_rule_moneym'] - $secreads * $article['article_rule_moneym2'];
					} else {
						$moneyout = pdo_fetchcolumn('select sum(add_money) from ' . tablename('ewei_shop_article_share') . ' where aid=:aid and uniacid=:uniacid limit 1', array(':aid' => $article['id'], ':uniacid' => $_W['uniacid']));
						$moneylast = $article['article_rule_moneytotal'] - $moneyout;
					}
				}

				$creditlast <= 0 && $creditlast = 0; //剩余积分
				$moneylast <= 0 && $moneylast = 0; //剩余余额

				if ($creditlast <= 0) {
					$givecredit = 0;
				}
				if ($moneylast <= 0) {
					$givemoney = 0;
				}
			}
			//分享记录
			$insert = array('aid' => $article['id'], 'share_user' => $shareid, 'click_user' => $myid, 'click_date' => time(),
				'add_credit' => $givecredit,
				'add_money' => $givemoney,
				'uniacid' => $_W['uniacid']);
			pdo_insert('ewei_shop_article_share', $insert);
			if ($givecredit > 0) {
				//可以奖励积分
				m('member')->setCredit($profile['openid'], 'credit1', $givecredit, array(0, $shopset['name'] . " 文章营销奖励积分"));
			}

			if ($givemoney > 0) {
				//可以奖励余额
				m('member')->setCredit($profile['openid'], 'credit2', $givemoney, array(0, $shopset['name'] . " 文章营销奖励余额"));
			}
			if ($givecredit > 0 || $givemoney > 0) {
				// 查询模板id
				$article_sys = pdo_fetch("SELECT * FROM " . tablename('ewei_shop_article_sys') . " WHERE uniacid=:uniacid limit 1 ", array(':uniacid' => $_W['uniacid']));
				// 发送通知
				$detailurl = mobileUrl('member', null, true);
				$p = '';
				if ($givecredit > 0) {
					$p.=$givecredit . '个积分、';
				}
				if ($givemoney > 0) {
					$p.=$givemoney . '元余额';
				}

				$msg = array(
					'first' => array('value' => "您的奖励已到帐！", "color" => "#4a5077"),
					'keyword1' => array('title' => '任务名称', 'value' => "分享得奖励", "color" => "#4a5077"),
					'keyword2' => array('title' => '通知类型', 'value' => "用户通过您的分享进入文章《" . $article['article_title'] . "》，系统奖励您" . $p . "。", "color" => "#4a5077"),
					'remark' => array('value' => "奖励已发放成功，请到会员中心查看。", "color" => "#4a5077")
				);
				if (!empty($article_sys['article_message'])) {
					m('message')->sendTplNotice($profile['openid'], $article_sys['article_message'], $msg, $detailurl);
				} else {
					m('message')->sendCustomNotice($profile['openid'], $msg, $detailurl);
				}
			}
		}

		function mid_replace($content) {
			global $_GPC;
			//替换内容
			preg_match_all("/href\=[\"|\'](.*?)[\"|\']/is", $content, $links);
			foreach ($links[1] as $key => $lnk) {
				$newlnk = $this->href_replace($lnk);
				$content = str_replace($links[0][$key], "href=\"{$newlnk}\"", $content);
			}
			return $content;
		}

		function href_replace($lnk) {
			global $_GPC;
			$newlnk = $lnk;
			if (strexists($lnk, 'ewei_shop') && !strexists($lnk, '&mid')) {
				if (strexists($lnk, '?')) {
					$newlnk = $lnk . "&mid=" . intval($_GPC['mid']);
				} else {
					$newlnk = $lnk . "?mid=" . intval($_GPC['mid']);
				}
			}
			return $newlnk;
		}

		function perms() {
			return array(
				'article' => array(
					'text' => $this->getName(), 'isplugin' => true,
					'child' => array(
						'cate' => array('text' => '分类设置', 'addcate' => '添加分类-log', 'editcate' => '编辑分类-log', 'delcate' => '删除分类-log'),
						'page' => array('text' => '文章设置', 'add' => '添加文章-log', 'edit' => '修改文章-log', 'delete' => '删除文章-log', 'showdata' => '查看数据统计', 'otherset' => '其他设置', 'report' => '举报记录')
					)
				)
			);
		}

	}

}