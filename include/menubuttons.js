function onBBBover(bbbM) {
    bbbM.className = "middle_h";
    dojo.query("td.left", bbbM.parentNode)[0].className = "left_h";
    dojo.query("td.right", bbbM.parentNode)[0].className = "right_h";
}
function onBBBout(bbbM) {
    bbbM.className = "middle";
    dojo.query("td.left_h", bbbM.parentNode)[0].className = "left";
    dojo.query("td.right_h", bbbM.parentNode)[0].className = "right";
}
var pl = new Image(); 
pl.src="../gfx/button-mid-hover.gif";
pl = new Image(); 
pl.src="../gfx/button-l-hover.gif";
pl = new Image(); 
pl.src="../gfx/button-r-hover.gif";
