<?php
if (!(defined('IN_IA')))
{
    exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'union/core/inc/mobile_union.php';
class Welfareindex_EweiShopV2Page extends UnionMobilePage
{
    function main(){
        global $_W;
        global $_GPC;
        $union_id=$this->member['union_id'];

        $row=$_W['welfareconfig'];
        if(empty($row) || empty($row['welfarestatus'])){
            $this->message("福利未启用,请等待管理员启用");
        }
        $list=array();
        $examinestatus=false;
        if($row['marry']==0){
            $list['marry']=array(
                'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/fuli/ic_marry.png",
                'text'=>'结婚',
                'url'=>mobileUrl('union/welfare',array('type'=>1))
            );
            $examinestatus=true;
        }
        if($row['birth']==0){
            $list['birth']=array(
                'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/fuli/ic_rear.png",
                'text'=>'生育',
                'url'=>mobileUrl('union/welfare',array('type'=>2))
            );
            $examinestatus=true;
        }
        if($row['hospitalization']==0){
            $list['hospitalization']=array(
                'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/fuli/ic_hospital.png",
                'text'=>'住院',
                'url'=>mobileUrl('union/welfare',array('type'=>3))
            );
            $examinestatus=true;
        }
        if($row['retire']==0){
            $list['retire']=array(
                'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/fuli/ic_retire.png",
                'text'=>'退休',
                'url'=>mobileUrl('union/welfare',array('type'=>4))
            );
            $examinestatus=true;
        }
        if($row['funeral']==0){
            $list['funeral']=array(
                'imgurl'=>EWEI_SHOPV2_LOCAL."/plugin/union/template/mobile/default/static/images/fuli/ic_burial.png",
                'text'=>'丧葬',
                'url'=>mobileUrl('union/welfare',array('type'=>5))
            );
            $examinestatus=true;
        }

        $examineids=array();
        //查询本次可以进行审核的用户
        if(isset($row['examine']) && !empty($row['examine']) && $examinestatus){
            foreach ($row['examine'] as $exid){
                if($exid){
                    $examineids[]= $exid;
                }
            }
        }


        $examine=false;
        $examineids=!empty($examineids) ? array_unique($examineids) : array();
        if($examineids){
            if(count($examineids)==1){
                $examine_list=pdo_fetchall("select * from ".tablename("ewei_shop_union_examine")." where uniacid=:uniacid and union_id=:union_id and enable=1 and id=:id",array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid'],':id'=>$examineids[0]));
            }else{
                $examine_list=pdo_fetchall("select * from ".tablename("ewei_shop_union_examine")." where uniacid=:uniacid and id in(".implode(",",$examineids).") and union_id=:union_id and enable=1 ",array(":uniacid"=>$_W['uniacid'],":union_id"=>$_W['unionid']));
            }

            $ids=array();
            foreach ($examine_list as $row){
                $memberlist=json_decode($row['optionlist'],true);

                $templds=$this->getMemberids($memberlist);
                //var_dump($templds);
                $ids=$ids+$templds;
            }

            if(in_array($this->member['id'],$ids)){
                $examine=true;
            }
            if($this->member['replay_power']){
                $examine=true;
            }
        }


        include $this->template();
    }

    private function  getMemberids($memberlist){
            $ids=array();
            foreach ($memberlist as $row){
                $ids[]=$row['memberlist'];
            }
            return !empty($ids) ? array_unique($ids) : array();
    }
}