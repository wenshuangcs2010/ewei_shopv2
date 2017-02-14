<?php
/*
 * 人人商城
 * 
 * @author ewei 狸小狐 QQ:22185157 
 */
if (!defined('ES_PATH')) {
    exit('Access Denied');
}
class LoginController  extends Controller {
    
     function index(){
         global $_W,$_GPC;
         $basicset = $this->basicset();
         $title = "登录";
         include $this->template('login/index');
     }
    public function check(){
        global $_W,$_GPC;
        $setting = $_W['setting'];
        $member['content'] = array(
            'username' => trim($_GPC['username']),
            'password' => trim($_GPC['password'])
        );
        load()->model('user');
        $username = trim($_GPC['username']);
        pdo_query('DELETE FROM'.tablename('users_failed_login'). ' WHERE lastupdate < :timestamp', array(':timestamp' => TIMESTAMP-300));
        $failed = pdo_get('users_failed_login', array('username' => $username, 'ip' => CLIENT_IP));
        if ($failed['count'] >= 5) {
            $data['msg'] = '输入密码错误次数超过5次，请在5分钟后再登录';
            $data['name'] = 'password';
            echo json_encode($data);
            die();
        }
        if(empty($username)) {
            $data['msg'] = '请输入要登录的用户名';
            $data['name'] = 'password';
            echo json_encode($data);
            die();
        }
        $member['username'] = $username;
        $member['password'] = $_GPC['password'];
        if(empty($member['password'])) {
            $data['msg'] = '请输入密码';
            $data['name'] = 'password';
            echo json_encode($data);
            die();
        }
        $record = user_single($member);
        if(!empty($record)) {
            if($record['status'] == 1) {
                $data['msg'] = '您的账号正在审核或是已经被系统禁止，请联系网站管理员解决！';
                $data['name'] = 'system';
                echo json_encode($data);
                die();
            }
            if($record['endtime'] > 0 && $record['endtime'] < time()) {
                $data['msg'] = '您的账号服务时间已过，请联系网站管理员解决！';
                $data['name'] = 'system';
                echo json_encode($data);
                die();
            }
            $founders = explode(',', $_W['config']['setting']['founder']);
            $_W['isfounder'] = in_array($record['uid'], $founders);
            if (!empty($_W['siteclose']) && empty($_W['isfounder'])) {
                $data['msg'] = '站点已关闭，关闭原因：' . $_W['setting']['copyright']['reason'];
                $data['name'] = 'system';
                echo json_encode($data);
                die();
            }
            $cookie = array();
            $cookie['uid'] = $record['uid'];
            $cookie['lastvisit'] = $record['lastvisit'];
            $cookie['lastip'] = $record['lastip'];
            $cookie['hash'] = md5($record['password'] . $record['salt']);
            $session = base64_encode(json_encode($cookie));
            isetcookie('__session', $session, !empty($_GPC['rember']) ? 7 * 86400 : 0, true);
            $status = array();
            $status['uid'] = $record['uid'];
            $status['lastvisit'] = TIMESTAMP;
            $status['lastip'] = CLIENT_IP;
            user_update($status);
            if($record['type'] == ACCOUNT_OPERATE_CLERK) {
                header('Location:' . url('account/switch', array('uniacid' => $record['uniacid'])));
                die;
            }
            if(empty($forward)) {
                $forward = $_GPC['forward'];
            }
            if(empty($forward)) {
                $forward = '/web/index.php?c=account&a=display';
            }
            if ($record['uid'] != $_GPC['__uid']) {
                isetcookie('__uniacid', '', -7 * 86400);
                isetcookie('__uid', '', -7 * 86400);
            }
            pdo_delete('users_failed_login', array('id' => $failed['id']));

            /*$this->message("欢迎回来，{$record['username']}。", $forward);*/
            $result_url = '';
            $data['msg'] = 'true';
            load()->model('account');
            $role = uni_permission($record['uid'],$_GPC['__uniacid']);
            if(!empty($_GPC['__uniacid'])){
                if($role===FALSE){
                    $result_url = '../web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=sysset.account';
                }else{
                    $result_url = "../web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=shop";
                }
            }else{
                $result_url = '../web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=sysset.account';
            }

            $data['url'] = $result_url;
        } else {
            if (empty($failed)) {
                pdo_insert('users_failed_login', array('ip' => CLIENT_IP, 'username' => $username, 'count' => '1', 'lastupdate' => TIMESTAMP));
            } else {
                pdo_update('users_failed_login', array('count' => $failed['count'] + 1, 'lastupdate' => TIMESTAMP), array('id' => $failed['id']));
            }
            $data['msg'] = '登录失败，请检查您输入的用户名和密码！';
            $data['name'] = 'system';
            echo json_encode($data);
            die();
        }
        echo json_encode($data);
    }
    
}