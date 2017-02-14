define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
    modal.init = function (params) {
        core.json("commission/qrcode", {goodsid: params.goodsid}, function (ret) {
            $("#posterimg").find('.fui-cell-group').remove();
            $("#posterimg").find('img').attr('src', ret.result.img).show()
        }, false, true)
    };
    return modal
});