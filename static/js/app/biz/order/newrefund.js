define(['core', 'tpl'], function (core, tpl) {
    var modal = {params: {}};
    modal.init = function (params) {
        modal.params.orderid = params.orderid;
        modal.params.refundid = params.refundid;
        modal.params.ogid=params.ogid;
        modal.params.total=params.total;
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
       $(".plus").click(function(event) {
            var num=parseInt($(".shownum").val());
            if(num>=modal.params.total){
                FoxUI.toast.show("退货数量不能大于"+modal.params.total);
                return ;
            }
          $(".shownum").val(num+1);
       });
       $(".minus").click(function(event) {
        var num=$(".shownum").val();
        if(num<=1){
                FoxUI.toast.show('退货数量不能为0 ');
                return ;
        }
        $(".shownum").val(num-1);
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
            core.json('order/newrefund/goodsrefundSubmit', {
                'refundid': modal.params.refundid,
                'orderid': modal.params.orderid,
                'rtype': $('#rtype').val(),
                'reason': $('#reason').val(),
                'content': $('#content').val(),
                'number':$(".shownum").val(),
                'ogid':modal.params.ogid,
                'images': images,
                'price': $('#price').val()
            }, function (ret) {
                if (ret.status == 1) {
                    location.href = core.getUrl('order/detail', {id: modal.params.orderid});
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
            FoxUI.confirm('确定您要取消申请?', '', function () {
                $(this).attr('stop', 1).attr('buttontext', $(this).html()).html('正在处理..');
                core.json('order/newrefund/cancel', {'id': modal.params.orderid}, function (postjson) {
                    if (postjson.status == 1) {
                        //location.href = core.getUrl('order/detail', {id: modal.params.orderid});
                       // return
                    } else {
                        FoxUI.toast.show(postjson.result.message)
                    }
                    $('.btn-cancel').removeAttr('stop').html($('.btn-cancel').attr('buttontext')).removeAttr('buttontext')
                }, true, true)
            })
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
            core.json('order/newrefund/express', {
                id: modal.params.orderid,
                refundid: modal.params.refundid,
                express: $('#express').val(),
                expresscom: $('#expresscom').val(),
                expresssn: $('#expresssn').val()
            }, function (postjson) {
                if (postjson.status == 1) {
                    location.href = core.getUrl('order/detail', {id: modal.params.orderid})
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
                core.json('order/refund/receive', {
                    refundid: modal.params.refundid,
                    id: modal.params.orderid
                }, function (postjson) {
                    if (postjson.status == 1) {
                        location.href = core.getUrl('order/detail', {id: modal.params.orderid})
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