<?php echo '<?xml version="1.0" encoding="utf-8"?>'."\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>sud</title>
<script type="text/javascript" src="../jquery-1.5.1.min.js"></script>

<style type="text/css">
/* <![CDATA[ */

html, body, input {
    font-family: Verdana, Ariel;
    font-size: 8pt;
}

body {
    background-color: #ebebeb;
    color: #000000;
    width:100%;
    margin:0;
    padding:0;
    border:0;
}

.disabled {
    background-color: #2b3c4d;
    color: #ffffff;
}

.enabled {
    background-color: #ffffff;
    color: #000000;
}

#sudokuForm table {
    border-collapse: collapse;
    margin: 10px;
    background-color: #223344;
    margin-left: auto;
    margin-right: auto;
}

#str {
    background-color: #ffffff;
    color: #000000;
}

#sudokuForm table input {
    width: 2em;
    text-align: center;
    padding-top: 4px;
    padding-bottom: 4px;
    border-style: none;
}

#clear,#lock,#check,#solve,#populate,#encode {
    margin: 5px;
}

#clear,#lock,#check,#solve {
    width: 5em;
}

#populate,#encode {
    width: 8em;
}

#str {
    margin: 10px;
    width: 55em;
}

.left {
    border-left: 4px solid #374859;
}

.top {
    border-top: 4px solid #374859;
}

.right {
    border-right: 4px solid #374859;
}

.bottom {
    border-bottom: 4px solid #374859;
}

#divOut {
    position: relative;
    clear: both;
    float: left;
    width: 100%;
    text-align: center;
}

#divIn {
    margin-left: auto;
    margin-right: auto;
    position: relative;
    top: 10em;
    width: 60em;
    text-align: center;
}

#status {
    margin-top: 1em;
    color : #444444;
}

/* ]]> */
</style>

<script type="text/javascript">
/* <![CDATA[ */

var SIZE = 9;
var SECTORSIZE = 3;

var puzzle = ",3,,,,9,6,,,2,,,,,7,3,,,8,9,,,5,,,2,7,,,,8,9,6,7,,,,1,8,7,,4,5,9,,,,4,5,1,3,,,,3,8,,,7,,,4,6,,,9,4,,,,,8,,,2,1,,,,3,";
//",,,,,,,,,,,,,,3,,8,5,,,1,,2,,,,,,,,5,,7,,,,,,4,,,,1,,,,9,,,,,,,,5,,,,,,,7,3,,,2,,,1,,,,,,,,,4,,,9";

$(function() {
    $("form").submit(function() {
        return false;
    });

    $("input").focus(function() {
        $("#status").html("&nbsp;");
    });

    $("#sudokuForm input:text").keypress(function(e) {
        if (e.keyCode == 13) e.preventDefault();
    });

    $("#clear").click(function() {
        var $cell;
        for (var y = 0; y < SIZE; y++) {
            for (var x = 0; x < SIZE; x++) {
                $cell = $("#cell" + x + "-" + y);
                $cell.attr("value", "");
                $cell.removeClass("disabled");
                $cell.addClass("enabled");
                $cell.removeAttr("readonly");
            }
        }
    });

    $("#lock").click(function() {
        var $cell, val;
        for (var y = 0; y < SIZE; y++) {
            for (var x = 0; x < SIZE; x++) {
                $cell = $("#cell" + x + "-" + y);
                val = $cell.attr("value");
                if (checkSpot([ x, y ], val)) {
                    $cell.removeClass("enabled");
                    $cell.addClass("disabled");
                    $cell.attr("readonly", "readonly");
                }
                else {
                    $cell.removeClass("disabled");
                    $cell.addClass("enabled");
                    $cell.removeAttr("readonly");
                }
            }
        }
    });

    $("#check").click(function() {
        $("#status").text(checkSudoku() ? "Pass." : "Fail.");
    });

    $("#solve").click(function() {
        var start = (new Date()).getTime();
        var result = solveBrute();

        var took = (new Date()).getTime() - start;
        var secs = took / 1000;

        $("#status").text((result ? "Pass." : "Fail.") + "  Took " + secs + " seconds.");

    });

    $("#populate").click(function() {
        var str = $("#str").attr("value");
        var nums = str.split(/,/);
        var chr, $cell;
        for (var y = 0; y < SIZE; y++) {
            for (var x = 0; x < SIZE; x++) {
                chr = nums[y * SIZE + x];
                $cell = $("#cell" + x + "-" + y);
                if (chr == null || !String(chr).match(/^[1-9]$/)) {
                    $cell.attr("value", "");
                    $cell.removeClass("disabled");
                    $cell.addClass("enabled");
                    $cell.removeAttr("readonly");
                }
                else {
                    $cell.attr("value", chr);
                    $cell.removeClass("enabled");
                    $cell.addClass("disabled");
                    $cell.attr("readonly", "readonly");
                }
            }
        }
    });

    $("#encode").click(function() {
        var str = "";
        var chr;
        for (var y = 0; y < SIZE; y++) {
            for (var x = 0; x < SIZE; x++) {
                chr = $.trim($("#cell" + x + "-" + y).attr("value"));
                str += chr + ",";
            }
        }
        str = str.substring(0, str.length - 1);
        $("#str").attr("value", str);
    });

    $("#str").attr("value", puzzle);
});

