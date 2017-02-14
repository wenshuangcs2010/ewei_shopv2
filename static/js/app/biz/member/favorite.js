define(['core', 'tpl'], function (core, tpl) {
    var modal = {page: 1,type:0};
    modal.init = function () {
        if ($('#tab a').length>0){
            $('#tab a').unbind('click').click(function (e) {
                e.preventDefault();
                var _this = this;
                $('#tab a').removeClass('active');
                $(_this).addClass('active');
                if ($(_this).data('tab')=='type0') {
                    modal.type = 0;
                }else if ($(_this).data('tab')=='type1') {
                    modal.type = 1;
                };
                $('.container').html('');
                modal.page = 1;
                modal.getList();
                $('.fui-content').infinite({
                    onLoading: function () {
                        modal.getList()
                    }
                });
                $('.btn-edit').unbind('click').click(function () {
                    modal.changeMode()
                });
                $('.editcheckall').unbind('click').click(function () {
                    var checked = $(this).find(':checkbox').prop('checked');
                    $(".edit-item").prop('checked', checked);
                    modal.caculateEdit()
                });
                $('.btn-delete').unbind('click').click(function () {
                    if ($('.edit-item:checked').length <= 0) {
                        return
                    }
                    modal.remove()
                });
            });
            $('#tab a').first().click();
        }else {
            if (modal.page == 1) {
                modal.getList()
            };
            $('.fui-content').infinite({
                onLoading: function () {
                    modal.getList()
                }
            });
            $('.btn-edit').unbind('click').click(function () {
                modal.changeMode()
            });
            $('.editcheckall').unbind('click').click(function () {
                var checked = $(this).find(':checkbox').prop('checked');
                $(".edit-item").prop('checked', checked);
                modal.caculateEdit()
            });
            $('.btn-delete').unbind('click').click(function () {
                if ($('.edit-item:checked').length <= 0) {
                    return
                }
                modal.remove()
            });
        }
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
            $('.container').hide();
            $('.btn-edit').html('');
            $('.editmode').html('');
            $('.content-empty').show();
            return
        }
        if (modal.status == 'favorite') {
            $('.fui-content').addClass('navbar');
            $('.edit-item').prop('checked', false);
            $('.editcheckall').prop('checked', false);
            $('.editmode').show();
            modal.status = 'edit';
            $('.btn-edit').html('完成')
        } else {
            $('.fui-content').removeClass('navbar');
            $('.editmode').hide();
            modal.status = 'favorite';
            $('.btn-edit').html('编辑')
        }
    };

    modal.getUrl = function () {
        var url = '';
        if (modal.type==0){
            url = 'member/favorite/get_list';
        }else if(modal.type==1){
            url = 'merch/member/favorite/get_list';
        }
        return url;
    };

    modal.getToggleUrl = function () {
        var url = '';
        if (modal.type==0){
            url = 'member/favorite/toggle';
        }else if(modal.type==1){
            url = 'merch/member/favorite/toggle';
        }
        return url;
    };

    modal.getTpl = function () {
        var url = '';
        if (modal.type==0){
            url = 'tpl_member_favorite_list';
        }else if(modal.type==1){
            url = 'tpl_merch_member_favorite_list';
        }
        return url;
    };

    modal.getList = function () {
        var url = modal.getUrl();
        core.json(url, {page: modal.page}, function (ret) {
            var result = ret.result;
            if (result.total <= 0) {
                $('.btn-edit').hide();
                $('.container').hide();
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
            var tpl = modal.getTpl();
            core.tpl('.container', tpl, result, modal.page > 1);
            $('.edit-item').unbind('click').click(function () {
                modal.caculateEdit()
            })
        })
    };
    modal.toggle = function (goodsid, isfavorite, callback) {
        var url = modal.getToggleUrl();
        core.json(url, {id: goodsid, isfavorite: isfavorite}, function (ret) {
            if (ret.status == 0) {
                FoxUI.toast.show(ret.result);
                return
            }
            if (callback) {
                callback(ret.result.isfavorite)
            }
        }, false, true)
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
        FoxUI.confirm('确认取消关注这些商品吗?', function () {
            core.json('member/favorite/remove', {ids: ids}, function (ret) {
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