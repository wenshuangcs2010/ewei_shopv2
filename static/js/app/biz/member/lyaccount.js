define(['core'], function (core, tpl, picker) {
    var modal = {backurl: ''};

    modal.initBind=function (params) {
        modal.endtime = params.endtime;
        modal.backurl = params.backurl;
        if (modal.endtime > 0) {
            modal.seconds = modal.endtime;
            modal.verifycode()
        }
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

        $('#btnSubmit').click(function () {
            if ($('#btnSubmit').attr('stop')) {
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show('请输入11位手机号码!');
                return
            }
            if (!$('#verifycode2').isInt() || $('#verifycode2').len() != 4) {
                FoxUI.toast.show('请输入图形验证码');
                return
            }
            if (!$('#verifycode').isInt() || $('#verifycode').len() != 5) {
                FoxUI.toast.show('请输入5位数字验证码!');
                return
            }

            $('#btnSubmit').html('正在绑定...').attr('stop', 1);
            core.json('union/lyhome/member/bind', {
                mobile: $('#mobile').val(),
                verifycode: $('#verifycode').val(),
                verifycode2:$('#verifycode2').val(),
                pwd: $('#pwd').val()
            }, function (ret) {
                if (ret.status == 0) {
                    FoxUI.toast.show(ret.result.message);
                    $('#btnSubmit').html('立即绑定').removeAttr('stop');
                    return
                }
                FoxUI.alert('绑定成功!', '', function () {
                    location.href = params.backurl ? atob(params.backurl) : core.getUrl('union.lyhome.member')
                })
            }, true, true)
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
    return modal;
})