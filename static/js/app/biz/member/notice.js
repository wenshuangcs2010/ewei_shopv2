define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
    modal.init = function () {
        $('.fui-switch').change(function () {
            core.json('member/notice', {
                type: $(this).data('type'),
                checked: $(this).prop('checked') ? 1 : 0
            }, function () {
            }, true, true)
        })
    };
    return modal
});