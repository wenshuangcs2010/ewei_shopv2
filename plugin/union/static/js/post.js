define(['core','jquery.gcjs','foxui.picker'], function (core) {
    var modal = {page: 1, params: {}};
    var defaults = {
    };

    modal.suggestions_init=function(){
        $("#btnSubmit").click(function () {
            if ($('#title').isEmpty()) {
                FoxUI.toast.show('请输入标题');
                return
            }
            if ($('#description').isEmpty()) {
                FoxUI.toast.show('请输入详情');
                return
            }
            var id=$("#id").isEmpty() ? 0 : $("#id").val();

            if(id>0){
                FoxUI.toast.show('已提交的建言禁止修改');
                return
            }
            FoxUI.confirm('提交后将不能进行修改和删除?', '提示', function () {
                core.json('union/index/get_suggestions_post',{'id':id,"title":$('#title').val(),'description':$("#description").val()}, function (ret) {
                    if(ret.status==0){
                        FoxUI.toast.show(ret.message);
                        return
                    }
                    window.location.href=ret.result.url;
                }, false, true)

            })

        })
    };
    modal.welfare_init=function(){

        $("#btn-submit").click(function () {
            var bont=$(this);
            if(bont.attr("stop")==1){
                FoxUI.toast.show("短时间内禁止重复提交");
                return
            }
            $(this).attr("stop",1);
            if ($('#datetime').isEmpty()) {
                bont.removeAttr("stop");
                FoxUI.toast.show('请输入福利时间');
                return
            }
            if ($('#money').isEmpty()) {
                bont.removeAttr("stop");
                FoxUI.toast.show('请输入福利金额');
                return
            }
            if ($('#money').val()<=0) {
                bont.removeAttr("stop");
                FoxUI.toast.show('请检查输入福利金额');
                return
            }

            var images = [];
            $('#images').find('li').each(function () {
                images.push($(this).data('filename'))
            });
            core.json('union/welfare/post',{
                'type':$("#type").val(),
                'images':images,
                'remarks':$("#remarks").val(),
                'money':$('#money').val(),
                'datetime':$('#datetime').val(),
                'moneytype':$("#moneytype").val(),
                'bankcard':$("#bankcard").val(),
                'bankname':$("#bankname").val(),
            }, function (ret) {
                bont.removeAttr("stop");
                FoxUI.toast.show(ret.result.message);
                if(ret.status==1){
                    window.location.href=core.getUrl("union.welfare.walist",{type:$("#type").val()});
                }
            }, false, true)
        })
    };
    modal.lyaddress_install=function(){
        $('#btn-submit').click(function () {
            var _botton_btn=$(this);


            if($("#name").isEmpty()){
                FoxUI.toast.show('请检查输入姓名');

                return
            }


            if($("#imid").isEmpty()){
                FoxUI.toast.show('请输入身份证号');

                return
            }
            //港澳台居住证的识别 19年和20开头
            var waidireg=/^8[123]0000(?:19|20)\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])\d{3}[\dX]$/;

            if(!$("#imid").isIDCard() && !waidireg.test($("#imid").val())
            ){
                FoxUI.toast.show('身份证号格式不正确');

                return
            }


            if($("#mobile").isEmpty()){
                FoxUI.toast.show('请输入手机号');

                return
            }
            if(!$("#mobile").isMobile()){
                FoxUI.toast.show('请输入正确手机号');

                return
            }
            if($("#number").isEmpty()){
                FoxUI.toast.show('请输入入住人数');

                return
            }


            var images = [];
            $('#images').find('li').each(function () {
                images.push($(this).data('filename'))
            });
            var stop=_botton_btn.attr("data-stop");
            if(stop==1){
                FoxUI.toast.show('请勿重复提交');
                return
            }
            _botton_btn.attr("data-stop",1);
            FoxUI.confirm("确认预订么？",function () {
                core.json('union/lyhome/lyaddressline/add',{
                    'id':$("#id").val(),
                    'name':$("#name").val(),
                    'mobile':$("#mobile").val(),
                    'datetime':$("#date").val(),
                    'number':$("#number").val(),
                    'images':images,
                    'imid':$("#imid").val(),
                }, function (ret) {
                    _botton_btn.attr("data-stop",0);
                    if(ret.status==1){
                        FoxUI.alert('预约成功等待酒店审核','提示',function () {
                            window.location.href=core.getUrl("union.lyhome.lyaddressline.view",{id:$("#id").val()});
                        });

                    }else{
                        FoxUI.toast.show(ret.result.message);
                        return
                    }
                }, false, true);
            });

        })
    }

    modal.add_friendship_init=function(){
        $('#btn-submit').click(function () {
            var images = [];
            $('#images').find('li').each(function () {
                images.push($(this).data('filename'))
            });
            var life_images=[];
            $('#images1').find('li').each(function () {
                life_images.push($(this).data('filename'))
            });
            if($("#name").isEmpty()){
                FoxUI.toast.show('请检查输入姓名');
                return
            }
            if($("#age").isEmpty()){
                FoxUI.toast.show('请检查输入年龄');
                return
            }
            core.json('union/friendship/addfriendship',{
                'id':$("#id").val(),
                'name':$("#name").val(),
                'age':$("#age").val(),
                'sex':$("#sex").val(),
                'maritalstatus':$('#maritalstatus').val(),
                'height':$('#height').val(),
                'education':$("#education").val(),
                'address':$("#address").val(),
                'income':$("#income").val(),
                'work':$("#work").val(),
                'character':$("#character").val(),
                'other':$("#other").val(),
                'contact':$("#contact").val(),
                'otherage':$("#otherage").val(),
                'othercondition':$("#othercondition").val(),
                'declaration':$("#declaration").val(),
                'images':images,
                'life_images':life_images,
                'otherheight':$("#otherheight").val(),
                'othereducation':$("#othereducation").val(),
                'otheraddress':$("#otheraddress").val(),
                'otherwork':$("#otherwork").val(),
                'otherincome':$("#otherincome").val(),
                'othercharacter':$("#othercharacter").val(),

            }, function (ret) {
                if(ret.status==1){
                    FoxUI.toast.show("发布成功");
                    setTimeout(function(){
                        window.location.href=core.getUrl("union/friendship/friendship_edit",{type:1});
                    }, 3000);

                }else{
                    FoxUI.toast.show(ret.result.message);
                    return
                }
            }, false, true)
        })
    };
    modal.add_message_init=function(params){
            $("#btn-submit").click(function (){
                var text=$(".friendaddmessage").val();
                if($(".friendaddmessage").isEmpty()){
                    FoxUI.toast.show("留言不能为空");
                    return
                }
                console.log(params);
                if(params.friendship_id==0 || typeof (params.friendship_id)=="undefined"){
                    FoxUI.toast.show("数据访问错误，请返回重试");
                    return
                }

                core.json('union/friendship/addmessage',{"friendship_id":params.friendship_id,'text':text},function (ret) {
                    FoxUI.toast.show("留言增加成功");
                    setTimeout(function(){
                        window.location.href=core.getUrl("union/friendship/view",{id:params.friendship_id});
                    }, 1000);
                },false,true)
            })
    };
    modal.venue_init=function(params){
        $("#btn-submit").click(function (){
            var id=$("#id").val();
            var date=$("#date").val();
            var starttime=$("#starttime").val();
            var endtime=$("#endtime").val();
            if($("#date").isEmpty()){
                FoxUI.toast.show("请选择预订日期");
                return
            }
            if($("#starttime").isEmpty()){
                FoxUI.toast.show("请选择预订开始时间");
                return
            }
            if($("#endtime").isEmpty()){
                FoxUI.toast.show("请选择预订结束时间");
                return
            }
            core.json('union/venue/add',{
                'id':id,
                'date':date,
                'starttime':starttime,
                'endtime':endtime,
            },function (ret) {
                if(ret.status==0){
                    FoxUI.toast.show(ret.result.message);
                    return
                }
                FoxUI.toast.show("预订成功");
                history.back();
            },false,true)
        })
    };
    modal.memberactivity_init=function(){
        $("#btnSubmit").unbind("click").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var id=$("#id").val();
            var description=$("#description").val();
            core.json('union/memberactivity/status', {id:id,description:description}, function (ret) {
                if(ret.status!=1){
                    FoxUI.toast.show(ret.result.message);
                }else{
                    window.location.href=ret.result.url;
                }
                return
            })
        })
    };
    modal.train_init=function(){
        $("#btnSubmit").unbind("click").click(function(e){
            e.preventDefault();
            e.stopPropagation();
            var id=$("#id").val();
            var description=$("#description").val();
            core.json('union/train/status', {id:id,description:description}, function (ret) {

                if(ret.status!=1){
                    FoxUI.toast.show(ret.result.message);
                }else{
                    window.location.href=ret.result.url;
                }

                return
            })
        })
    };
    modal.loadqutime=function(intDiff,act_id){
        window.items=window.setInterval(function(){
            var day=0,
                hour=0,
                minute=0,
                second=0;//时间默认值
            if(intDiff > 0){
                day = Math.floor(intDiff / (60 * 60 * 24));

                minute = Math.floor(intDiff / 60) - (day * 24 * 60);
                second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
            }

            if (minute <= 9) minute = '0' + minute;
            if (second <= 9) second = '0' + second;
            $('.lasttimes').html(minute+":"+second);
            intDiff--;
            if(intDiff==0){
                //时间结束的概念
                FoxUI.alert("答题时间已经到了", "提示", function () {
                    window.location.href=core.getUrl("union/quiz/showquzi",{act_id:act_id});
                });
            }
        }, 1000);
    }
    modal.quizinit=function(params){

        //需要知道当前题目位置
        var index=0;
        var thable_leng=$(".quiz_bg .list-number").length;//全部题目数量
        $(".list-number").each(function(){
            if($(this).is(":visible")){
                index=$(this).data("index");
            }
        });
        if(index>=thable_leng){
            $("#btn-submit").html("提交");
            $("#btn-submit").attr("data-submit",1);
        }

        $(".answer").unbind("click").on("click",function(){
           //alert($(this).("id"));
            var childer_show=$(this).children(".external");
            var qustype=$(this).data("qus_type");
            if(qustype==1){//单选题目的时候
                $(".answer").data("selected",0);
                $(".answer").children('.external').hide();
            }
            if(childer_show.is(':visible')){
                childer_show.hide();
                $(this).data("selected",0);
            }else{
                childer_show.show();
                $(this).data("selected",1);
            }
        })
        $("#btn-submit").unbind("click").on("click",function(){
            var postid=Array();
            $(".list-number").each(function(){
                if($(this).is(":visible")){
                    index=$(this).data("index");//点击之后的位置
                }
            })
            $(".answer").each(function(index,value){
                var value_info=$(value);
                if(value_info.data("selected")==1){
                    postid.push(value_info.data("id"));
                    params.quizid=$(this).data("quizid");
                }
            })

            if(postid.length==0){
                FoxUI.toast.show("请选择一个答案");
                return;
            }
            params.selectid=postid.join(",");


            var table_list=$("#list_index_"+index);

            console.log(index);
            console.log(thable_leng);
            console.log(table_list);

            core.json('union/quiz/user_chick',params, function (ret) {
                var status=$("#btn-submit").attr("data-submit");
                if((index+1)>=thable_leng){
                    $("#btn-submit").html("提交");
                    $("#btn-submit").attr("data-submit",1);
                }
                if(status==1){

                    window.location.href=core.getUrl("union/quiz/jinshai",{act_id:params.act_id});
                }else{
                    $(".list-number").hide();
                    table_list.show();
                    $(".answer").data("selected",0);
                    $(".answer").children('.external').hide();
                }
            },true,true)

        })
    };
    modal.research_init=function(params){
        var dataparams={};
        dataparams.research_id=params.id;
        $(".answer").unbind("click").click(function () {
            $(".answer .external").hide();
            $(this).find(".external").show();
            dataparams.option_id=$(this).data('id');
        });
        $("#btn-submit").click(function () {
            if(dataparams.option_id==0){
                FoxUI.toast.show("请选择一个选项");
                return ;
            }
            FoxUI.confirm("确认选择并提交么？",'提示',function () {
                core.json('union/research/sign',dataparams, function (ret) {
                    if(ret.status!=1){
                        FoxUI.toast.show(ret.result.message);
                    }else{
                        FoxUI.toast.show(ret.result.message);
                        location.reload();
                    }
                },true,true)
            })
        })

    };
    modal.sign_init=function(params){
        $(".btn-default").click(function(){
            if($("#name").isEmpty()){
                FoxUI.toast.show("抱歉!请输入姓名");
                return ;
            }
            if($("#mobile").isEmpty()){
                FoxUI.toast.show("抱歉!请输入手机号码");
                return ;
            }
            if(!$("#mobile").isMobile()){
                FoxUI.toast.show("抱歉!手机号码错误");
                return ;
            }


            params.username=$("#name").val();
            params.mobile=$("#mobile").val();
            core.json('union/report/sign_list',params, function (ret) {
                if(ret.status!=1){
                    FoxUI.toast.show(ret.result.message);
                }else{
                    FoxUI.toast.show(ret.result.message);
                    setTimeout(function(){
                        window.location.href=core.getUrl("union/report/sign_list",{id:params.id,union_id:params.union_id});
                    },3000);
                }
            },true,true)
        })
    };
    modal.addassociation_init=function(params){
        $('#btnSubmit').unbind("click").click(function () {
            var content=$("#description").val();
            params.content=content;
            if(params.type=="join"){

                FoxUI.confirm("确认加入当前小组么？",'提示',function () {
                    core.json('union/association/addtions',params, function (ret) {
                        if(ret.status!=1){
                            FoxUI.toast.show(ret.result.message);
                            return ;
                        }
                        FoxUI.toast.show(ret.result.msg);
                        setTimeout(function(){
                            window.history.back(-1);
                        },1000);
                    })
                })
            }else if(params.type=="signout"){
                FoxUI.confirm("确认退出当前小组么？",'提示',function () {
                    core.json('union/association/addtions',params, function (ret) {
                        if(ret.status!=1){
                            FoxUI.toast.show(ret.result.message);
                            return ;
                        }else{
                            FoxUI.toast.show(ret.result.msg);
                            setTimeout(function(){
                                window.history.back(-1);
                            },1000);
                        }
                    })
                })
            }else{
                FoxUI.toast.show("未到申请时间，或者申请时间已过过期");
                return ;
            }
        })
    };
    modal.voteoption_init=function(params){
        $('#btn-submit').unbind("click").click(function () {
            params.optionid=$(this).data("id");
            FoxUI.confirm("确认投票给当前候选人么？",'提示',function () {
                core.json('union/vote/ticket',params, function (ret) {
                    //if(ret.status!=1){
                        FoxUI.alert(ret.result.message,'注意');
                    //}
                })
            });

        })
    };
    modal.add_leavmessage_init=function(params){
            $("#btn-submit").unbind("click").on("click",function () {
                var title=$("#title").val();
                var desc=$("#desc").val();
                if(title==""){
                    FoxUI.alert("请填写标题",'注意');
                    return ;
                }
                if(desc==""){
                    FoxUI.alert("请填写详细描述",'注意');
                    return ;
                }
                core.json('union/leavmessage/addmessage',{title:title,desc:desc}, function (ret) {
                    if(ret.status!=1){
                        FoxUI.alert(ret.result.message,'注意');
                        return;
                    }else{
                        FoxUI.alert(ret.result.message,'注意');
                        setTimeout(function(){
                            window.history.back(-1);
                        },2000);
                        return ;
                    }
                },true,true)
            })
    };
    modal.add_replay_init=function (params) {
        $("#btn-submit").unbind("click").on("click",function () {

            var desc=$("#desc").val();

            if(desc==""){
                FoxUI.alert("请填写详细描述",'注意');
                return ;
            }
            core.json('union/leavmessage/addreplay',{id:params.id,parent_id:params.parent_id,desc:desc}, function (ret) {
                if(ret.status!=1){
                    FoxUI.alert(ret.result.message,'注意');
                    return;
                }else{
                    FoxUI.alert(ret.result.message,'注意');
                    setTimeout(function(){
                        window.history.back(-1);
                    },2000);
                    return ;
                }
            },true,true)
        })
    }
    return modal;
})