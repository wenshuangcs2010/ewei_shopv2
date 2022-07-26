define(['jquery', 'jquery.gcjs'], function (jQuery) {
    var modal = {
        submitUrl:''
    };
    modal.init=function(params){
        modal.params = $.extend(modal.params, params || {});
        $(".layui-btn").click(function (e) {
            e.preventDefault();
            modal.submit();
        })

    }
    modal.submit=function(){
        $('.layui-btn').attr('disabled',true).html('正在登录...');

        if($("[name='username']").isEmpty()){

            $('.layui-btn').removeAttr('disabled').html('立即登录');
            return;
        }

        if($('[name="password"]').isEmpty()){

            $('.layui-btn').removeAttr('disabled').html('立即登录');
            return;
        }
        $.ajax({
            url: modal.submitUrl,
            type:'post',
            data: {username: $(":input[name=username]").val() ,password: $(":input[name=password]").val()},
            dataType:'json',
            cache:false,
            success:function(ret){
                if(ret.status==1){
                    location.href = ret.result.url;
                    return;
                }
                $('#btn-login').removeAttr('disabled').html('登录');
                $(":input[name=password]").select();
                layer.msg(ret.result.message);
                $('.layui-btn').removeAttr('disabled').html('立即登录');
            }
        })
    }
    return modal;
})