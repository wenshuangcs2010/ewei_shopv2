define(['core', 'tpl'], function (core, tpl) {
    var modal = {params: {}};
    modal.init = function (params, isteam) {
        var defaults = {
            orderid: 0,
            teamid: 0,
            wechat: {success: false},
            cash: {success: false},
            alipay: {success: false}
        };
        modal.params = $.extend(defaults, params || {});
        $('.pay-btn').click(function () {
            modal.pay(this)
        })
    };
    modal.pay = function (btn) {
        var btn = $(btn), type = btn.data('type') || '';
        if (type == '') {
            return
        }
        if (btn.attr('stop')) {
            return
        }
        btn.attr('stop', 1);
        if (type == 'wechat') {
            modal.payWechat(btn)
        } else if (type == 'alipay') {
            modal.payAlipay(btn)
        } else if (type == 'credit') {
            FoxUI.confirm('确认要支付吗?', '提醒', function () {
                modal.complete(btn, type)
            }, function () {
                btn.removeAttr('stop')
            })
        } else {
            modal.complete(btn, type)
        }
    };
    modal.payWechat = function (btn) {

        if(core.ish5app()){
            appPay('wechat', modal.params.ordersn, modal.params.money, false, function () {
                var settime = setInterval(function () {
                    $.getJSON(core.getUrl('groups/pay/orderstatus'),{id: modal.params.orderid},function (data) {
                        if (data.result.status>=1){
                            clearInterval(settime);
                            modal.complete(btn, 'wechat')
                        }
                    });
                },1000);
            });
            return;
        }

        var wechat = modal.params.wechat;
        if (!wechat.success) {
            return
        }
        if (wechat.weixin) {
            WeixinJSBridge.invoke('getBrandWCPayRequest', {
                'appId': wechat.appid ? wechat.appid : wechat.appId,
                'timeStamp': wechat.timeStamp,
                'nonceStr': wechat.nonceStr,
                'package': wechat.package,
                'signType': wechat.signType,
                'paySign': wechat.paySign,
            }, function (res) {
                if (res.err_msg == 'get_brand_wcpay_request:ok') {
                    modal.complete(btn, 'wechat')
                } else if (res.err_msg == 'get_brand_wcpay_request:cancel') {
                    FoxUI.toast.show('取消支付');
                    btn.removeAttr('stop')
                } else {
                    btn.removeAttr('stop');
                    alert(res.err_msg)
                }
            })
        }
        if (wechat.weixin_jie) {
            var img = core.getUrl('index/qr',{url:wechat.code_url});
            $('#qrmoney').text(modal.params.money);
            $('.order-weixinpay-hidden').show();
            $('#btnWeixinJieCancel').unbind('click').click(function(){
                btn.removeAttr('stop');
                clearInterval(settime);
                $('.order-weixinpay-hidden').hide();
            });
            var settime = setInterval(function () {
                $.getJSON(core.getUrl('groups/pay/orderstatus'),{id: modal.params.orderid},function (data) {
                    if (data.result.status>=1){
                        clearInterval(settime);
                        modal.complete(btn, 'wechat')
                    }
                });
            },1000);
            $('.verify-pop').find('.close').unbind('click').click(function () {
                $('.order-weixinpay-hidden').hide();
                btn.removeAttr('stop');
                clearInterval(settime);
            });
            $('.verify-pop').find('.qrimg').attr('src', img).show()
        }

    };
    modal.payAlipay = function (btn) {
        var alipay = modal.params.alipay;
        if (!alipay.success) {
            return
        }
        location.href = core.getUrl('order/alipay', {url: alipay.url})
    };
    modal.complete = function (btn, type) {
        core.json('groups/pay/complete', {
            orderid: modal.params.orderid,
            teamid: modal.params.teamid,
            type: type,
            isteam: modal.params.isteam
        }, function (pay_json) {
            if (pay_json.status == 1) {
                if (modal.params.teamid > 0) {
                    location.href = core.getUrl('groups/team/detail', {
                        orderid: modal.params.orderid,
                        teamid: modal.params.teamid
                    })
                } else {
                    location.href = core.getUrl('groups/orders/detail', {
                        orderid: modal.params.orderid,
                        teamid: modal.params.teamid
                    })
                }
                return
            }
            FoxUI.loader.hide();
            btn.removeAttr('stop');
            FoxUI.toast.show(pay_json.result.message)
        }, false, true)
    };
    return modal
});