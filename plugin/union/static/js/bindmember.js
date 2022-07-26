define(['core','jquery.gcjs'], function (core) {

    var modal = {seconds:0};
    modal.bindclick=function(){
        $(".list-group-li li").unbind("click").on("click",function(){

            $("#unionname").val( $(this).text());
            $("#unionid").val($(this).data("id"));
            $('#serchhtml').hide();
        })
    };
    modal.verifycode = function () {
        modal.seconds--;
        if (modal.seconds > 0) {
            $('#btnCode').html(modal.seconds + '秒后重发').addClass('disabled').attr('disabled', 'disabled');
            setTimeout(function () {
                modal.verifycode()
            }, 1000)
        } else {
            $('#btnCode').html('获取验证码').removeClass('disabled').removeAttr('disabled')
        }
    };
    modal.init=function (params) {
        //单位输入监听
        $(".fui-content").click(function () {
            $('#serchhtml').hide();
        })
        $('#unionname').bind('input propertychange', function () {
            if ($.trim($(this).val()) == '') {
                $('#serchhtml').hide();
            }else{
                var keyword=$(this).val()
                core.json('union/activityhome/getuninonlist', {keyword:keyword}, function (ret) {
                    var html="";
                    $('#searchlist').html("");
                    $.each(ret, function(index, val) {
                        //console.log(val.title);
                        html+="<a class='list-group-li' ><li data-id='"+val.id+"'>"+val.title+"</li></a>";
                    });
                    $('#searchlist').append(html);
                    $('#serchhtml').show();
                    modal.bindclick();
                })
            }
        });


        $("#btnCode2").click(function () {
            $(this).prop('src', '../web/index.php?c=utility&a=code&r=' + Math.round(new Date().getTime()));
            return false
        });
        $('#btnCode').click(function () {
            if ($('#btnCode').hasClass('disabled')) {
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show('请输入11位手机号码!');
                return
            }
            modal.seconds = 60;
            core.json('account/verifycode',
                {
                    mobile: $('#mobile').val(),
                    temp: 'sms_bind',
                    imgcode: $.trim($('#verifycode2').val()) || 0,
                },
                function (ret) {
                    if (ret.status != 1) {
                        FoxUI.toast.show(ret.result.message);
                        $('#btnCode').html('获取验证码').removeClass('disabled').removeAttr('disabled')
                    }
                    if (ret.status == 1) {
                        modal.verifycode()
                    }
                }, false, true)
        });

        $('#btn-submit').click(function () {

            if ($('#btn-submit').attr('stop')) {
                return
            }
            var unionid=$("#unionid").val();
            var realname=$("#realname").val();


            if ($('#realname').isEmpty()) {
                FoxUI.toast.show('请输入姓名!');
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show('请输入11位手机号码!');
                return
            }
            if($("#unionname").isEmpty()){
                FoxUI.toast.show('请输入单位名称!');
                return
            }
            if (!$('#verifycode').isInt() || $('#verifycode').len() != 5) {
                FoxUI.toast.show('请输入5位数字验证码!');
                return
            }
            $('#btn-submit').html('正在注册...').attr('stop', 1);
            core.json('union/activityhome/reg', {
                unionid:unionid,
                unionname:$("#unionname").val(),
                realname:realname,
                mobile: $('#mobile').val(),
                verifycode: $('#verifycode').val(),
                pwd: $('#pwd').val()
            }, function (ret) {
                if (ret.status == 3) {
                    $(".errormsg").show();
                    $('#btn-submit').html('进入答题').removeAttr('stop');
                    return
                }
                if (ret.status != 1) {
                    FoxUI.toast.show(ret.result.message);
                    $('#btn-submit').html('进入答题').removeAttr('stop');
                    return
                }
                FoxUI.alert('立即去答题!', '', function () {
                    location.href = core.getUrl('union/quiz')
                })
            }, false, true)

        })
    }

    return modal;
});