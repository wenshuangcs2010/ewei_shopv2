define(['core', 'tpl', 'biz/goods/picker'], function (core, tpl, picker) {
    var modal = {
        page: 1,
        merchid:0
    };
    modal.init = function (args) {
        modal.merchid = args.merchid || 0;
        // 更新底部菜单 购物车数量
        if (typeof(window.cartcount) !== 'undefined') {
            picker.changeCartcount(window.cartcount);
        }

        if (!modal.toGoods) {
            modal.page = 1
        } else {
            modal.toGoods = false
        }

        modal.getList();

        modal.bindEvents();

        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList();
            }
        });

    };

    modal.bindEvents = function () {

        $("a").click(function () {
            modal.toGoods = true
        });

        $('.buy').unbind('click').click(function () {
            var goodsid = $(this).closest('.fui-goods-item').data('goodsid');
            var type = $(this).closest('.fui-goods-item').data('type');
            if (type == 20){
                location.href = core.getUrl('goods/detail',{id:goodsid});
            } else {
                picker.open({
                    goodsid: goodsid,
                    total: 1
                });
            }
        });
    };
    modal.getUrl = function () {
        var url = '';
        if (modal.merchid==0){
            url = 'get_recommand';
        }else{
            url = 'merch/get_recommand';
        }
        return url;
    };
    var isloading = false;

    modal.getList = function () {

        if (isloading) {
            return;
        }
        isloading = true;
        var url = modal.getUrl();
        var param = {
            page: modal.page,
            merchid:modal.merchid
        };
        core.json(url, param, function (ret) {
            var result = ret.result;

            if (result.total <= 0) {
                $('.fui-content').infinite('stop');
                $('.fui-content').lazyload();
            } else {
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop');
                }
                if (result.page == 1) {
                    var c = "#recommand";
                } else {
                    var c = "#recommand .fui-goods-group";
                }
                modal.page++;
                core.tpl(c, 'tpl_recommand', result, modal.page > 1);
                modal.bindEvents();
                isloading = false;
            }
        });
    };
    return modal;
});