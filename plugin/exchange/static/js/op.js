define(['core', 'tpl'], function (core, tpl) {
    var modal = {};
        modal.init = function(){
            $('.order-verify').unbind('click').click(function () {
                var ajaxurl = $(this).attr('value');
                var goods = $(this).attr('data');
                var c = '#option'+goods;
                ajaxurl = ajaxurl+'&yx='+$(c).val();
                modal.verify(ajaxurl,goods)

            });

            $('.x').unbind('click').click(function () {
                if($(this).attr('type') == 'default'){
                    $(this).removeClass('btn-default').addClass('btn-danger');
                    $(this).attr('type','danger');
                    $(this).html("<i class='icon icon-selected'></i> 已选择");
                    var e = "#goods"+$(this).attr('data');
                    $(e).val($(this).attr('data'));
                    var total = $("input[name='total']").val();
                    var new_total = Number(total) + Number("1");
                    $("input[name='total']").val(new_total);
                    $('.total').html(new_total);
                    goodsArr.push([$(this).attr('data'),$(this).attr('value')]);
                    modal.caculate(goodsArr,optionsss);
                    var goods = $(this).attr('data');
                    var option = 0;
                    if ($("div[data-id="+goods+"]").length >=1){
                        var val = $("div[data-id="+goods+"]").find('input').val();
                        var newval = Number(val) + Number(1);
                        var val = $("div[data-id="+goods+"]").find('input').val(newval);
                    }else{
                        var title = $("div[data-goodsid="+goods+"]").find(".name").text();
                        var goodsgroup = '<div class="fui-list goods-item" data-id="'+goods+'" data-goodsid="0"> <div class="fui-list-media"> <img src="'+$("div[data-id=g"+goods+"]").attr("data-value")+'" class="round package-goods-img" style="height: 55.2px;"> </div> <div class="fui-list-inner"> <div class="text">'+title+'</div> <div class="text">'+$("button[data-id="+option+"]").text()+'</div> </div> <div class="fui-list-angle"> <span class="price ">¥ <span class="marketprice">'+$(".g"+goods).text()+'</span></span> <span class="amount"> <div class="fui-number small" data-value="" data-unit="" data-maxbuy="" data-minbuy="" data-goodsid=""> <div class="minus" onclick="minus(this)" data-id="'+goods+'" data-option="'+option+'">-</div> <input class="num" type="tel" value="1" readonly data-value="'+$("div[data="+goods+"]").attr('value')+'"/> <div class="plus" onclick="plus(this)" data-id="'+goods+'" data-option="'+option+'">+</div> </div> </span> </div> </div>';
                        $(".cart .fui-list-group").append(goodsgroup);
                    }

                }else {
                    $(this).removeClass('btn-danger').addClass('btn-default');
                    $(this).attr('type','default');
                    $(this).html("选择");
                    var e = "#goods"+$(this).attr('data');
                    $(e).val('');
                    var total = $("input[name='total']").val();
                    var goods = $(this).attr('data');
                    $("div[data-id="+goods+"]").remove();
                    var i;
                    var c = 0;

                    for(i=0;i<goodsArr.length;i++){
                        if (goodsArr[i][0] == $(this).attr('data')){
                            goodsArr.splice(i,1);
                            i--;
                            c++;
                        }else{
                            continue;
                        }
                    }
                    var new_total = Number(total) - Number(c);
                    $("input[name='total']").val(new_total);
                    $('.total').html(new_total);
                    modal.caculate(goodsArr,optionsss);
                }
            });
        }
        modal.verify = function (ajaxurl,goods) {
            htmlobj=$.ajax({url:ajaxurl,async:false});
            container = new FoxUIModal({
                content: htmlobj.responseText,
                extraClass: "popup-modal",
            });
            container.show('slow');
            $('.closebtn').unbind('click').click(function () {
                container.close();
            });
            $('.cartbtn').unbind('click').click(function () {
                container.close();
            });
            $('.xuanhaole').unbind('click').click(function () {
                var data = $("input[name='data']").val();
                var e = "#option"+goods;
                var option = $(e).val();
                $(e).val(data);
                var c = "a[data='"+goods+"']";
                var count = $("input[name='count']").val();
                var old_count = $("input[name='old_count']").val();
                if (oc > 0){
                    var count_change = Number($("input[name='count']").val()) - Number(old_count);
                    count_change = Number(count_change)  - Number(oc) - Number(count_change);
                    var n = Number(count) + Number(count_change);
                    $("input[name='count']").val(n);
                    oc = 0;
                    var count_change = Number($("input[name='count']").val()) - Number($("input[name='old_count']").val());
                }else{
                    var count_change = Number(count) - Number(old_count);
                }


                if($("input[name='count']").val()>0){
                    $(c).html("<i class='icon icon-selected'></i> 已选择");
                    $(c).removeClass('btn-default').addClass('btn-danger');
                }else{
                    $(c).html(" 选择");
                    $(c).removeClass('btn-danger').addClass('btn-default');
                }
                optionsss = optionArr;
                $(".cart").find("div[data-goodsid="+goods+"]").remove();
                for(var i = 0;i<optionsss.length;i++){
                    option = optionsss[i][0];
                    if ($("button[data-id="+option+"]").val() == undefined){
                        continue;
                    }
                    if ($("div[data-id="+goods+'_'+option+"]").length >=1){
                        var val = $("div[data-id="+goods+'_'+option+"]").find('input').val();
                        var newval = Number(val) + Number(1);
                        var val = $("div[data-id="+goods+'_'+option+"]").find('input').val(newval);
                    }else{
                        var title = $("div[data-goodsid="+goods+"]").find(".name").text();
                        var goodsgroup = '<div class="fui-list goods-item" data-id="'+goods+'_'+option+'" data-goodsid="'+goods+'"> <div class="fui-list-media"> <img src="'+$(".fui-modal").find("img[data-id="+goods+"]").attr("src")+'" class="round package-goods-img" style="height: 55.2px;"> </div> <div class="fui-list-inner"> <div class="text">'+title+'</div> <div class="text">'+$("button[data-id="+option+"]").text()+'</div> </div> <div class="fui-list-angle"> <span class="price ">¥ <span class="marketprice">'+$("button[data-id="+option+"]").val()+'</span></span> <span class="amount"> <div class="fui-number small" data-value="" data-unit="" data-maxbuy="" data-minbuy="" data-goodsid=""> <div class="minus" onclick="minus(this)" data-id="'+goods+'_'+option+'">-</div> <input class="num" type="tel" value="1" readonly data-value="'+$("button[data-id="+option+"]").val()+'"/> <div class="plus" onclick="plus(this)" data-id="'+goods+'_'+option+'" data-option="'+option+'">+</div> </div> </span> </div> </div>';
                        $(".cart .fui-list-group").append(goodsgroup);
                    }
                }
                modal.caculate(goodsArr,optionsss);
                container.close();
            });
    };

    modal.caculate = function(goods,option){
        $("input[name='goods[]']").remove();
        for (var i = 0;i<goods.length;i++){
            $("div[data-goodsid="+goods[i][0]+"]").append("<input type='hidden' name='goods[]' value='"+goods[i][0]+"'>");
        }
        $("input[name='option[]']").val('');
        var opstr = '';
        for (i = 0;i<option.length;i++){
            var goodsid = $(".inoption"+option[i][0]).attr('data');
            var bs = $("#option"+goodsid).val();
            if (bs == undefined){
                bs = '';
            }
            opstr = bs + '_'+option[i][0];
            $("#option"+goodsid).val(opstr);
        }
        var arr = goods.concat(option);
        var leng = arr.length;
        $(".total").text(arr.length);
        $("input[name='total']").val(leng);
        arr.sort(function (x,y) {
            return y[1] - x[1];
        });
        if (exchangetype == 1){//按个数
            if(!Number(exchangevalue)==0){
                for(var c = 0;c<Number(exchangevalue);c++){
                    arr.splice(0,1);
                }
                var sum = 0;
                for(var d = 0;d<arr.length;d++){
                    sum = parseFloat(arr[d][1]) + parseFloat(sum);
                }
                sum = parseFloat(sum).toFixed(2);
                $(".value").html(sum);

                var again = Number(exchangevalue)-Number(leng);
                if (Number(again)<0){again = 0;}
                $(".again").html(again);
            }
        }else if(exchangetype == 2) {//按面值
            var sum = 0;
            var cha = 0;//差价
            for(var e = 0;e<arr.length;e++){
                sum = parseFloat(arr[e][1]) + parseFloat(sum);
                if (sum == parseFloat(exchangevalue).toFixed(2)){
                    break;
                }else if(sum > parseFloat(exchangevalue).toFixed(2)){
                    cha = parseFloat(sum) - parseFloat(exchangevalue);
                    break;
                }else{
                    continue;
                }
            }
            var sum2 = 0;
            for(var f = e+1;f<arr.length;f++){
                sum2 = parseFloat(arr[f][1]) + parseFloat(sum2);
            }
            var total = parseFloat(sum2) + parseFloat(cha);
            total = total.toFixed(2);
            $(".value").html(total);
        }
    };
    return modal
});