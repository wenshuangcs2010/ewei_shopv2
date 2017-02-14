define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
    modal.init = function (params) {

        modal.initLogout();
    };
    
    modal.initLogout = function () {
        $(".btn-logout").unbind('click').click(function () {
            FoxUI.confirm('当前已登录，确定要退出？',function () {
                location.href = core.getUrl('account/logout');
            });
        });
    };
    
    return modal
});