<?php require("../../log.php"); ?>
<?php echo '<?xml version="1.0" encoding="utf-8"?>'."\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<style type="text/css">
/* <![CDATA[ */
html, body { background-color: black; color: white; height: 100%; margin: 0 0 0 0; padding: 0 0 0 0; width: 100%; }
#bigt { background-color: #223344; border: 8px solid black; border-collapse: separate; color: #a0a0a0; font-family: "Courier 10 pitch", "Courier New", monospace; font-size: 7pt; height: 100%; text-align: center; vertical-align: middle; width: 100%; }
img { border: none; margin: 0 0 0 0; outline: none; padding: 0 0 0 0; }
#maincell { border: none; text-align: center; vertical-align: bottom; }
h1 { font-size: 2em; font-weight: 700; }
h2 { font-size: 1.75em; font-weight: 700; }
h3 { font-size: 1.58em; font-weight: 500; }
h4 { font-size: 1.33em; font-weight: 500; }
h5, dt { font-size: 1em; font-weight: 700; }
h6 { font-size: .8em; font-weight: 700; }
tfoot, thead { font-size: 1em; }
th { font-size: 1em; font-weight: bold; vertical-align: baseline; }
a:link { background-color: inherit; color: #bbb689; text-decoration: none; }
a:visited { background-color: inherit; color: #bbb689; text-decoration: none; }
a:active { background-color: inherit; color: #bbb689; text-decoration: none; }
a:hover { background-color: inherit; color: #fffacd; text-decoration: underline; }
small { font-size: .7em; }
big { font-size: 1.17em; }
blockquote, pre { font-family: "Courier New", monospace; }
/* ]]> */
</style>

<script type="text/javascript">
/* <![CDATA[ */
onload = function() { fishStart("maincell", 10); };
document.onkeypress = fishKeyPress;

var doperatio = 9;
var leftfish = new Image();
var leftdopefish = new Image();
leftfish.src = "fishleft.gif";
leftdopefish.src = "swimfish-left.gif";

var rightfish = new Image();
var rightdopefish = new Image();
rightfish.src = "fishright.gif";
rightdopefish.src = "swimfish-right.gif";

var fishObjects = new Array();
var container;
var osd;
var instr;
var ctop, cleft, cright, cbottom;

function fishStart(where, fishes) {
    container = document.getElementById(where);
    container.onclick = new Function("container.focus();");

    osd = document.createElement("div");
    osd.style.position = "absolute";
    osd.style.left = getLeft(container) + "px";
    osd.style.top = getTop(container) + "px";
    osd.style.textAlign = "left";
    osd.style.color = "#777777";
    osd.style.fontSize = "7pt";
    osd.style.fontFamily = '"Courier 10 Pitch", "Courier New", monospace';
    document.body.appendChild(osd);

    instr = document.createElement("div");
    instr.style.position = "absolute";
    instr.style.textAlign = "center";
    instr.style.color = "#777777";
    instr.style.fontSize = "7pt";
    instr.style.fontFamily = '"Courier 10 Pitch", "Courier New", monospace';
    instr.innerHTML = "spacebar - make fish<br/>enter - erase fish<br/>click - annoy fish";
    document.body.appendChild(instr);
    moveInstr();
    setInterval(moveInstr, 2000);

    for (i = 0; i < fishes; i++) {
        setTimeout(makeFish, i * 30000);
    }
}

function moveInstr() {
    getFishBounds();
    instr.style.top = getTop(container) + container.offsetHeight - instr.offsetHeight + "px";
    instr.style.left = getLeft(container) + container.offsetWidth / 2 - instr.offsetWidth / 2 + "px";
}

function getFishBounds() {
    ctop = getTop(container);
    cleft = getLeft(container);
    cright = getLeft(container) + container.offsetWidth;
    cbottom = getTop(container) + container.offsetHeight;
}

function updateOsd() {
    osd.innerHTML = "fish: " + fishObjects.length;
}

function fishKeyPress(e) {
    var keynum;
    if (window.event) { // IE
        keynum = event.keyCode;
    }
    else if (e.which) { // Netscape/Firefox/Opera
        keynum = e.which;
    }

    if (keynum == 32) {
        makeFish();
    }
    else if (keynum == 13) {
        killFish()
    }
}

function makeFish() {
    var num = fishObjects.push(new FishBounce());
    fishObjects[num - 1].fish.title = "fish " + num;
    fishObjects[num - 1].start();
    updateOsd();
}

function killFish() {
    if (container.hasChildNodes()) {
        fishObjects.pop().kill();
        updateOsd();
    }
}

function FishBounce() {
    var stepY = Math.round(Math.random() * 3) + 1;
    var stepX = Math.round(Math.random() * 3) + 1;

    var fish = new Image();
    fish.style.position = "absolute";
    fish.style.zIndex = Math.round(Math.random() * 666) + 666;

    var fishTop;
    var fishLeft;
    var fishYDir = 1;
    var fishXDir = 0;
    var clicked = false;
    var timer;

    this.fish = fish;
    this.start = start;
    this.kill = kill;

    var dope = false;
    if (Math.round(Math.random() * doperatio) == 1) dope = true;
    fish.src = (dope == true ? leftdopefish.src : leftfish.src);

    function start() {
        wait();
    }

    function stop(e) {
        clearInterval(timer);
    }

    function cont(e) {
        if (!clicked) move();
        clicked = false;
    }

    function click(e) {
        stepY = 22;
        stepX = 22;
        cont();
        clicked = true;
    }

    function kill() {
        clearInterval(timer);
        container.removeChild(fish);
    }

    function wait() {
        if (fish.width) ok();
        else setTimeout(wait, 1000);
    }

    function ok() {
        getFishBounds();

        fishTop = ctop;
        fishLeft = cright - fish.width;

        fish.style.top = fishTop + "px";
        fish.style.left = fishLeft + "px";

        fish.onmouseover = stop;
        fish.onmouseout = cont;
        fish.onmousedown = click;

        container.appendChild(fish);

        move();
    }

    function move() {
        if (fishTop + fish.height + stepY >= cbottom) {
            fishYDir = 0;
        }
        if (fishTop <= ctop) {
            fishYDir = 1;
        }

        if (fishLeft + fish.width + stepX >= cright) {
            fishLeft = cright - fish.width - stepX;
            fishXDir = 0;
            fish.src = (dope == true ? leftdopefish.src : leftfish.src);
        }
        if (fishLeft <= cleft) {
            fishXDir = 1;
            fish.src = (dope == true ? rightdopefish.src : rightfish.src);
        }

        if (fishYDir == 1) fishTop += stepY;
        else fishTop -= stepY;

        if (fishXDir == 1) fishLeft += stepX;
        else fishLeft -= stepX;

        fish.style.top = fishTop + "px";
        fish.style.left = fishLeft + "px";

        if (Math.round(Math.random() * 33) == 1) {
            stepX = Math.round(Math.random() * 3) + 1;
        }
        if (Math.round(Math.random() * 100) == 1) {
            stepY = Math.round(Math.random() * 3) + 1;
        }

        timer = setTimeout(move, 40);
    }
}

function getTop(obj) {
    var ret = 0;
    while (obj.offsetParent) {
        ret += obj.offsetTop;
        obj = obj.offsetParent;
    }
    return ret;
}

function getLeft(obj) {
    var ret = 0;
    while (obj.offsetParent) {
        ret += obj.offsetLeft;
        obj = obj.offsetParent;
    }
    return ret;
}
/* ]]> */
</script>

<title>fish bounce</title>
</head>

<body>
    <table id="bigt">
        <tr>
            <td id="maincell"></td>
        </tr>
    </table>
</body>
</html>