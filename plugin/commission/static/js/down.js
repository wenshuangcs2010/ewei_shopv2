define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, level: ''};
    modal.init = function () {
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList()
            }
        });
        if (modal.page == 1) {
            modal.getList()
        }
        FoxUI.tab({
            container: $('#tab'), handlers: {
                level1: function () {
                    modal.changeTab(1)
                }, level2: function () {
                    modal.changeTab(2)
                }, level3: function () {
                    modal.changeTab(3)
                }
            }
        })
    };
    modal.changeTab = function (level) {
        $('.fui-content').infinite('init');
        $('.content-empty').hide(), $('.infinite-loading').show(), $('#container').html('');
        modal.page = 1, modal.level = level, modal.getList()
    };
    modal.getList = function () {
        core.json('commission/down/get_list', {page: modal.page, level: modal.level}, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('#container').hide();
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('#container').show();
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#container', 'tpl_commission_down_list', result, modal.page > 1)
        })
    };
    return modal
});