define(['core', 'tpl'], function (core, tpl, op) {
    var modal = {goods: false, address: 0, addressid: 0, canpay: 0, needdispatchpay: 0, settime: 0};
    modal.init = function (params) {
        modal.goods = goods = params.goods;
        modal.log = goods = params.log;
        var loadAddress = false;
        if (typeof(window.selectedAddressData) !== 'undefined') {
            modal.address = window.selectedAddressData;
            modal.addressid = modal.address.id;
            $("#address_select").val(modal.address.address);
            $("#carrier_realname").show().find("input").val(modal.address.realname);
            $("#carrier_mobile").show().find("input").val(modal.address.mobile);
            delete window.selectedAddressData
        }
        $('.fui-footer .btn-1').click(function () {
            if (modal.goods.dispatch > 0 && modal.addressid < 1) {
                FoxUI.toast.show("请选择收货地址!");
                return
            }
            modal.openActionSheet(false)
        });
        $('.order-verify').unbind('click').click(function () {
            var orderid = $(this).data('orderid');
            modal.verify(orderid)
        });
        $('.order-finish').unbind('click').click(function () {
            var logid = $(this).data('logid');
            FoxUI.confirm('确认已收到货了吗?', '提示', function () {
                modal.finish(logid)
            })
        });
        $('.look-diyinfo').click(function () {
            var data = $(this).attr('data');
            var id = "diyinfo_" + data;
            var hide = $(this).attr('hide');
            if (hide == '1') {
                $("." + id).slideDown()
            } else {
                $("." + id).slideUp()
            }
            $(this).attr('hide', hide == '1' ? '0' : '1')
        });
        if ($('#nearStore').length > 0) {
            var arr = [];
            var geolocation = new BMap.Geolocation();
            geolocation.getCurrentPosition(function (r) {
                var _this = this;
                if (this.getStatus() == BMAP_STATUS_SUCCESS) {
                    var lat = r.point.lat, lng = r.point.lng;
                    $('.store-item').each(function () {
                        var location = $(this).find('.location');
                        var store_lng = $(this).data('lng'), store_lat = $(this).data('lat');
                        if (store_lng > 0 && store_lat > 0) {
                            var distance = core.getDistanceByLnglat(lng, lat, store_lng, store_lat);
                            $(this).data('distance', distance);
                            location.html('距离您: ' + distance.toFixed(2) + "km").show()
                        } else {
                            $(this).data('distance', 999999999999999999);
                            location.html('无法获得距离').show()
                        }
                        arr.push($(this))
                    });
                    arr.sort(function (a, b) {
                        return a.data('distance') - b.data('distance')
                    });
                    $.each(arr, function () {
                        $('.store-container').append(this)
                    });
                    $('#nearStore').show();
                    $('#nearStoreHtml').append($(arr[0]).html());
                    var location = $('#nearStoreHtml').find('.location').html();
                    $('#nearStoreHtml').find('.location').html(location + "<span class='fui-label fui-label-danger'>最近</span> ");
                    $(arr[0]).remove()
                }
            }, {enableHighAccuracy: true})
        }
    };
    modal.finish = function (id) {
        core.json('creditshop/log/finish', {id: id}, function (pay_json) {
            if (pay_json.status == 1) {
                location.reload();
                return
            }
            FoxUI.toast.show(pay_json.result)
        }, true, true)
    };
    modal.openActionSheet = function (round) {
        FoxUI.actionsheet.show("选择支付方式", [{
            text: '微信支付', extraClass: 'wechat', onclick: function () {
                modal.payDispatch('wechat')
            }
        }, {
            text: '支付宝支付', extraClass: 'alipay', onclick: function () {
                modal.payDispatch('alipay')
            }
        },], round)
    };
    modal.verify = function (orderid) {
        container = new FoxUIModal({
            content: $(".order-verify-hidden").html(),
            extraClass: "popup-modal",
            maskClick: function () {
                container.close()
            }
        });
        container.show();
        $('.verify-pop').find('.close').unbind('click').click(function () {
            container.close()
        });
        core.json('groups/verify/qrcode', {id: orderid}, function (ret) {
            if (ret.status == 0) {
                FoxUI.alert('生成出错，请刷新重试!');
                return
            }
            var time = +new Date();
            $('.verify-pop').find('.qrimg').attr('src', ret.result.url + "?timestamp=" + time).show()
        }, false, true)
    };
    modal.payDispatch = function (paytype) {
        if (modal.goods.isverify == 0 && modal.goods.dispatch > 0) {
            var tiptext = '确认兑换并支付运费吗？';
            modal.needdispatchpay = 1
        } else {
            var tiptext = '确认兑换吗?';
            modal.needdispatchpay = 0
        }
        FoxUI.message.show({
            icon: 'icon icon-information',
            content: tiptext,
            buttons: [{
                text: '确定', extraClass: 'btn-danger', onclick: function () {
                    setTimeout(function () {
                        core.json('creditshop/log/paydispatch', {
                            id: modal.log.id,
                            addressid: modal.addressid,
                            paytype: paytype
                        }, function (json) {
                            var result = json.result;
                            if (modal.needdispatchpay) {
                                if (result.wechat) {
                                    var wechat = result.wechat;
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
                                                modal.payResult()
                                            } else if (res.err_msg == 'get_brand_wcpay_request:cancel') {
                                                FoxUI.toast.show('取消支付')
                                            } else {
                                                core.json('creditshop/log/paydispatch', {
                                                    id: modal.log.id,
                                                    addressid: modal.addressid,
                                                    jie: 1
                                                }, function (wechat_jie) {
                                                    modal.payWechatJie(wechat_jie.result.wechat)
                                                }, false, true)
                                            }
                                        })
                                    } else if (result.wechat.weixin_jie || result.wechat.jie == 1) {
                                        modal.payWechatJie(wechat)
                                    }
                                } else if (result.alipay) {
                                    var alipay = result.alipay;
                                    if (!alipay.success) {
                                        FoxUI.toast.show('支付参数错误！')
                                    }
                                    location.href = core.getUrl('order/pay_alipay', {
                                        id: modal.log.id,
                                        type: 21,
                                        url: alipay.url
                                    })
                                }
                            } else {
                                modal.payResult()
                            }
                        }, true, true)
                    }, 1000)
                }
            }, {
                text: '取消', extraClass: 'btn-default', onclick: function () {
                }
            }]
        })
    };
    modal.payWechatJie = function (wechat) {
        var img = core.getUrl('index/qr',{url:wechat.code_url});
        $('#qrmoney').text(modal.goods.dispatch);
        $('.fui-header').hide();
        $('#btnWeixinJieCancel').unbind('click').click(function () {
            clearInterval(modal.settime);
            $('.order-weixinpay-hidden').hide();
            $('.fui-header').show()
        });
        $('.order-weixinpay-hidden').show();
        modal.settime = setInterval(function () {
            modal.payResult()
        }, 2000);
        $('.verify-pop').find('.close').unbind('click').click(function () {
            $('.order-weixinpay-hidden').hide();
            $('.fui-header').show();
            clearInterval(modal.settime)
        });
        $('.verify-pop').find('.qrimg').attr('src', img).show()
    };
    modal.payResult = function () {
        var tiptext = modal.needdispatchpay ? '运费支付成功!' : '兑换成功!';
        core.json('creditshop/log/payresult', {id: modal.log.id}, function (json) {
            var result = json.result;
            if (json.status != 1) {
                if (modal.settime == 0) {
                    FoxUI.toast.show(result.message)
                }
                return
            }
            clearInterval(modal.settime);
            FoxUI.message.show({
                icon: 'icon icon-success',
                content: tiptext,
                buttons: [{
                    text: '确定', extraClass: 'btn-danger', onclick: function () {
                        location.reload()
                    }
                }]
            })
        }, false, true)
    };
    return modal
});