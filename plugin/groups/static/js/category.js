define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, category: 0, keyword: ''};
    modal.init = function (params) {
        modal.category = params.category;
        modal.keyword = params.keyword;
        modal.getList();
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList()
            }
        });
        $("#search").click(function () {
            var kw = $.trim($("#keyword").val());
            var url = core.getUrl('groups/category');
            location.href = url + "&keyword=" + kw + "&category=" + modal.category
        });
        $("#keyword").keypress(function (event) {
            if (event.keyCode == 13) {
                var kw = $.trim($("#keyword").val());
                var url = core.getUrl('groups/category');
                location.href = url + "&keyword=" + kw + "&category=" + modal.category
            }
        })
    };
    modal.getList = function () {
        core.json('groups/category/get_list', {
            page: modal.page,
            category: modal.category,
            keyword: modal.keyword
        }, function (ret) {
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
                } else {
                    modal.page++
                }
            }
            $('.content-loading').hide();
            core.tpl('#container', 'tpl_list', result, modal.page > 1);
            FoxUI.according.init()
        })
    };
    return modal
});