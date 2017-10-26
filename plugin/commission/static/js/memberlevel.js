define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, status: '',uid:''};
    modal.init = function (params) {
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList()
            }
        });
        modal.uid=params.uid;
        modal.type = params.type;
        modal.starttime = params.starttime;
        modal.endtime = params.endtime;
        modal.agentid=params.agentid;
        console.log(modal.uid);
        if (modal.page == 1) {
            modal.getList()
        }
        $("#select").unbind('change').change(function () {
            var $type = $("#type1");
            $type.hide();
            if (this.value == '1'){
                $type.show();
            }
        });
        FoxUI.tab({
            container: $('#tab'), handlers: {
                status: function () {
                    modal.changeTab('')
                }, status0: function () {
                    modal.changeTab(0)
                }, status1: function () {
                    modal.changeTab(1)
                }, status3: function () {
                    modal.changeTab(3)
                }
            }
        })
    };
    modal.changeTab = function (status) {
        $('.fui-content').infinite('init');
        $('.content-empty').hide(), $('.infinite-loading').show(), $('#container').html('');
        modal.page = 1, modal.status = status, modal.getList()
    };
    modal.loading = function () {
        modal.page++
    };
    modal.getList = function () {
        core.json('commission/memberorder/get_memberorderlist', {agentid:modal.agentid,page: modal.page,uid:modal.uid,type: modal.type, status: 3,starttime:modal.starttime,endtime:modal.endtime}, function (ret) {
            var result = ret.result;
            $('#total').html(result.total);
            $('#money').html(result.money);
            if (result.list.length <= 0) {
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
            core.tpl('#container', 'tpl_commission_order_list', result, modal.page > 1);
            FoxUI.according.init()
        })
    };
    return modal
});