function getSpotList() {
    var spotList = new Array();
    var $cell, val;
    for (var y = 0; y < SIZE; y++) {
        for (var x = 0; x < SIZE; x++) {
            $cell = $("#cell" + x + "-" + y);
            val = $cell.attr("value");
            if (val == null || val == "") {
                spotList.push( [ x, y ] );
            }
        }
    }
    return spotList;
}

function checkSpot(spot, val) {
    if (!String(val).match(/^[1-9]$/)) return false;
    var $cell, sector;
    for (var x = 0; x < SIZE; x++) {
        $cell = $("#cell" + x + "-" + spot[1]);
        if (spot[0] != x && $cell.attr("value") == val)
            return false;
    }

    for (var y = 0; y < SIZE; y++) {
        $cell = $("#cell" + spot[0] + "-" + y);
        if (spot[1] != y && $cell.attr("value") == val)
            return false;
    }

    sector = [ (Math.floor(spot[0] / SECTORSIZE) * SECTORSIZE), (Math.floor(spot[1] / SECTORSIZE) * SECTORSIZE) ];
    for (var y = sector[1]; y < sector[1] + SECTORSIZE; y++) {
        for (var x = sector[0]; x < sector[0] + SECTORSIZE; x++) {
            $cell = $("#cell" + x + "-" + y);
            if (spot[0] != x && spot[1] != y && $cell.attr("value") == val)
                return false;
        }
    }
    return true;
}

function solveBrute() {
    var spotList = getSpotList();
    var back = 1;
    var spot, $cell, $bcell, found;
    for (var s = 0; s < spotList.length; s++) {
        spot = spotList[s];
        $cell = $("#cell" + spot[0] + "-" + spot[1]);
        found = false;
        for (var i = back; i <= SIZE && !found; i++) {
            if (checkSpot(spot, i)) {
                $cell.attr("value", i);
                back = 1;
                found = true;
            }
        }
        if (!found) {
            s--;
            if (s < 0) return false;
            spot = spotList[s];
            $bcell = $("#cell" + spot[0] + "-" + spot[1]);
            back = Number($bcell.attr("value")) + 1;
            $bcell.attr("value", "");
            s--;
        }
    }
    return checkSudoku();
}

function checkSudoku() {
    var $cell, val;
    for (var y = 0; y < SIZE; y++) {
        for (var x = 0; x < SIZE; x++) {
            $cell = $("#cell" + x + "-" + y);
            val = $cell.attr("value");
            if (!checkSpot([ x, y ], val))
                return false;
        }
    }
    return true;
}

/* ]]> */
</script>

</head>

