define(['core', 'tpl','BMap'], function (core, tpl) {
        var modal = {
            store: false
        };
        var lng=0;
        var lat=0;

        var bool=false;
       
        modal.init = function (params) {
            var map = new BMap.Map("bpmap");
            modal.store = storelist= params.store;
            var geolocation = new BMap.Geolocation();
                geolocation.getCurrentPosition(function(r){
                if(this.getStatus() == BMAP_STATUS_SUCCESS){
                    lng=r.point.lng;
                    lat=r.point.lat;
                    $("#container").html("");
                    var pointA = new BMap.Point(lng,lat);  //
                //console.log(lng,lat);
                    $.each(storelist, function(index, val) {
                       var pointB = new BMap.Point(val.lng,val.lat);
                       var distance=(map.getDistance(pointA,pointB)).toFixed(2);
                       distance=(distance/1000).toFixed(2);
                      // console.log(map.getDistance(pointA,pointB));
                       storelist[index]['distance']=distance;
                    });
                  //console.log(storelist);
                    storelist.sort(function(a,b){
                        return a.distance-b.distance;
                    });
                    var result=[];
                    result.list=storelist;
                    //console.log();
                 core.tpl("#container", 'tpl_store_list', result,true);
                }

                else {
                    alert("无法获取您的位置");
                }
            },{enableHighAccuracy: true});
        };
       
        return modal;
    });