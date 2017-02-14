define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1};
    modal.init = function () {
        modal.page = 1;
        $('.fui-content').infinite({
            onLoading: function () {
                modal.getList()
            }
        });
        if (modal.page == 1) {
            modal.getList()
        }
        $('.btn-edit').click(function () {
            modal.changeMode()
        });
        $('.editcheckall').click(function () {
            var checked = $(this).find(':checkbox').prop('checked');
            $(".edit-item").prop('checked', checked);
            modal.caculateEdit()
        });
        $('.btn-delete').click(function () {
            if ($('.edit-item:checked').length <= 0) {
                return
            }
            modal.remove()
        })
    };
    modal.caculateEdit = function () {
        $('.editcheckall .fui-radio').prop('checked', $('.edit-item').length == $('.edit-item:checked').length);
        var selects = $('.edit-item:checked').length;
        if (selects > 0) {
            $('.btn-delete').removeClass('disabled')
        } else {
            $('.btn-delete').addClass('disabled')
        }
    };
    modal.changeMode = function () {
        if ($('.goods-item').length <= 0) {
            $('.container').remove();
            $('.btn-edit').remove();
            $('.editmode').remove();
            $('.content-empty').show();
            return
        }
        if (modal.status == 'history') {
            $('.fui-content').addClass('navbar');
            $('.edit-item').prop('checked', false);
            $('.editcheckall').prop('checked', false);
            $('.editmode').show();
            modal.status = 'edit';
            $('.btn-edit').html('完成')
        } else {
            $('.fui-content').removeClass('navbar');
            $('.editmode').hide();
            modal.status = 'history';
            $('.btn-edit').html('编辑')
        }
    };
    modal.getList = function () {
        core.json('member/history/get_list', {page: modal.page}, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.btn-edit').hide();
                $('.content-empty').show();
                $('.fui-content').infinite('stop')
            } else {
                $('.btn-edit').show();
                $('.container').show();
                $('.content-empty').hide();
                $('.fui-content').infinite('init');
                if (result.list.length <= 0 || result.list.length < result.pagesize) {
                    $('.fui-content').infinite('stop')
                }
            }
            modal.page++;
            core.tpl('.container', 'tpl_member_history_list', result, modal.page > 1);
            $('.edit-item').unbind('click').click(function () {
                modal.caculateEdit()
            })
        })
    };
    modal.remove = function (ids, callback) {
        var ids = [];
        $('.edit-item:checked').each(function () {
            var id = $(this).closest('.goods-item').data('id');
            ids.push(id)
        });
        if (ids.length <= 0) {
            return
        }
        FoxUI.confirm('确认删除这些足迹吗?', function () {
            core.json('member/history/remove', {ids: ids}, function (ret) {
                if (ret.status == 0) {
                    FoxUI.toast.show(ret.result);
                    return
                }
                $.each(ids, function () {
                    $(".goods-item[data-id='" + this + "']").prev().remove();
                    $(".goods-item[data-id='" + this + "']").remove()
                });
                modal.changeMode()
            }, true, true)
        })
    };
    return modal
});