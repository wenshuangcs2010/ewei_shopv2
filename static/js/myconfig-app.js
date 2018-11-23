var version = +new Date();
require.config({
    urlArgs: 'v=' + version, 
    baseUrl: '../addons/ewei_shopv2/static/js/app',
    paths: {
        'jquery': '../dist/jquery/jquery-1.11.1.min',
        'jquery.gcjs': '../dist/jquery/jquery.gcjs',
        'tpl':'../dist/tmodjs',
        'foxui':'../dist/foxui/js/foxui.min',
        'foxui.picker':'../dist/foxui/js/foxui.picker.min',
        'foxui.citydata':'../dist/foxui/js/foxui.citydata.min',
        'jquery.qrcode':'../dist/jquery/jquery.qrcode.min',
        'ydb':'../dist/Ydb/YdbOnline',
        'swiper':'../dist/swiper/swiper.min',
        'BMap':"//api.map.baidu.com/api?v=2.0&ak=uvlCwUWyIK4zr4Umk6oqk27U",
        
    },
    shim: {
        'foxui':{
            deps:['jquery']
        },
        'foxui.picker': {
            exports: "foxui",
            deps: ['foxui','foxui.citydata']
        },
		'jquery.gcjs': {
	                 deps:['jquery']
		},
        'BMap': {
            deps: ['jquery'],
            exports: 'BMap'
        },
        
    },
    waitSeconds: 0
});
