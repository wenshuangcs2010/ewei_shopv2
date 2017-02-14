define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1};

    modal.init = function (params) {
        modal.cate = params.cate;
        modal.keyword = params.keyword;

        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList()
            }
        });
        if (modal.page == 1) {
            modal.getList()
        }
    };
    modal.getList = function () {
        core.json('qa/getlist', {
            page: modal.page,
            cate: modal.cate,
            keyword: modal.keyword
        }, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.question-title').hide();
                $("#container").hide();
                $('.fui-content').infinite('stop')
                $(".empty").show();
            } else {
                $('.question-title').show();
                $("#container").show();
                $(".empty").hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('#container', 'tpl_list', result, modal.page > 1);
            FoxUI.according.init()
        })
    };
    return modal
});