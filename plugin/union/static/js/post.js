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
            FoxUI.confirm('提交后禁止修改和删除?', '提示', function () {
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
                FoxUI.toast.show('请输入福利时间');
                return
            }
            if ($('#money').isEmpty()) {
                FoxUI.toast.show('请输入福利金额');
                return
            }
            if ($('#money').val()<=0) {
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
            }, function (ret) {
                bont.removeAttr("stop");
                FoxUI.toast.show(ret.result.message);

            }, false, true)
        })
    };
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
                FoxUI.toast.show('请检查输入姓名');
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
                    FoxUI.toast.show("数据增加成功");
                    setTimeout(function(){
                        window.location.href=core.getUrl("union/friendship/friendship_edit");
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
                    FoxUI.toast.show("数据增加成功");
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
                FoxUI.toast.show(ret.result.message);
                return
            })
        })
    }
    return modal;
})