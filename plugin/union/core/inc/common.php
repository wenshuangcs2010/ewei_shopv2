<?php
if ($_GPC['r'] != 'union.manage.login')
{
	session_start();


	if (empty($_SESSION['__union_' . (int) $_GPC['i'] . '_session']))
	{
		header('location: ' . unionUrl('login'));
		exit();
	}
	$GLOBALS['_W']['unionuser'] = $_SESSION['__union_' . (int) $_GPC['i'] . '_session'];
	$GLOBALS['_W']['unionid'] = $GLOBALS['_W']['unionuser']['id'];
}
function unionUrl($do = '', $query = NULL, $full = false)
{
	global $_W;
	global $_GPC;
	$dos = explode('/', trim($do));
	$routes = array();
	$routes[] = $dos[0];
	if (isset($dos[1])) 
	{
		$routes[] = $dos[1];
	}
	if (isset($dos[2])) 
	{
		$routes[] = $dos[2];
	}
	if (isset($dos[3])) 
	{
		$routes[] = $dos[3];
	}
	$r = implode('.', $routes);
	if (!(is_array($query))) 
	{
		$query = array();
	}
	if (!(empty($r))) 
	{
		$query = array_merge(array('r' => $r), $query);
	}
	$query = array_merge(array('i' => (int) $_GPC['i']), $query);

	return str_replace('./index.php', './union.php', wurl('', $query));
}
?>