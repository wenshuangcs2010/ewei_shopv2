define(['core', 'tpl'], function (core, tpl) {
    var modal = {params: {}};
    modal.init = function (id) {
        modal.params.id = id;
        $("a.btn-single").bind("click", function () {
            core.json('groups/goods/goodsCheck', {'id': modal.params.id, type: 'single'}, function (postjson) {
                if (postjson.status == 1) {
                    location.href = core.getUrl('groups/orders/confirm', {id: modal.params.id, type: 'single'});
                    return
                } else {
                    FoxUI.toast.show(postjson.result.message)
                }
            }, true, true)
        });
        $("a.btn-groups").bind("click", function () {
            core.json('groups/goods/goodsCheck', {'id': modal.params.id, type: 'groups'}, function (postjson) {
                if (postjson.status == 1) {
                    location.href = core.getUrl('groups/orders/confirm', {
                        id: modal.params.id,
                        type: 'groups',
                        heads: 1
                    });
                    return
                } else {
                    FoxUI.toast.show(postjson.result.message)
                }
            }, true, true)
        })
    };
    return modal
});