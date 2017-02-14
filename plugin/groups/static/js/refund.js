define(['core', 'tpl'], function (core, tpl) {
    var modal = {params: {}};
    modal.init = function (params) {
        modal.params.orderid = params.orderid;
        modal.params.teamid = params.teamid;
        modal.params.refundid = params.refundid;
        $('.refund-container-uploader').uploader({
            uploadUrl: core.getUrl('util/uploader'),
            removeUrl: core.getUrl('util/uploader/remove')
        });
        $('#rtype').change(function () {
            var rtype = $(this).find("option:selected").val();
            if (rtype == 2) {
                $('.r-group').hide();
                $('.re-g').html('换货')
            } else {
                $('.r-group').show();
                $('.re-g').html('退款')
            }
        });
        $('.btn-submit').click(function () {
            if ($(this).attr('stop')) {
                return
            }
            if (!$('#price').isNumber()) {
                FoxUI.toast.show('请输入数字金额!');
                return
            }
            var images = [];
            $('#images').find('li').each(function () {
                images.push($(this).data('filename'))
            });
            $(this).attr('stop', 1).html('正在处理...');
            core.json('groups/refund/submit', {
                'orderid': modal.params.orderid,
                'teamid': modal.params.teamid,
                'rtype': $('#rtype').val(),
                'reason': $('#reason').val(),
                'content': $('#content').val(),
                'images': images,
                'price': $('#price').val()
            }, function (ret) {
                if (ret.status == 1) {
                    location.href = core.getUrl('groups/orders/detail', {
                        orderid: modal.params.orderid,
                        teamid: modal.params.teamid
                    });
                    return
                }
                $('.btn-submit').removeAttr('stop').html('确定');
                FoxUI.toast.show(ret.result.message)
            }, true, true)
        });
        $('.btn-cancel').click(function () {
            if ($(this).attr('stop')) {
                return
            }
            $(this).attr('stop', 1).attr('buttontext', $(this).html()).html('正在处理..');
            core.json('groups/refund/cancel', {
                orderid: modal.params.orderid,
                teamid: modal.params.teamid
            }, function (ret) {
                if (ret.status == 1) {
                    location.href = core.getUrl('groups/orders/detail', {
                        orderid: modal.params.orderid,
                        teamid: modal.params.teamid
                    });
                    return
                }
                $('.btn-cancel').removeAttr('stop').html($('.btn-cancel').attr('buttontext')).removeAttr('buttontext')
            }, true, true)
        });
        $("select[name=express]").val($('#express_old').val()).change(function () {
            var obj = $(this);
            var sel = obj.find("option:selected");
            var name = sel.data("name");
            $(":input[name=expresscom]").val(name)
        });
        $('#express_submit').click(function () {
            if ($(this).attr('stop')) {
                return
            }
            if ($('#expresssn').isEmpty()) {
                FoxUI.toast.show('请填写快递单号');
                return
            }
            $(this).html('正在处理...').attr('stop', 1);
            core.json('groups/refund/express', {
                orderid: modal.params.orderid,
                teamid: modal.params.teamid,
                refundid: modal.params.refundid,
                express: $('#express').val(),
                expresscom: $('#expresscom').val(),
                expresssn: $('#expresssn').val()
            }, function (postjson) {
                if (postjson.status == 1) {
                    location.href = core.getUrl('groups/orders/detail', {
                        orderid: modal.params.orderid,
                        teamid: modal.params.teamid
                    })
                } else {
                    $('#express_submit').html('确认').removeAttr('stop');
                    FoxUI.toast.show(postjson.result.message)
                }
            }, true, true)
        });
        $('.btn-receive').click(function () {
            if ($(this).attr('stop')) {
                return
            }
            FoxUI.confirm('确认您已经收到换货物品?', '', function () {
                $(this).attr('stop', 1).html('正在处理...');
                core.json('groups/refund/receive', {
                    refundid: modal.params.refundid,
                    orderid: modal.params.orderid,
                    teamid: modal.params.teamid
                }, function (postjson) {
                    if (postjson.status == 1) {
                        location.href = core.getUrl('groups/orders/detail', {
                            orderid: modal.params.orderid,
                            teamid: modal.params.teamid
                        })
                    } else {
                        $('.btn-receive').html('确认收到换货物品').removeAttr('stop');
                        FoxUI.toast.show(postjson.result.message)
                    }
                }, true, true)
            })
        })
    };
    return modal
});