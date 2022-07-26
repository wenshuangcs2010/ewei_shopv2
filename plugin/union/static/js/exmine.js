define(['core'], function (core) {
    var modal = {page: 1, params: {}};
    var defaults = {};
    modal.init=function(params){
        console.log("ss");

        FoxUI.tab({
            container: $('#tabexmine'), handlers: {
                level1: function () {
                    modal.changeTab(1)
                }, level2: function () {
                    modal.changeTab(2)
                }
            }
        });


        modal.params = $.extend(defaults, params || {});
        var leng = $.trim($('.container').html());
        if (leng == '') {
            modal.page = 1;
            modal.getexaminelist();
        }
        $('.fui-content').infinite({
            onLoading: function () {
                var tabval=$("#tabexmine").find(".active").data("tab");

                if(tabval=='level1'){
                    modal.getexaminelist();
                }
                if(tabval=='level2'){
                    modal.getexamineoldlist();
                }
            }
        });

    };
    modal.getexaminelist=function () {
        modal.params.page = modal.page;
        core.json('union/welfare/getexaminelist', modal.params, function (ret) {
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
            core.tpl('#container', 'tpl_list', result,modal.page>1);

        }, false, true)
    };
    modal.getexamineoldlist=function () {
        modal.params.page = modal.page;
        core.json('union/welfare/getexamineoldlist', modal.params, function (ret) {
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
            core.tpl('#container', 'tpl_list', result,modal.page>1);

        }, false, true)
    };
    modal.changeTab = function (level) {
        $('.fui-content').infinite('init');
        $('.content-empty').hide(), $('.infinite-loading').show(), $('#container').html('');
        modal.page = 1;

        if(level==1){
            modal.getexaminelist()
        }
        if(level==2){
            modal.getexamineoldlist()
        }

    };
    return modal;
});