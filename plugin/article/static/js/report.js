define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
    modal.init = function (params) {
        modal.aid = params.aid;
        $("#report_content").bind("input propertychange", function () {
            var _val = $(this).val();
            var len = _val.length;
            var span = $(".textarea_counter span");
            span.text(len);
            if (len > 200) {
                span.addClass("text-danger")
            } else {
                span.removeClass("text-danger")
            }
        });
        $("#sub").click(function () {
            var _this = $(this);
            if (_this.data('state')) {
                FoxUI.toast.show("正在提交中...!");
                return
            }
            var report_cate = $.trim($("#report_cate option:selected").val());
            var report_content = $.trim($("#report_content").val());
            if (report_cate == '') {
                FoxUI.toast.show("请选择投诉原因!");
                return
            }
            if (report_content == '') {
                FoxUI.toast.show("请填写投诉内容!");
                return
            }
            if (report_content.length < 20) {
                FoxUI.toast.show("投诉内容小于20个字符!");
                return
            }
            if (report_content.length > 200) {
                FoxUI.toast.show("投诉内容超出200个字符!");
                return
            }
            $("#sub").text("正在提交中...").data('state', 1);
            core.json('article/report/post', {
                aid: modal.aid,
                cate: report_cate,
                content: report_content
            }, function (json) {
                if (json.status) {
                    FoxUI.message.show({
                        title: "提交成功!",
                        icon: 'icon icon-success',
                        content: "我们确认后会第一时间进行处理",
                        buttons: [{
                            text: '确定', extraClass: 'btn-success', onclick: function () {
                                WeixinJSBridge.call('closeWindow')
                            }
                        }]
                    })
                } else {
                    FoxUI.message.show({
                        title: "提交失败!",
                        icon: 'icon icon-wrong',
                        content: "提交失败，请重试",
                        buttons: [{
                            text: '确定', extraClass: 'btn-default', onclick: function () {
                                location.reload()
                            }
                        }]
                    })
                }
            })
        })
    };
    return modal
});