<body>
    <div id="divOut">
    <div id="divIn">
    <form id="sudokuForm" action="" method="get">
        <div>
            <input type="button" id="clear" value="clear" />
            <input type="button" id="lock" value="lock" />
        </div>
        <div>
        <table>
            <tr>
                <td class="top left">
                    <input type="text" name="cell0-0" id="cell0-0" maxlength="1" />
                </td>
                <td class="top">
                    <input type="text" name="cell1-0" id="cell1-0" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell2-0" id="cell2-0" maxlength="1" />
                </td>
                <td class="top left">
                    <input type="text" name="cell3-0" id="cell3-0" maxlength="1" />
                </td>
                <td class="top">
                    <input type="text" name="cell4-0" id="cell4-0" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell5-0" id="cell5-0" maxlength="1" />
                </td>
                <td class="top left">
                    <input type="text" name="cell6-0" id="cell6-0" maxlength="1" />
                </td>
                <td class="top">
                    <input type="text" name="cell7-0" id="cell7-0" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell8-0" id="cell8-0" maxlength="1" />
                </td>
            </tr>
            <tr>
                <td class="left">
                    <input type="text" name="cell0-1" id="cell0-1" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell1-1" id="cell1-1" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell2-1" id="cell2-1" maxlength="1" />
                </td>
                <td class="left">
                    <input type="text" name="cell3-1" id="cell3-1" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell4-1" id="cell4-1" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell5-1" id="cell5-1" maxlength="1" />
                </td>
                <td class="left">
                    <input type="text" name="cell6-1" id="cell6-1" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell7-1" id="cell7-1" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell8-1" id="cell8-1" maxlength="1" />
                </td>
            </tr>
            <tr>
                <td class="bottom left">
                    <input type="text" name="cell0-2" id="cell0-2" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell1-2" id="cell1-2" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell2-2" id="cell2-2" maxlength="1" />
                </td>
                <td class="bottom left">
                    <input type="text" name="cell3-2" id="cell3-2" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell4-2" id="cell4-2" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell5-2" id="cell5-2" maxlength="1" />
                </td>
                <td class="bottom left">
                    <input type="text" name="cell6-2" id="cell6-2" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell7-2" id="cell7-2" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell8-2" id="cell8-2" maxlength="1" />
                </td>
            </tr>
            <tr>
                <td class="top left">
                    <input type="text" name="cell0-3" id="cell0-3" maxlength="1" />
                </td>
                <td class="top ">
                    <input type="text" name="cell1-3" id="cell1-3" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell2-3" id="cell2-3" maxlength="1" />
                </td>
                <td class="top left">
                    <input type="text" name="cell3-3" id="cell3-3" maxlength="1" />
                </td>
                <td class="top">
                    <input type="text" name="cell4-3" id="cell4-3" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell5-3" id="cell5-3" maxlength="1" />
                </td>
                <td class="top left">
                    <input type="text" name="cell6-3" id="cell6-3" maxlength="1" />
                </td>
                <td class="top">
                    <input type="text" name="cell7-3" id="cell7-3" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell8-3" id="cell8-3" maxlength="1" />
                </td>
            </tr>
            <tr>
                <td class="left">
                    <input type="text" name="cell0-4" id="cell0-4" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell1-4" id="cell1-4" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell2-4" id="cell2-4" maxlength="1" />
                </td>
                <td class="left">
                    <input type="text" name="cell3-4" id="cell3-4" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell4-4" id="cell4-4" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell5-4" id="cell5-4" maxlength="1" />
                </td>
                <td class="left">
                    <input type="text" name="cell6-4" id="cell6-4" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell7-4" id="cell7-4" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell8-4" id="cell8-4" maxlength="1" />
                </td>
            </tr>
            <tr>
                <td class="bottom left">
                    <input type="text" name="cell0-5" id="cell0-5" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell1-5" id="cell1-5" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell2-5" id="cell2-5" maxlength="1" />
                </td>
                <td class="bottom left">
                    <input type="text" name="cell3-5" id="cell3-5" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell4-5" id="cell4-5" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell5-5" id="cell5-5" maxlength="1" />
                </td>
                <td class="bottom left">
                    <input type="text" name="cell6-5" id="cell6-5" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell7-5" id="cell7-5" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell8-5" id="cell8-5" maxlength="1" />
                </td>
            </tr>
            <tr>
                <td class="top left">
                    <input type="text" name="cell0-6" id="cell0-6" maxlength="1" />
                </td>
                <td class="top">
                    <input type="text" name="cell1-6" id="cell1-6" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell2-6" id="cell2-6" maxlength="1" />
                </td>
                <td class="top left">
                    <input type="text" name="cell3-6" id="cell3-6" maxlength="1" />
                </td>
                <td class="top">
                    <input type="text" name="cell4-6" id="cell4-6" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell5-6" id="cell5-6" maxlength="1" />
                </td>
                <td class="top left">
                    <input type="text" name="cell6-6" id="cell6-6" maxlength="1" />
                </td>
                <td class="top">
                    <input type="text" name="cell7-6" id="cell7-6" maxlength="1" />
                </td>
                <td class="top right">
                    <input type="text" name="cell8-6" id="cell8-6" maxlength="1" />
                </td>
            </tr>
            <tr>
                <td class="left">
                    <input type="text" name="cell0-7" id="cell0-7" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell1-7" id="cell1-7" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell2-7" id="cell2-7" maxlength="1" />
                </td>
                <td class="left">
                    <input type="text" name="cell3-7" id="cell3-7" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell4-7" id="cell4-7" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell5-7" id="cell5-7" maxlength="1" />
                </td>
                <td class="left">
                    <input type="text" name="cell6-7" id="cell6-7" maxlength="1" />
                </td>
                <td>
                    <input type="text" name="cell7-7" id="cell7-7" maxlength="1" />
                </td>
                <td class="right">
                    <input type="text" name="cell8-7" id="cell8-7" maxlength="1" />
                </td>
            </tr>
            <tr>
                <td class="bottom left">
                    <input type="text" name="cell0-8" id="cell0-8" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell1-8" id="cell1-8" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell2-8" id="cell2-8" maxlength="1" />
                </td>
                <td class="bottom left">
                    <input type="text" name="cell3-8" id="cell3-8" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell4-8" id="cell4-8" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell5-8" id="cell5-8" maxlength="1" />
                </td>
                <td class="bottom left">
                    <input type="text" name="cell6-8" id="cell6-8" maxlength="1" />
                </td>
                <td class="bottom">
                    <input type="text" name="cell7-8" id="cell7-8" maxlength="1" />
                </td>
                <td class="bottom right">
                    <input type="text" name="cell8-8" id="cell8-8" maxlength="1" />
                </td>
            </tr>
        </table>
        </div>
        <div>
            <input type="button" id="check" value="check" />
            <input type="button" id="solve" value="solve" />
        </div>
        <div id="status">&nbsp;</div>
    </form>
    <form id="populateForm" action="" method="get">
        <div>
            <input type="button" id="populate" value="populate &uarr;" />
            <input type="button" id="encode" value="encode &darr;" />
        </div>
        <div>
            <input type="text" name="str" id="str" />
        </div>
    </form>
    </div>
    </div>
</body>
</html>
