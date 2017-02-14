define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
    modal.allow = function () {

        if (!$('#money').isNumber() || $('#money').isEmpty()) {
            return false
        } else {
            var money = parseFloat($('#money').val());
            if (money <= 0) {
                return false
            }
            if (modal.min > 0) {
                if (money < modal.min) {
                    return false
                }
            }
            if (money > modal.max) {
                return false
            }
        }

        if (modal.withdrawcharge > 0 && money != 0) {
            var deductionmoney = money / 100 * modal.withdrawcharge;
            deductionmoney = Math.round(deductionmoney*100)/100;

            if (deductionmoney >= modal.withdrawbegin && deductionmoney <= modal.withdrawend) {
                deductionmoney = 0;
            }

            var realmoney = money - deductionmoney;
            realmoney = Math.round(realmoney*100)/100;

            $("#deductionmoney").html(deductionmoney);
            $("#realmoney").html(realmoney);
            $(".charge-group").show();
        }


        return true
    };
    modal.init = function (params) {
        modal.withdrawcharge = params.withdrawcharge;
        modal.withdrawbegin = params.withdrawbegin;
        modal.withdrawend = params.withdrawend;
        modal.min = params.min;
        modal.max = params.max;

        var checked_applytype = $('#applytype').find("option:selected").val();
        if (checked_applytype == 2) {
            $('.ab-group').show();
            $('.alipay-group').show();
            $('.bank-group').hide();
        } else if (checked_applytype == 3) {
            $('.ab-group').show();
            $('.alipay-group').hide();
            $('.bank-group').show();
        } else {
            $('.ab-group').hide();
            $('.alipay-group').hide();
            $('.bank-group').hide();
        }

        $('#applytype').change(function () {
            var applytype = $(this).find("option:selected").val();
            if (applytype == 2) {
                $('.ab-group').show();
                $('.alipay-group').show();
                $('.bank-group').hide();
            } else if (applytype == 3) {
                $('.ab-group').show();
                $('.alipay-group').hide();
                $('.bank-group').show();
            } else {
                $('.ab-group').hide();
                $('.alipay-group').hide();
                $('.bank-group').hide();
            }
        });

        $('#btn-all').click(function () {
            if (modal.max <= 0) {
                return
            }
            $('#money').val(modal.max);
            if (!modal.allow()) {
                $('#btn-next').addClass('disabled')
            } else {
                $('#btn-next').removeClass('disabled')
            }
        });
        $('#money').bind('input propertychange', function () {
            if (!modal.allow()) {
                $('#btn-next').addClass('disabled')
            } else {
                $('#btn-next').removeClass('disabled')
            }
        });
        $('#btn-next').click(function () {
            var money = $.trim($('#money').val());
            if ($(this).attr('submit')) {
                return
            }
            if (!modal.allow()) {
                return
            }
            if ($('.btn-withdraw').attr('submit')) {
                return
            }
            var money = $('#money').val();
            if (!$('#money').isNumber()) {
                FoxUI.toast.show('请输入提现金额!');
                return
            }

            var html = '';
            var realname = '';
            var alipay = '';
            var alipay1 = '';
            var bankname = '';
            var bankcard = '';
            var bankcard1 = '';
            var applytype = $('#applytype').find("option:selected").val();
            var typename = $('#applytype').find("option:selected").html();

            if (applytype == undefined) {
                FoxUI.toast.show('未选择提现方式，请您选择提现方式后重试!');
                return
            }

            if (applytype == 0) {
                html = typename;
            } else if (applytype == 2) {
                if ($('#realname').isEmpty()) {
                    FoxUI.toast.show('请填写姓名!');
                    return
                }
                if ($('#alipay').isEmpty()) {
                    FoxUI.toast.show('请填写支付宝帐号!');
                    return
                }
                if ($('#alipay1').isEmpty()) {
                    FoxUI.toast.show('请填写确认帐号!');
                    return
                }
                if ($('#alipay').val() != $('#alipay1').val()) {
                    FoxUI.toast.show('支付宝帐号与确认帐号不一致!');
                    return
                }
                realname = $('#realname').val();
                alipay = $('#alipay').val();
                alipay1 = $('#alipay1').val();
                html = typename + "?<br>姓名:" + realname + "<br>支付宝帐号:" + alipay;
            } else if (applytype == 3) {
                if ($('#realname').isEmpty()) {
                    FoxUI.toast.show('请填写姓名!');
                    return
                }
                if ($('#bankcard').isEmpty()) {
                    FoxUI.toast.show('请填写银行卡号!');
                    return
                }
                if (!$('#bankcard').isNumber()) {
                    FoxUI.toast.show('银行卡号格式不正确!');
                    return
                }
                if ($('#bankcard1').isEmpty()) {
                    FoxUI.toast.show('请填写确认卡号!');
                    return
                }
                if ($('#bankcard').val() != $('#bankcard1').val()) {
                    FoxUI.toast.show('银行卡号与确认卡号不一致!');
                    return
                }
                realname = $('#realname').val();
                bankcard = $('#bankcard').val();
                bankcard1 = $('#bankcard1').val();
                bankname = $('#bankname').find("option:selected").html();
                html = typename + "?<br>姓名:" + realname + "<br>" + bankname + " 卡号:" + $('#bankcard').val();
            }

            if (applytype < 2) {
                var confirm_msg = '确认要' + html + "?";
            } else {
                var confirm_msg = '确认要' + html;
            }

            if (modal.withdrawcharge > 0) {
                confirm_msg += ' 扣除手续费 ' + $("#deductionmoney").html() + ' 元,实际到账金额 ' + $("#realmoney").html() + ' 元';
            }

            FoxUI.confirm(confirm_msg, function () {
                $('.btn-withdraw').attr('submit', 1);
                core.json('member/withdraw/submit', {applytype: applytype, realname: realname, alipay: alipay, alipay1: alipay1, bankname: bankname, bankcard: bankcard, bankcard1: bankcard1, money: money}, function (rjson) {
                    if (rjson.status != 1) {
                        $('.btn-widthdraw').removeAttr('submit');
                        FoxUI.toast.show(rjson.result.message);
                        return
                    }
                    FoxUI.toast.show('提现申请成功，请等待审核!');
                    location.href = core.getUrl('member/log',{type:1})
                }, true, true)
            })
        })
    };
    return modal
});