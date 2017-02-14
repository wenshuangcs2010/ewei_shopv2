define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, pagesize: 20, count: 1};
    modal.init = function () {
        modal.page = 1;
        modal.getList();
        $("#btn-more").unbind('click');
    };
    modal.getList = function () {
        core.json('member/rank/ajaxpage', {page: modal.page}, function (ret) {
            ret.result.page = modal.count;
            core.tpl('#container', 'tpl_member_rank_list', ret.result, modal.page > 1);
            if (ret.result.stop || ret.result.list.length < modal.pagesize) {
                $("#btn-more").removeClass("btn-danger").addClass("btn-default disabled").unbind('click').text("已经加载完");
                return;
            }
            $('#btn-more').removeClass("btn-default disabled").addClass("btn-danger").click(function () {
                $(this).unbind('click').removeClass("btn-danger").addClass("btn-default disabled");
                modal.count = modal.pagesize * modal.page + 1;
                modal.page++;
                modal.getList();
            })
        }, true)
    };
    modal.initOrder = function () {
        modal.getOrderList();
        $("#btn-more").unbind('click');
    };
    modal.getOrderList = function () {
        core.json('member/rank/ajaxorderpage', {page: modal.page}, function (ret) {
            ret.result.page = modal.count;
            core.tpl('#container', 'tpl_member_order_rank_list', ret.result, modal.page > 1);
            if (ret.result.stop || ret.result.list.length < modal.pagesize) {
                $("#btn-more").removeClass("btn-danger").addClass("btn-default disabled").unbind('click').text("已经加载完");
                return;
            }
            $('#btn-more').removeClass("btn-default disabled").addClass("btn-danger").click(function () {
                $(this).unbind('click').removeClass("btn-danger").addClass("btn-default disabled");
                modal.count = modal.pagesize * modal.page + 1;
                modal.page++;
                modal.getOrderList()
            })
        }, true)
    };
    return modal
});