<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/page_union.php';
class Index_EweiShopV2Page extends UnionWebPage
{
    public $membertype=array(
        1=>"正式员工",
        2=>"临时员工",
        3=>"外派",
        4=>"借调",
        5=>"其他"
    );
    public $membersex=array(
        0=>"其他",
        1=>"男",
        2=>"女",
    );
    public $columns=array(
        array('title' => "单位", 'field' => '', 'width' => 32),
        array('title' => '处室/部门', 'field' => '', 'width' => 32),
        array('title' => "姓名", 'field' => '', 'width' => 32),
        array('title' => "职务", 'field' => '', 'width' => 32),
        array('title' => "固话号", 'field' => '', 'width' => 32),
        array('title' => "手机号", 'field' => '', 'width' => 32),
        array('title' => "性别(男,女,其他)", 'field' => '', 'width' => 32),
        array('title' => "员工类型（正式员工 临时员工 外派 借调 其他）", 'field' => '', 'width' => 32),
        array('title' => '备注', 'field' => '', 'width' => 32),
    );

    public function main(){
        //计算还未看的用户
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition="  where m.uniacid=:uniacid and m.union_id=:union_id  and isdelete=0 ";
        $name=empty($_GPC['name']) ? "":$_GPC['name'];
        $mobile=empty($_GPC['mobile']) ? "":$_GPC['mobile'];

        $selector1=empty($_GPC['selector1']) ? "" : intval($_GPC['selector1']);
        $paras=array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']);
        if($name){
            $condition.=" and m.name like :name ";
            $paras[':name']=$name."%";
        }
        if($mobile){
            $condition.=" and m.mobile_phone like :mobile ";
            $paras[':mobile']=$mobile."%";
        }
        if($selector1){
            $condition.=" and m.department = :department ";
            $paras[':department']=$selector1;
        }
        $sql="select m.*,d.name as dname from ".tablename("ewei_shop_union_members")." as m LEFT JOIN ".
            tablename("ewei_shop_union_department")." as d ON d.id=m.department".
            $condition;
        $sql.=" order by sort desc,add_time desc LIMIT " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql, $paras);
        $total = pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." as m ".$condition,$paras);
        foreach ($list as &$row){
            $row['type']=$this->membertype[$row['type']];
            $row['sex']=$this->membersex[$row['sex']];
        }
        unset($row);

        $pager = pagination($total, $pindex, $psize);
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));
        include $this->template();
    }
    public function add(){
        $this->post();
    }
    public function edit(){
        $this->post();
    }
    public function post(){
        global $_W;
        global $_GPC;
        $action=unionUrl("member/index/post");
        $id=intval($_GPC['id']);
        //需要添加部门后才能导入
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));

        if(empty($department)){
            $this->model->show_json(0,array("url"=>unionUrl("member/department"),"message"=>"请先添加部门"));
        }
        if($id){
            $vo=pdo_fetch("select * from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and id=:id",array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],":id"=>$id));
            if($vo['year']){
                $vo['birthday']=$vo['year'].'-'.$vo['moth'].'-'.$vo['day'];
            }
        }
        if($_W['ispost']){
            $parssword="";
           $data=array(
               'name'=>trim($_GPC['name']),
               'sex'=>intval($_GPC['sex']),
               'type'=>intval($_GPC['type']),
               'department'=>intval($_GPC['department']),
               'remk'=>trim($_GPC['remk']),
               'activate'=>trim($_GPC['activate']),
               'company'=>trim($_GPC['company']),
               'duties'=>trim($_GPC['duties']),
               'telephone'=>trim($_GPC['telephone']),
               'sort'=>intval($_GPC['sort']),
               'status'=>1,
           );


           if($data['activate']==1 && $id){
               $data['entrytime']=time();
               //检查手机号是否有默认
               $count=pdo_fetchcolumn("select count(*) from ".tablename("ewei_shop_union_members")." where is_default=1 and mobile_phone=:mobile_phone",array(":mobile_phone"=>$vo['mobile']));
               if($count<=0){
                   $data['is_default']=1;
               }
           }
           if(isset($_GPC['date']) && !empty($_GPC['date'])){
               $date=explode('-',trim($_GPC['date']));
               $data['year']=$date[0];
               $data['moth']=$date[1];
               $data['day']=$date[2];
           }
           if($id){
               pdo_update("ewei_shop_union_members",$data,array("uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid'],'id'=>$id));
               $this->model->show_json(1,'修改成功');
           }else{
               $data['add_time']=time();

               //检查mobile_phone 是否重复

               $sql="select count(*) from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and mobile_phone=:mobile_phone ";
               $mobile_member_count= pdo_fetchcolumn($sql,array(":uniacid"=>$_W['uniacid'],'union_id'=>$_W['unionid'],':mobile_phone'=>trim($_GPC['mobile_phone'])));

               if(!empty($mobile_member_count)){
                   $this->model->show_json(0,'手机号码重复请检测');
               }
               $data['mobile_phone']=trim($_GPC['mobile_phone']);
               $data['uniacid']=$_W['uniacid'];
               $data['union_id']=$_W['unionid'];

               pdo_insert("ewei_shop_union_members",$data);
               $this->model->show_json(1,'添加成功');
           }
        }

        include $this->template("member/member_post");

    }

    public function export(){
        m('excel')->temp('批量导入工会会员模板', $this->columns);
    }

    public function import(){
        global $_W;
        global $_GPC;
        $action=unionUrl("member/department/edit");
        //需要添加部门后才能导入
        $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1";
        $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));

        if(empty($department)){
            $this->model->show_json(0,array("url"=>unionUrl("member/department"),"message"=>"请先添加部门"));
        }

        if($_W['ispost']){
            $url=$_GPC['inputxcel'];
            $data=$this->checkdata($url);

            if(is_error($data)){
                $this->model->show_json(0,$data['message']);
            }
            $this->model->show_json(1,$data['message']);
        }
        include $this->template("member/member_import");
    }

    private function checkdata($url){
        global $_W;
        $len=strpos($url,"union");
        if($len===false){
            return error(-1,'文件错误');
        }
        $filename=substr($url,$len);
        $uploadfile=ATTACHMENT_ROOT .$filename;
        try{
            if(!is_file($uploadfile)){
                throw new Exception("文件错误,联系管理员");
            }
            $ext =  strtolower( pathinfo($filename, PATHINFO_EXTENSION) );
            if($ext!='xlsx' && $ext!='xls' ){
                throw new Exception("请上传 xls 或 xlsx 格式的Excel文件");
            }
            $rows = m('excel')->importurl($uploadfile,$ext);
            if(is_error($rows)){
                throw new Exception($rows['message']);
            }
            $sql="select * from ".tablename("ewei_shop_union_department")." where union_id=:union_id and uniacid=:uniacid and enable=1";

            $department=pdo_fetchall($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid']));
            if(empty($department)){
                throw new Exception("请先添加部门");
            }
            $dep_list=array();
            foreach ($department as $dep){
                $dep_list[$dep['name']]=$dep['id'];
            }
            pdo_begin();
            $indertdata=array();
            $success=0;
            $error=0;
            $membertype=array_flip($this->membertype);
            $membersex=array_flip($this->membersex);

            foreach ($rows = m('excel')->importurl($uploadfile,$ext) as $key=> $row){
                $company=trim($row[0]);//单位
                $department=trim($row[1]);//部门
                $name=trim($row[2]); //姓名
                $duties=trim($row[3]);//职务
                $telephone=trim($row[4]);//固话号
                $mobile=trim($row[5]);//手机号
                $sex=trim($row[6]);//性别
                $type=trim($row[7]);
                $remk=trim($row[8]);
                if(empty($mobile) && empty($names)){
                    continue;
                }
                if(empty($mobile)){
                    throw new Exception(($key+1)."行,手机号码未录入");
                }
                if(empty($name)){
                    throw new Exception(($key+1)."行,姓名未录入");
                }
                if(!isset($membersex[$sex])){
                    throw new Exception(($key+1)."行,性别错误");
                }
                if(!isset($membertype[$type])){
                    throw new Exception(($key+1)."行,员工类型错误");
                }
                if(count($row)!==count($this->columns)){
                    throw new Exception("数据格式不正确");
                }
                if(!isset($dep_list[$department])){
                    throw new Exception("({$department})--此部门未添加请添加后导入");
                }
                $sql="select id from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and mobile_phone=:mobile_phone and isdelete=0 ";

                $cheack_mobile_repeat=pdo_fetchcolumn($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':mobile_phone'=>$mobile));


                $sql="select id from ".tablename("ewei_shop_union_members")." where uniacid=:uniacid and union_id=:union_id and name=:name and isdelete=0 ";

                $check_name_repeat=pdo_fetchcolumn($sql,array(":union_id"=>$_W['unionid'],':uniacid'=>$_W['uniacid'],':name'=>$name));


                if($cheack_mobile_repeat || $check_name_repeat){
                    $updatedata=array(
                        'name'=>$name,
                        'sex'=>$membersex[$sex],
                        'type'=>$membertype[$type],
                        'department'=>$dep_list[$department],
                        'remk'=>$remk,
                        'company'=>$company,
                        'duties'=>$duties,
                        'telephone'=>$telephone,
                    );
                    if($cheack_mobile_repeat){
                        $status=pdo_update("ewei_shop_union_members",$updatedata,array("id"=>$cheack_mobile_repeat));
                    }
                    if($check_name_repeat){
                        $status=pdo_update("ewei_shop_union_members",$updatedata,array("id"=>$check_name_repeat));
                    }

                    $status ? $success++:$error++;
                }else{
                    $indertdata=array(
                        'uniacid'=>$_W['uniacid'],
                        'mobile_phone'=>$mobile,
                        'name'=>$name,
                        'sex'=>$membersex[$sex],
                        'type'=>$membertype[$type],
                        'department'=>$dep_list[$department],
                        'union_id'=>$_W['unionid'],
                        'remk'=>$remk,
                        'add_time'=>time(),
                        'company'=>$company,
                        'duties'=>$duties,
                        'telephone'=>$telephone,
                    );
                    $status=pdo_insert("ewei_shop_union_members",$indertdata);
                    $status ? $success++:$error++;
                }
                $mobile_phone[]=$mobile;

            }
            if($mobile_phone){
                $mobile_reqpeat=$this->FetchRepeatMemberInArray($mobile_phone);
            }
            if(!empty($mobile_reqpeat)){
                $mobile_reqpeat=array_unique($mobile_reqpeat);
                throw new Exception("导入数据中重复的手机号".join(",",$mobile_reqpeat));
            }
            pdo_commit();
        }catch (Exception $e){
            pdo_rollback();
            return error(-1,$e->getMessage());
        }finally{
            @unlink($uploadfile);
        }

        return array("message"=>"导入成功数据".$success."失败数据".$error);
    }

    function FetchRepeatMemberInArray($array) {
        // 获取去掉重复数据的数组
        $unique_arr = array_unique ( $array );
        // 获取重复数据的数组
        $repeat_arr = array_diff_assoc ( $array, $unique_arr );
        return $repeat_arr;
    }


}