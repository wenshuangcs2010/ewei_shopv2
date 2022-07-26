function fontFun() {
    var winW = document.documentElement.clientWidth;
    if (winW >= 750) {
        document.documentElement.style.fontSize = "625%";
    }
    else {
        document.documentElement.style.fontSize = (winW / 750 * 625) + "%";
    }
}
fontFun();
window.onresize = fontFun;
 