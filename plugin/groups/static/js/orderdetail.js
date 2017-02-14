define(['core', 'tpl', './op.js'], function (core, tpl, op) {
    var modal = {params: {}};
    modal.init = function (params) {
        modal.params.orderid = params.orderid;
        modal.params.teamid = params.teamid;
        op.init({fromDetail: true});
        $('.btn-cancel').click(function () {
            if ($(this).attr('stop')) {
                return
            }
            FoxUI.confirm('确定您要取消申请?', '', function () {
                $(this).attr('stop', 1).attr('buttontext', $(this).html()).html('正在处理..');
                core.json('groups/refund/cancel', {'orderid': modal.params.orderid}, function (postjson) {
                    if (postjson.status == 1) {
                        location.href = core.getUrl('groups/orders/detail', {
                            orderid: modal.params.orderid,
                            teamid: modal.params.teamid
                        });
                        return
                    } else {
                        FoxUI.toast.show(postjson.result.message)
                    }
                    $('.btn-cancel').removeAttr('stop').html($('.btn-cancel').attr('buttontext')).removeAttr('buttontext')
                }, true, true)
            })
        });
        $('.order-verify').unbind('click').click(function () {
            var orderid = $(this).data('orderid');
            modal.verify(orderid)
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
    return modal
});