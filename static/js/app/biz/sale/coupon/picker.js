define(['core', 'tpl'], function (core, tpl) {
    var modal = {coupons: [], couponid: 0, couponname: '', merchid: 0};
    var CouponPicker = function (params) {
        var self = this;
        self.params = $.extend({}, params || {});
        self.data = {coupons: self.params.coupons};
        self.show = function () {
            if (self.data.coupons !== modal.coupons) {
                modal.pickerHTML = tpl('tpl_coupons', self.data)
            }
            modal.coupons = self.data.coupons;
            modal.picker = new FoxUIModal({
                content: modal.pickerHTML,
                extraClass: 'picker-modal',
                maskClick: function () {
                    modal.picker.close()
                }
            });
            modal.picker.show();
            $('.coupon-picker').find('.coupon-item2.selected').removeClass('selected');
            $('.coupon-picker').find(".coupon-item2[data-couponid='" + self.params.couponid + "']").addClass('selected');
            $('.coupon-picker').find('.coupon-item2').click(function () {
                $('.coupon-picker').find('.coupon-item2.selected').removeClass('selected');
                $(this).addClass('selected');
                modal.couponid = $(this).data('couponid');
                modal.merchid = $(this).data('merchid');
                modal.couponname = $(this).find('.name').html()
            });
            $('.coupon-picker').find('.btn-cancel').click(function () {
                modal.couponid = 0;
                modal.merchid = 0;
                modal.couponname = '';
                modal.picker.close();
                if (self.params.onCancel) {
                    self.params.onCancel()
                }
            });
            $('.coupon-picker').find('.btn-confirm').click(function () {
                var item = $('.coupon-picker').find('.coupon-item2.selected');
                if (item.length <= 0) {
                    FoxUI.toast.show('未选择优惠券');
                    return
                }
                var data = {
                    id: item.data('couponid'),
                    merchid: item.data('merchid'),
                    couponname: item.data('couponname'),
                    deduct: item.data('deduct'),
                    discount: item.data('discount'),
                    backtype: item.data('backtype')
                };
                if (self.params.onSelected) {
                    self.params.onSelected(data)
                }
                modal.picker.close()
            })
        }
    };
    modal.show = function (params) {
        var picker = new CouponPicker(params);
        picker.show()
    };
    return modal
});