define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, status: '',merchid:0};
    modal.init = function (params) {
        modal.status = params.status;
        modal.merchid = params.merchid;
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList()
            }
        });
        if (modal.page == 1) {
            modal.getList()
        }
        $('.icon-delete').click(function () {
            $('.fui-tab-danger a').removeClass('active');
            modal.changeTab(5)
        });
        FoxUI.tab({
            container: $('#tab'), handlers: {
                tab: function () {
                    modal.changeTab('')
                }, tab0: function () {
                    modal.changeTab(0)
                }, tab1: function () {
                    modal.changeTab(1)
                }, tab2: function () {
                    modal.changeTab(2)
                }, tab3: function () {
                    modal.changeTab(3)
                }, tab4: function () {
                    modal.changeTab(4)
                }
            }
        })
    };
    modal.changeTab = function (status) {
        if(status == 5) {
            $('.icon-delete').css('color','red');
        } else {
            $('.icon-delete').css('color','#999');
        }
        $('.fui-content').infinite('init');
        $('.content-empty').hide(), $('.container').html(''), $('.infinite-loading').show();
        modal.page = 1, modal.status = status, modal.getList()
    };
    modal.loading = function () {
        modal.page++
    };
    modal.getList = function () {
        core.json('union/lyhome/lyaddressline/get_list', {page: modal.page, status: modal.status, merchid: modal.merchid}, function (ret) {
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
            core.tpl('.container', 'tpl_order_index_list', result, modal.page > 1);

        })
    };
    return modal
});