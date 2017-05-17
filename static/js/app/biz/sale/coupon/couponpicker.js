define(['core', 'tpl'], function (core, tpl) {
    var modal = {coupons: [], merchid: 0};
    var CouponPicker = function (params) {
        var self = this;
        self.params = $.extend({}, params || {});
        self.data = {coupons: self.params.coupons};
        self.show = function () {
            if (self.data.coupons !== modal.coupons) {
                modal.pickerHTML = tpl('tpl_getcoupons', self.data)
            }
            modal.coupons = self.data.coupons;
            modal.picker = new FoxUIModal({
                content: modal.pickerHTML,
                extraClass: 'picker-modal',
                maskClick: function () {
                    modal.picker.close();
                }
            });
            modal.picker.show();

            $('.coupon-picker').find('.ling').click(function () {
                var couponid = $(this).data('couponid');
                var credit = $(this).data('credit');

                var btn = $(this);
                if (btn.attr('submitting') == '1') {
                    return
                }

                btn.attr('oldhtml', btn.html()).html('操作中..').attr('submitting', 1);
                core.json('goods/detail/pay', {id: couponid}, function (ret) {
                    if (ret.status <= 0) {
                        btn.html(btn.attr('oldhtml')).removeAttr('oldhtml').removeAttr('submitting');
                        $(".fui-message-popup .btn-danger").text('确定');
                        modal.paying = false;
                        modal.picker.close();
                        FoxUI.toast.show(ret.result.message);
                    }else
                    {
                        FoxUI.toast.show('领取成功');
                        btn.html(btn.attr('oldhtml')).removeAttr('oldhtml').removeAttr('submitting');
                        modal.picker.close();
                    }

                }, true, true);


            });
            $('.coupon-picker').find('.btn-cancel').click(function () {
                modal.picker.close();
            });
        }
    };
    modal.show = function (params) {
        var picker = new CouponPicker(params);
        picker.show()
    };
    return modal
});