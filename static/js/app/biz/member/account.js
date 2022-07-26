define(['core'], function (core, tpl, picker) {
    var modal = {backurl: ''};
    modal.initLogin = function (params) {
        modal.backurl = params.backurl;
        $('#btnSubmit').click(function () {
            if ($('#btnSubmit').attr('stop')) {
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show('请输入11位手机号码!');
                return
            }
            if ($('#pwd').isEmpty()) {
                FoxUI.toast.show('请输入登录密码!');
                return
            }
            $('#btnSubmit').html('正在登录...').attr('stop', 1);
            core.json('account/login', {mobile: $('#mobile').val(), pwd: $('#pwd').val()}, function (ret) {
                FoxUI.toast.show(ret.result.message);
                if (ret.status != 1) {
                    $('#btnSubmit').html('立即登录').removeAttr('stop');
                    return
                } else {
                    $('#btnSubmit').html('正在跳转...')
                }
                setTimeout(function () {
                    if (modal.backurl) {
                        location.href = modal.backurl;
                        return
                    }
                    location.href = core.getUrl('')
                }, 1000)
            }, false, true)
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
    modal.initRf = function (params) {
        modal.backurl = params.backurl;
        modal.type = params.type;
        modal.endtime = params.endtime;
        modal.imgcode = params.imgcode;
        if (modal.endtime > 0) {
            modal.seconds = modal.endtime;
            modal.verifycode()
        }
        $('#btnCode').click(function () {
            if ($('#btnCode').hasClass('disabled')) {
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show('请输入11位手机号码');
                return
            }
            if (!$.trim($('#verifycode2').val()) && modal.imgcode == 1) {
                FoxUI.toast.show('请输入图形验证码');
                return
            }

            modal.seconds = 60;
            core.json('account/verifycode', {
                mobile: $('#mobile').val(),
                imgcode: $.trim($('#verifycode2').val()) || 0,
                temp: !modal.type ? "sms_reg" : "sms_forget"
            }, function (ret) {
                FoxUI.toast.show(ret.result.message);
                if (ret.status != 1) {
                    $('#btnCode').html('获取验证码').removeClass('disabled').removeAttr('disabled')
                }
                if (ret.status == 1) {
                    modal.verifycode()
                }
                if (ret.status == -1 && modal.imgcode == 1) {
                    $("#btnCode2").trigger('click')
                }
            }, false, true)
        });
        $("#btnCode2").click(function () {
            $(this).prop('src', '../web/index.php?c=utility&a=code&r=' + Math.round(new Date().getTime()));
            return false
        });
        $('#btnSubmit').click(function () {
            if ($('#btnSubmit').attr('stop')) {
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show('请输入11位手机号码');
                return
            }
            if (!$('#verifycode').isInt() || $('#verifycode').len() != 5) {
                FoxUI.toast.show('请输入5位数字验证码');
                return
            }
            if ($('#pwd').isEmpty()) {
                FoxUI.toast.show('请输入登录密码');
                return
            }
            if ($('#pwd').val() !== $('#pwd1').val()) {
                FoxUI.toast.show('两次密码输入不一致');
                return
            }
            $('#btnSubmit').html('正在处理...').attr('stop', 1);
            var url = !modal.type ? "account/register" : "account/forget";
            core.json(url, {
                mobile: $('#mobile').val(),
                verifycode: $('#verifycode').val(),
                pwd: $('#pwd').val()
            }, function (ret) {
                if (ret.status != 1) {
                    FoxUI.toast.show(ret.result.message);
                    var text = modal.type ? "立即找回" : "立即注册";
                    $('#btnSubmit').html(text).removeAttr('stop');
                    return
                } else {
                    FoxUI.alert(ret.result.message, '', function () {
                        if (modal.backurl) {
                            location.href = core.getUrl('account/login', {
                                mobile: $('#mobile').val(),
                                backurl: modal.backurl
                            })
                        } else {
                            location.href = core.getUrl('account/login', {mobile: $('#mobile').val()})
                        }
                    })
                }
            }, false, true)
        })
    };
    modal.initBind = function (params) {
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
            if (!$('#verifycode').isInt() || $('#verifycode').len() != 5) {
                FoxUI.toast.show('请输入5位数字验证码!');
                return
            }
            $('#btnSubmit').html('正在绑定...').attr('stop', 1);
            core.json('member/bind', {
                mobile: $('#mobile').val(),
                verifycode: $('#verifycode').val(),
                pwd: $('#pwd').val()
            }, function (ret) {
                if (ret.status == 0) {
                    FoxUI.toast.show(ret.result.message);
                    $('#btnSubmit').html('立即绑定').removeAttr('stop');
                    return
                }
                if (ret.status < 0) {
                    FoxUI.confirm(ret.result.message, "注意", function () {
                        core.json('member/bind', {
                            mobile: $('#mobile').val(),
                            verifycode: $('#verifycode').val(),
                            pwd: $('#pwd').val(),
                            confirm: 1
                        }, function (ret) {
                            if (ret.status == 1) {
                                FoxUI.alert('绑定成功!', '', function () {
                                    location.href = params.backurl ? atob(params.backurl) : core.getUrl('member')
                                });
                                return
                            }
                            FoxUI.toast.show(ret.result.message);
                            $('#btnSubmit').html('立即绑定').removeAttr('stop');
                            return
                        }, true, true)
                    }, function () {
                        $('#btnSubmit').html('立即绑定').removeAttr('stop')
                    });
                    return
                }
                FoxUI.alert('绑定成功!', '', function () {
                    location.href = params.backurl ? atob(params.backurl) : core.getUrl('member')
                })
            }, true, true)
        })
    };
    modal.initChange = function (params) {
        modal.endtime = params.endtime;
        if (modal.endtime > 0) {
            modal.seconds = modal.endtime;
            modal.verifycode()
        }
        $('#btnCode').click(function () {
            if ($('#btnCode').hasClass('disabled')) {
                return
            }
            if (!$('#mobile').isMobile()) {
                FoxUI.toast.show('请输入11位手机号码!');
                return
            }
            modal.seconds = 60;
            core.json('account/verifycode', {mobile: $('#mobile').val(), temp: 'sms_changepwd'}, function (ret) {
                if (ret.status != 1) {
                    FoxUI.toast.show(ret.result.message);
                    $('#btnCode').html('获取验证码').removeClass('disabled').removeAttr('disabled')
                }
                if (rer.status == 1) {
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
            if (!$('#verifycode').isInt() || $('#verifycode').len() != 5) {
                FoxUI.toast.show('请输入5位数字验证码!');
                return
            }
            if ($('#pwd').isEmpty()) {
                FoxUI.toast.show('请输入登录密码!');
                return
            }
            if ($('#pwd').val() !== $('#pwd1').val()) {
                FoxUI.toast.show('两次密码输入不一致!');
                return
            }
            $('#btnSubmit').html('正在修改...').attr('stop', 1);
            core.json('member/changepwd', {
                mobile: $('#mobile').val(),
                verifycode: $('#verifycode').val(),
                pwd: $('#pwd').val()
            }, function (ret) {
                if (ret.status != 1) {
                    FoxUI.toast.show(ret.result.message);
                    $('#btnSubmit').html('立即修改').removeAttr('stop');
                    return
                }
                FoxUI.alert('修改成功!', '', function () {
                    location.href = core.getUrl('member')
                })
            }, false, true)
        })
    };
    return modal
});