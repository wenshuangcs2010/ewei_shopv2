define(['jquery'], function ($) {

	var biz = {};

	biz.url = function(routes, params, merch) {

		if(merch){
			var url = './merchant.php?c=site&a=entry&m=ewei_shopv2&do=web&r=' + routes.replace(/\//ig,'.');
		}else{
			var url = './index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=' + routes.replace(/\//ig,'.');
		}

		if (params) {
			if (typeof(params) == 'object') {
				url += "&" + $.toQueryString(params);
			} else if (typeof(params) == 'string') {
				url += "&" + params
			}
		}
		return url;
	};

	biz.selector =  {
		select: function (params) {

			params = $.extend({}, params || {});
			var name = params.name===undefined?'default':params.name;
			var modalid = name +"-selector-modal";
			modalObj =$('#' +modalid);
			if( modalObj.length <=0 ){
				var modal = '<div id="' +modalid +'"  class="modal fade" tabindex="-1">';
				modal += '<div class="modal-dialog" style="width: 920px;">';
				modal += '<div class="modal-content">';
				modal += '<div class="modal-header"><button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button><h3>数据选择器</h3></div>';
				modal += '<div class="modal-body" >';
				modal += '<div class="row">';
				modal += '<div class="input-group">';
				modal += '<input type="text" class="form-control" name="keyword" id="' + name +'_input" placeholder="' + ( params.placeholder===undefined?'':params.placeholder) + '" />';
				modal += '<span class="input-group-btn"><button type="button" class="btn btn-default" onclick="biz.selector.search(this, \'' + name + '\');">搜索</button></span>';
				modal += '</div>';
				modal += '</div>';
				modal += '<div class="content" style="padding-top:5px;" data-name="' + name +'"></div>';
				modal += '</div>';
				modal += '<div class="modal-footer"><a href="#" class="btn btn-default" data-dismiss="modal" aria-hidden="true">关闭</a></div>';
				modal += '</div>';
				modal += '</div>';
				modal += '</div>';

				modalObj = $(modal);
				modalObj.on('show.bs.modal',function(){
					if(params.autosearch=='1') {
						$.get(params.url, {
							keyword: ''
						}, function (dat) {
							$('.content', modalObj).html(dat);
						});
					};
				});
			};
			modalObj.modal('show');
		}
		, search:function(searchbtn, name){
			var input = $(searchbtn).closest('.modal').find('#' + name + '_input');
			var selector = $("#" + name + '_selector');
			var needkeywords = true;
			if( selector.data('nokeywords')=='1') {
				needkeywords = false;
			};

			var keyword = $.trim( input.val() );
			if(keyword=='' && needkeywords ){
				input.focus();
				return;
			}

			var modalObj =  $('#' +name +"-selector-modal");
			$('.content' ,modalObj).html("正在搜索....");

			$.get( selector.data('url'), {
				keyword: keyword
			}, function(dat){
				$('.content' ,modalObj).html(dat);
			});
		}
		, remove: function (obj,name ) {
			var selector = $("#" + name + '_selector');
			var css = selector.data('type') =='image'?'.multi-item':'.multi-audio-item';
			$(obj).closest(css).remove();
			biz.selector.refresh(name);
		}
		, set: function (obj, data) {

			var name = $(obj).closest('.content').data('name');
			var modalObj =  $('#' +name +"-selector-modal");
			var selector  =  $('#' +name +"_selector");

			var container = $('.container',selector);
			var key = selector.data('key') || 'id',
				text = selector.data('text') || 'title',
				thumb = selector.data('thumb') || 'thumb',
				multi = selector.data('multi') || 0,
				type = selector.data('type') || 'image',
				callback = selector.data('callback') || '',
				css = type=='image'?'.multi-item':'.multi-audio-item';

			if ($( css + '[data-' +key +'="' + data[key] + '"]',container).length > 0) {
				if( multi  === 0){
					modalObj.modal('hide');
				}
				return;
			}

			var id  = multi===0? name: name+"[]";
			var html ="";
			if(type=='image'){
				html +='<div class="multi-item" data-' + key+'="' + data[key] + '" data-name="' +name + '">';
				html += '<img class="img-responsive img-thumbnail" src="' + data[thumb] + '" >';
				html += '<div class="img-nickname">' + data[text] + '</div>';
				html += '<input type="hidden" value="' + data[key] + '" name="' + id +'">';
				html += '<em onclick="biz.selector.remove(this,\'' + name +'\')"  class="close">×</em>';
				html += '</div>';
			} else{
				html+="<div class='multi-audio-item' data-" + key+"='" + data[key] + "' data-name='" + name + "'>";
				html+="<div class='input-group'><input type='hidden' name='" + id  +"' value='" + data[key] +"'> ";
				html+="<input type='text' class='form-control img-textname' readonly='' value='" +  data[text] +"'>";
				html+="<div class='input-group-btn'><button class='btn btn-default' onclick='biz.selector.remove(this,\"" + name +"\")' type='button'><i class='fa fa-remove'></i></button></div></div></div>";
			}
			if(multi===0){
				container.html(html);
				modalObj.modal('hide');
			} else{
				container.append(html);
			}
			biz.selector.refresh(name);

			if( callback!==''){
				var callfunc = eval(callback);
				if(callfunc!==undefined){
					callfunc(data, obj);
				}
			}

		},refresh:function(name){

			var titles = '';
			var selector = $('#' + name + '_selector');

			var type = selector.data('type') || 'image';

			if(type=='image'){
				$('.multi-item',selector).each(function () {
					titles += " " + $(this).find('.img-nickname').html() ;
					if( $('.multi-item',selector).length>1){
						titles+="; ";
					}
				});
			} else{
				$('.multi-audio-item',selector).each(function () {
					titles += " " + $(this).find('.img-textname').val();
					if( $('.multi-audio-item',selector).length>1){
						titles+="; ";
					}
				});
			}




			$('#' + name + "_text",selector).val(titles);
		}

	};

	biz.selector_new =  {
		select: function (params) {

			params = $.extend({}, params || {});
			var name = params.name===undefined?'default':params.name;
			var modalid = name +"-selector-modal";
			modalObj =$('#' +modalid);
			if( modalObj.length <=0 ){
				var modal = '<div id="' +modalid +'"  class="modal fade" tabindex="-1">';
				modal += '<div class="modal-dialog" style="width: 920px;">';
				modal += '<div class="modal-content">';
				modal += '<div class="modal-header"><button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button><h3>数据选择器</h3></div>';
				modal += '<div class="modal-body" >';
				modal += '<div class="row">';
				modal += '<div class="input-group">';
				modal += '<input type="text" class="form-control" name="keyword" id="' + name +'_input" placeholder="' + ( params.placeholder===undefined?'':params.placeholder) + '" />';
				modal += '<span class="input-group-btn"><button type="button" class="btn btn-default" onclick="biz.selector_new.search(this, \'' + name + '\');">搜索</button></span>';
				modal += '</div>';
				modal += '</div>';
				modal += '<div class="content" style="padding-top:5px;" data-name="' + name +'"></div>';
				modal += '</div>';
				modal += '<div class="modal-footer"><a href="#" class="btn btn-default" data-dismiss="modal" aria-hidden="true">关闭</a></div>';
				modal += '</div>';
				modal += '</div>';
				modal += '</div>';

				modalObj = $(modal);
				modalObj.on('show.bs.modal',function(){
					if(params.autosearch=='1') {
						$.get(params.url, {
							keyword: ''
						}, function (dat) {
							$('.content', modalObj).html(dat);
						});
					};
				});
			};
			modalObj.modal('show');
		}
		, search:function(searchbtn, name){
			var input = $(searchbtn).closest('.modal').find('#' + name + '_input');
			var selector = $("#" + name + '_selector');
			var needkeywords = true;
			if( selector.data('nokeywords')=='1') {
				needkeywords = false;
			};

			var keyword = $.trim( input.val() );
			if(keyword=='' && needkeywords ){
				input.focus();
				return;
			}

			var modalObj =  $('#' +name +"-selector-modal");
			$('.content' ,modalObj).html("正在搜索....");

			$.get( selector.data('url'), {
				keyword: keyword
			}, function(dat){
				$('.content' ,modalObj).html(dat);
			});
		}
		, remove: function (obj,name ) {
			var selector = $("#" + name + '_selector');
			var css = selector.data('type') =='image'?'.multi-item':'.multi-product-item';
			$(obj).closest(css).remove();
			biz.selector_new.refresh(name);
		}
		, set: function (obj, data) {

			var name = $(obj).closest('.content').data('name');
			var modalObj =  $('#' +name +"-selector-modal");
			var selector  =  $('#' +name +"_selector");

			var key = selector.data('key') || 'id',
				text = selector.data('text') || 'title',
				thumb = selector.data('thumb') || 'thumb',
				multi = selector.data('multi') || 0,
				type = selector.data('type') || 'image',
				callback = selector.data('callback') || '',
				css = type=='image'?'.multi-item':'.multi-product-item',
				optionurl =selector.data('optionurl') || '',
				selectorid =selector.data('selectorid') || '';

			var container = $('.container',selector);


			if ($( css + '[data-' +key +'="' + data[key] + '"]',container).length > 0) {
				if( multi  === 0){
					modalObj.modal('hide');
				}
				return;
			}

			var id  = multi===0? name: name+"[]";
			var html ="";
			if(type=='image'){
				html +='<div class="multi-item" data-' + key+'="' + data[key] + '" data-name="' +name + '">';
				html += '<img class="img-responsive img-thumbnail" src="' + data[thumb] + '" >';
				html += '<div class="img-nickname">' + data[text] + '</div>';
				html += '<input type="hidden" value="' + data[key] + '" name="' + id +'">';
				html += '<em onclick="biz.selector_new.remove(this,\'' + name +'\')"  class="close">×</em>';
				html += '</div>';
			} else if(type=='product'){
				var optionurl  = optionurl =='' ? 'sale.package.hasoption': optionurl;
				var url = "index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=" + optionurl+"&goodsid="+data[key]+"&selectorid=" + selectorid;

				html += '<tr class="multi-product-item" data-' + key+'="' + data[key] + '" data-name="' + name + '">';
				html += "<input type='hidden' name='" + id  +"' value='" + data[key] +"'> ";
				html += "<input type='hidden' class='form-control img-textname' value='" +  data[text] +"'>";
				html +=	'<td style="width:80px;"><img src="' + data[thumb] + '" style="width:70px;border:1px solid #ccc;padding:1px" /></td>';
				html += '<td style="width:220px;">'+data[text]+'</td>';
				html += "<td><a class='btn btn-default btn-sm' data-toggle='ajaxModal' href='"+url+"' id='" + selectorid+"optiontitle"+data[key]+"'>设置</a>" +
					"<input type='hidden' id='" + selectorid+"packagegoods"+data[key]+"' value='' name='" + selectorid+"packagegoods["+data[key]+"]'></td>";
				html += '<td><a href="javascript:void(0);" class="btn btn-default btn-sm" onclick="biz.selector_new.remove(this,\'' + name +'\')" title="删除">';
				html += '<i class="fa fa-times"></i></a></td></tr>';
			}else{
				html+="<div class='111 multi-audio-item' data-" + key+"='" + data[key] + "' data-name='" + name + "'>";
				html+="<div class='input-group'><input type='hidden' name='" + id  +"' value='" + data[key] +"'> ";
				html+="<input type='text' class='form-control img-textname' readonly='' value='" +  data[text] +"'>";
				html+="<div class='input-group-btn'><button class='btn btn-default' onclick='biz.selector_new.remove(this,\"" + name +"\")' type='button'>" +
					"<i class='fa fa-remove'></i></button></div></div></div>";
			}
			if(multi===0){
				container.html(html);
				modalObj.modal('hide');
			} else{
				$("#param-items" + selectorid).append(html);
			}
			biz.selector_new.refresh(name);

			if( callback!==''){
				var callfunc = eval(callback);
				if(callfunc!==undefined){
					callfunc(data, obj);
				}
			}

		},refresh:function(name){

			var titles = '';
			var selector = $('#' + name + '_selector');

			var type = selector.data('type') || 'image';

			if(type=='image'){
				$('.multi-item',selector).each(function () {
					titles += " " + $(this).find('.img-nickname').html() ;
					if( $('.multi-item',selector).length>1){
						titles+="; ";
					}
				});
			} else{
				$('.multi-product-item',selector).each(function () {
					titles += " " + $(this).find('.img-textname').val();
					if( $('.multi-product-item',selector).length>1){
						titles+="; ";
					}
				});
			}




			$('#' + name + "_text",selector).val(titles);
		}

	};

    biz.selector_open =  {
    	callback:function(){

		},
        select: function (params) {

            params = $.extend({}, params || {});
			biz.selector_open.callback = typeof( params.callback )==='undefined'?false: params.callback;

            var name = params.name===undefined?'default':params.name;
            var modalid = name +"-selector-modal";
            modalObj =$('#' +modalid);
            if( modalObj.length <=0 ){
                var modal = '<div id="' +modalid +'"  class="modal fade" tabindex="-1">';
                modal += '<div class="modal-dialog" style="width: 920px;">';
                modal += '<div class="modal-content">';
                modal += '<div class="modal-header"><button aria-hidden="true" data-dismiss="modal" class="close" type="button">×</button><h3>数据选择器</h3></div>';
                modal += '<div class="modal-body" >';
                modal += '<div class="row">';
                modal += '<div class="input-group">';
                modal += '<input type="text" class="form-control" name="keyword" id="' + name +'_input" placeholder="' + ( params.placeholder===undefined?'':params.placeholder) + '" />';
                modal += '<span class="input-group-btn"><button type="button" class="btn btn-default" onclick="biz.selector.search(this, \'' + name + '\');">搜索</button></span>';
                modal += '</div>';
                modal += '</div>';
                modal += '<div class="content" style="padding-top:5px;" data-name="' + name +'"></div>';
                modal += '</div>';
                modal += '<div class="modal-footer"><a href="#" class="btn btn-default" data-dismiss="modal" aria-hidden="true">关闭</a></div>';
                modal += '</div>';
                modal += '</div>';
                modal += '</div>';

                modalObj = $(modal);
                modalObj.on('show.bs.modal',function(){
                    if(params.autosearch=='1') {
                        $.get(params.url, {
                            keyword: ''
                        }, function (dat) {
                            $('.content', modalObj).html(dat);
                        });
                    };
                });
            };
            modalObj.modal('show');
        }
        , search:function(searchbtn, name){
            var input = $(searchbtn).closest('.modal').find('#' + name + '_input');
            var selector = $("#" + name + '_selector');
            var needkeywords = true;
            if( selector.data('nokeywords')=='1') {
                needkeywords = false;
            };

            var keyword = $.trim( input.val() );
            if(keyword=='' && needkeywords ){
                input.focus();
                return;
            }

            var modalObj =  $('#' +name +"-selector-modal");
            $('.content' ,modalObj).html("正在搜索....");

            $.get( selector.data('url'), {
                keyword: keyword
            }, function(dat){
                $('.content' ,modalObj).html(dat);
            });
        }
        , remove: function (obj,name ) {
            var selector = $("#" + name + '_selector');
            var css = selector.data('type') =='image'?'.multi-item':'.multi-audio-item';
            $(obj).closest(css).remove();
            biz.selector.refresh(name);
        }
        , set: function (obj, data) {


			var name = $(obj).closest('.content').data('name');
			var modalObj =  $('#' +name +"-selector-modal");
			var selector  =  $('#' +name +"_selector");

			var  multi = selector.data('multi') || 0;
			if(multi===0){
				modalObj.modal('hide');
			}

			if( typeof(biz.selector_open.callback)==='function'){
					biz.selector_open.callback(data, obj);
            }
        }
    };
	window.biz = biz;
	return biz;
});
 