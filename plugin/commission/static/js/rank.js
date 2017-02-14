define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1, pagesize: 20};
    modal.init = function () {
        modal.page = 1;
        modal.count = 1;
        modal.getList();
        $("#btn-more").unbind('click');
    };
    modal.getList = function () {
        core.json('commission/rank/ajaxpage', {page: modal.page}, function (ret) {
            ret.result.page = modal.count;
            var html = tpl('tpl_list', ret.result);
            $('#container').append(html);
            if (ret.result.len < 1) {
                $("#btn-more").removeClass("btn-danger").addClass("btn-default block disabled").unbind('click').text("已经加载完");
                return
            }
            $('#btn-more').removeClass("btn-default disabled").addClass("btn-danger").click(function () {
                $(this).unbind('click').removeClass("btn-danger").addClass("btn-default disabled");
                modal.count = modal.pagesize * modal.page + 1;
                modal.page++;
                modal.getList();
            })
        }, true)
    };
    return modal
});