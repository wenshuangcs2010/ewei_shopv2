    define(['core', 'tpl'], function (core, tpl, op) {

        var modal = {
            params: {}
        };
        modal.init = function () {
            var verify_container = $('.verify-container');
            var verifytype = verify_container.data('verifytype'), orderid = verify_container.data('orderid');
            
            if( verifytype==2){
                if ($('.verify-checkbox:checked').length > 0) {
                    $('.order-verify').find('span').html('确认使用(' + $('.verify-checkbox:checked').length + ")");
                } else {
                    $('.order-verify').find('span').html('全部使用');
                }
            }
            verify_container.find('.verify-cell').each(function () {
                var cell = $(this);
                var verifycode = cell.data('verifycode');
                cell.find('.verify-checkbox').unbind('click').click(function () {
                     core.json('groups/verify/select', {id: orderid, verifycode: verifycode}, function (ret) {
                        setTimeout(function () {
                            if ($('.verify-checkbox:checked').length <= 0) {
                                $('.order-verify').find('span').html('全部使用');
                            } else {
                                $('.order-verify').find('span').html('确认使用(' + $('.verify-checkbox:checked').length + ")");
                            }
                        }, 0)
                    }, true, true);
                });
            }); 
            
            $(".fui-number").numbers({
                minToast: "最少核销{min}次",
                maxToast: "最多核销{max}次"
            });
            
            $('.order-verify').click(function () {
                  modal.verify($(this));
            })
        };
        modal.verify = function(btn){
            
            var tip = "", type =btn.data('verifytype'),orderid= btn.data('orderid') ;
            var times = parseInt( $('.shownum').val() );
         
            if(type==0){
                  tip = "确认核销吗?"
            } else if( type==1 ) {
                if( times<=0){
                    FoxUI.toast.show('最少核销一次');
                    return;
                }
                tip = "确认核销 <span class='text-danger'>" + times +"</span> 次吗?";
                
            } else if(type==2){
                  if ($('.verify-checkbox:checked').length <= 0) {
                                   tip = "确认核销所有消费码吗?";
                            } else {
                                   tip = "确认核销选择的消费码吗?";
                            }
                
            }
            FoxUI.confirm( tip ,function(){
                   core.json('groups/verify/complete',{id:orderid,times:times},function(ret){
                  if(ret.status==0){
                      FoxUI.toast.show( ret.result.message );
                      return;
                  }
                  location.href = core.getUrl('groups/verify/success',{id:orderid,times:times});
              });
            })
        };
        return modal;
    });