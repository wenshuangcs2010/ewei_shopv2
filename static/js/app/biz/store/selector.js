    define(['core', 'tpl', 'http://api.map.baidu.com/getscript?v=2.0&ak=ZQiFErjQB7inrGpx27M1GR5w3TxZ64k7'], function (core, tpl) {
        var modal = {};
        modal.init = function () {
              modal.bindEvents();
              
              if (typeof (window.selectedStoreData) !== 'undefined') {
                      $(".store-item .fui-list-media i").removeClass('selected');
                     $(".store-item[data-storeid='" + window.selectedStoreData.id + "'] .fui-list-media i").addClass('selected');
                  
              }
  
            $('.fui-searchbar input').bind('keyup', function () {
                var val = $.trim($(this).val());
                if (val == '') {
                    $('.store-item').show();
                } else {
                    var empty = true;
                    $('.store-item').each(function () {
                        if ($(this).html().indexOf(val) != -1) {
                            $(this).show();
                            empty = false;
                        } else {
                            $(this).hide();
                        }
                    });
                    if (empty) {
                        $('.content-empty').show();
                    } else {
                        $('.content-empty').hide();
                    }
                }
            });
            $('.fui-searchbar .searchbar-cancel').click(function () {
                $('.fui-searchbar input').val(''), $('.store-item').show(), $('.content-empty').hide();
            });

            $("#btn-near").click(function () {
                FoxUI.loader.show('正在定位...','icon icon-location');
                $('.fui-searchbar input').val(''), $('.store-item').show(), $('.content-empty').hide();
                var arr = [];
                
                var geolocation = new BMap.Geolocation();
                geolocation.getCurrentPosition(function (r) {
                    var _this = this;
                    if (this.getStatus() == BMAP_STATUS_SUCCESS) {
                        var lat = r.point.lat, lng = r.point.lng;
                        $('.store-item').each(function () {
                            var location = $(this).find('.location');
                            var store_lng = $(this).data('lng'), store_lat = $(this).data('lat');

                            if (store_lng > 0 && store_lat > 0) {
                                var distance = core.getDistanceByLnglat(lng, lat, store_lng, store_lat);
                                $(this).data('distance', distance);
                                location.html('距离您: ' + distance.toFixed(2) + "km").show();
                            } else {
                                $(this).data('distance', 999999999999999999);
                                location.html('无法获得距离').show();
                            }
                            arr.push($(this));
                        });
                        arr.sort(function (a, b) {
                            return a.data('distance') - b.data('distance')
                        });
                        $.each(arr, function () {
                            $('.fui-list-group').append(this);
                        });
                        modal.bindEvents();
                        
                        FoxUI.loader.hide();
                    }
                }, {
                    enableHighAccuracy: true
                });
            });
        };
        modal.bindEvents  =function(){
             $('.store-item').unbind('click').click(function(){
                 var $this = $(this);
                    window.selectedStoreData = {
                          'id':  $this.data('storeid'),
                          'storename': $this.find('.storename').html(),
                          'realname': $this.find('.realname').html(),
                          'mobile': $this.find('.mobile').html(),
                          'address':$this.find('.address').html()
                    };
                    history.back();
             });
        };
        return modal;
    });