define(['core', 'tpl'], function (core, tpl) {
    var modal = {params: {orderid: 0, goods: [], iscarry: 0, isverify: 0, isvirtual: 0, addressid: 0, couponid: 0}};
    modal.init = function (params) {
        modal.params = $.extend(modal.params, params || {});
        var loadAddress = false;
        if (typeof(window.selectedAddressData) !== 'undefined') {
            loadAddress = window.selectedAddressData
        } else if (typeof(window.editAddressData) !== 'undefined') {
            loadAddress = window.editAddressData;
            loadAddress.address = loadAddress.areas.replace(/ /ig, '') + ' ' + loadAddress.address
        }
        if (loadAddress) {
            modal.params.addressid = loadAddress.id;
            $('#addressInfo .has-address').show();
            $('#addressInfo .no-address').hide();
            $('#addressInfo .aid').val(loadAddress.id);
            $('#addressInfo .realname').html(loadAddress.realname);
            $('#addressInfo .mobile').html(loadAddress.mobile);
            $('#addressInfo .address').html(loadAddress.address);
            $('#addressInfo a').attr('href', core.getUrl('groups/address/selector'));
            $('#addressInfo a').click(function () {
                window.orderSelectedAddressID = loadAddress.id
            })
             var goodsid = $(":input[name=goodsid]").val();
             modal.getdispatchprice(loadAddress.id,goodsid);
        }
        var loadStore = false;
        if (typeof(window.selectedStoreData) !== 'undefined') {
            loadStore = window.selectedStoreData;
            modal.params.storeid = loadStore.id;
            $('#carrierInfo .storename').html(loadStore.storename);
            $('#carrierInfo .realname').html(loadStore.realname);
            $('#carrierInfo_mobile').html(loadStore.mobile);
            $('#carrierInfo .address').html(loadStore.address)
        }
        $('#deductcredit').click(function () {
            if (this.checked) {
                $("#isdeduct").val(1)
            } else {
                $("#isdeduct").val(0)
            }
            modal.totalPrice()
        })
    };
    modal.totalPrice = function () {
        var goodsprice = core.getNumber($('.goodsprice').html());
        var dispatchprice = core.getNumber($(".dispatchprice").html());
        var discountprice = 0;
        if ($('.discountprice').length > 0) {
            discountprice = core.getNumber($(".discountprice").html())
        }
        var totalprice = goodsprice - discountprice;
        totalprice = totalprice + dispatchprice;
        var deductprice = 0;
        if ($("#deductcredit").length > 0) {
            if ($("#deductcredit").prop('checked')) {
                deductprice = core.getNumber($("#deductcredit").data('money'))
            }
        }
        totalprice = totalprice - deductprice;
        if (totalprice <= 0) {
            totalprice = 0
        }
        $('.totalprice').html(core.number_format(totalprice));
        return totalprice
    };
    modal.getdispatchprice=function(aid,goodsid){
        $.ajaxSettings.async = false;
        core.json('groups/orders/dispatch', {'aid': aid, goodsid: goodsid}, function (ret) {
                $(".dispatchprice").html(core.number_format(ret));
            }, true, true)
    };
    return modal
});