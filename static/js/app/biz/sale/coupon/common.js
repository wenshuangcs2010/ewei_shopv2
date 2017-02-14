define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, cateid: ''};
    modal.init = function (params) {
        $(document).off('click','.fui-tab-scroll .item').on('click', ".fui-tab-scroll .item", function () {
            var left = 0;
            var tab = $(this).closest(".fui-tab-scroll");
            var container = tab.find(".container");
            var cateid = $(this).data('cateid');
            modal.page = 1;
            modal.cateid = cateid;
            $(this).addClass('on').siblings().removeClass('on');
            if (container.length > 0) {
                left = container.scrollLeft()
            }
            tab.html(tab.html());
            tab.find(".container").scrollLeft(left);
            $('.content-empty').hide(), $('.infinite-loading').show();
            $('#container').html('');
            modal.getList(modal.cateid)
        });

        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList(modal.cateid)
            }
        });
        if (modal.page == 1) {
            if ($(".coupon-list").length <= 0) {
                modal.getList(modal.cateid)
            } else {
                modal.page++
            }
        }
        modal.bindEvents()
    };
    modal.bindEvents = function () {
        require(['biz/sale/coupon/circle-progress'], function () {
            $('.coupon-index-list .coupon-list-allow').each(function () {
                var value = 1;
                var item = $(this), id = item.data('id'), t = item.data('t'), last = item.data('last');
                if (t == -1) {
                    value = 1
                } else {
                    value = last / t
                }
                $('.forth' + id + '.circle').circleProgress({
                    startAngle: -Math.PI / 4 * 6,
                    value: value,
                    size: 500,
                    emptyFill: 'rgba(0, 0, 0, 0.1)',
                    lineCap: 'round',
                    fill: {color: '#fff'}
                })
            })
        })
    };
    modal.getList = function (cateid) {
        core.json('sale/coupon/getlist', {page: modal.page, cateid: cateid}, function (ret) {
            $('.infinite-loading').hide();
            var result = ret.result;
            if (result.total <= 0) {
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            FoxUI.loader.hide();
            core.tpl('#container', 'tpl_list_coupon', result, modal.page > 1);
            modal.bindEvents()
        })
    };
    return modal
});