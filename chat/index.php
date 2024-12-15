<?php
$join = $_POST["join"];
$retrieve = $_POST["retrieve"];
$input = $_POST["input"];
$nick = $_POST["nick"];
$who = $_POST["who"];
$reset = $_POST["reset"];
$auth = $_POST["auth"];

function connect() {
    $con = mysql_connect('fdb2.awardspace.com', 'taverner_rollo', '23peyote46') or throw_error("con");
    mysql_select_db('taverner_rollo') or throw_error("con");
    return $con;
}

function join_chat() {
    if (nick() == null) {
        $sql = sprintf('insert into users(identifier) values ("%s")',
                my_escape(session_id()));
        mysql_query($sql) or throw_error("join");
        set_nick("user" . user_id());
    }
}

function retrieve() {
    global $retrieve;

    $sql = sprintf('select time, nick, text, event from chat c join users u on u.id = c.user_id where time > %s order by time',
            my_escape($retrieve));
    $result = mysql_query($sql) or throw_error("ret");

    $rows = array();

    while ($line = mysql_fetch_array($result)) {
        $rows[] = array( "time" => $line[0],
                         "nick" => htmlspecialchars($line[1]),
                         "text" => htmlspecialchars($line[2]),
                         "event" => $line[3] );
    }
    mysql_free_result($result);

    if (count($rows) > 0) {
        echo json_encode($rows);
    }
}

function input() {
    global $input;
    $input = trim($input);

    if ($input != "") {
        $sql = sprintf('insert into chat (user_id, text, time) values (%s, "%s", %s)',
                user_id(),
                my_escape($input),
                microtime(true));
        mysql_query($sql) or throw_error("input");
    }
}

function nick() {
    $sql = sprintf('select nick from users where identifier = "%s"',
            my_escape(session_id()));
    $result = mysql_query($sql);
    $nick = mysql_result($result, 0);
    mysql_free_result($result);
    return $nick;
}

function set_nick($nick) {
    $changed = true;

    $sql = sprintf('select 1 from users where nick = "%s"',
            my_escape($nick));
    $result = mysql_query($sql) or throw_error("set nick");
    mysql_num_rows($result) == 0 or $changed = false;
    mysql_free_result($result);

    if ($changed) {
        $sql = sprintf('update users set nick = "%s" where id = %s',
                my_escape($nick),
                user_id());
        mysql_query($sql) or throw_error("set nick");
    }
    return $changed;
}

function who() {
    $nick = nick();

    $sql = 'select nick, max(time) from users u left join chat c on c.user_id = u.id and event = 0 group by nick order by 2, u.id';
    $result = mysql_query($sql) or throw_error("who");

    $rows = array();

    while ($line = mysql_fetch_array($result)) {
        $rows[] = array( "time" => microtime(true),
                         "nick" => htmlspecialchars($nick),
                         "text" => htmlspecialchars($line[0] . ( $line[1] == null ? "" : " (spoke " . duration(microtime(true) - $line[1]) . " ago)" )),
                         "event" => "1" );
    }

    if (count($rows) > 0) {
        $rows[] = array( "time" => microtime(true),
                         "nick" => htmlspecialchars($nick),
                         "text" => htmlspecialchars(mysql_num_rows($result) . " users"),
                         "event" => "1" );
        echo json_encode($rows);
    }

    mysql_free_result($result);
}

function duration($secs) {
    $text = "";
    $num = 0;
    if (is_numeric($secs)) {
        if ($secs >= 31556926) {
            $num = floor($secs / 31556926);
            $text .=  $num . ( $num == 1 ? " year, " : " years, " );
            $secs = ($secs % 31556926);
        }
        if ($secs >= 86400) {
            $num = floor($secs / 86400);
            $text .=  $num . ( $num == 1 ? " day, " : " days, " );
            $secs = ($secs % 86400);
        }
        if ($secs >= 3600) {
            $num = floor($secs / 3600);
            $text .=  $num . ( $num == 1 ? " hour, " : " hours, " );
            $secs = ($secs % 3600);
        }
        if ($secs >= 60) {
            $num = floor($secs / 60);
            $text .=  $num . ( $num == 1 ? " minute, " : " minutes, " );
            $secs = ($secs % 60);
        }
        $num = floor($secs);
        $text .= $num . ( $num == 1 ? " second" : " seconds" );
    }
    return trim($text);
}

function auth() {
    global $auth;
    if ($auth == "crlbgroehapvgre") {
        return true;
    }
    return false;
}

function resetDatabase() {
    if (auth()) {
        $sql = 'drop table if exists users;';
        mysql_query($sql) or throw_error();
        $sql = 'drop table if exists chat;';
        mysql_query($sql) or throw_error();
        $sql = 'create table users ('
             . ' id bigint not null auto_increment,'
             . ' nick varchar(32),'
             . ' identifier varchar(256) not null unique,'
             . ' constraint pk_users primary key (id)'
             . ' );';
        mysql_query($sql) or throw_error();
        $sql = 'create table chat ('
             . ' id bigint not null auto_increment,'
             . ' time decimal(60,6) not null default 0,'
             . ' event int default 0,'
             . ' text varchar(256) not null,'
             . ' user_id bigint not null,'
             . ' constraint pk_chat primary key (id),'
             . ' constraint fk_chat_users foreign key (user_id) references users(id) on update cascade on delete cascade'
             . ' );';
        mysql_query($sql) or throw_error();
    }
}

function user_id() {
    $sql = sprintf('select id from users where identifier = "%s"',
            my_escape(session_id()));
    $result = mysql_query($sql) or throw_error("user id");;

    $user_id = mysql_result($result, 0);
    mysql_free_result($result);
    return $user_id;
}

function throw_error($msg = "") {
    header("HTTP/1.1 500 Internal Server Error");
    die($msg);
}

function my_escape($text) {
    if (get_magic_quotes_gpc()) {
        return mysql_real_escape_string(stripslashes($text));
    }
    return mysql_real_escape_string($text);
}

function process() {
    global $join, $retrieve, $input, $nick, $who, $reset;
    
    $supress = false;    
    $con = connect();

    session_start();
    join_chat();
    
    if (isset($join)) {
        join_chat();
        $supress = true;
    }
    elseif (isset($retrieve)) {
        retrieve();
        $supress = true;
    }
    elseif (isset($input)) {
        input();
        $supress = true;
    }
    elseif (isset($nick)) {
        $nick = trim($nick);
        $oldnick = nick();

        if ($nick != $oldnick
                && $nick != ""
                && !preg_match("/^user/i", $nick)
                && preg_match("/^[a-z]\w{2,}$/i", $nick)
                && set_nick($nick)) {

            $msg = "$oldnick is now known as $nick";

            $sql = sprintf('insert into chat (user_id, text, time, event) values (%s, "%s", %s, 1)',
                user_id(),
                my_escape($msg),
                microtime(true));

            mysql_query($sql) or throw_error("known as");
        }
        else {
            echo $oldnick;
        }
        $supress = true;
    }
    elseif (isset($who)) {
        who();
        $supress = true;
    }
    elseif (isset($reset)) {
        resetDatabase();
        $supress = true;
    }


    mysql_close($con);
    return $supress;
}

if (process()) {
    return;
}

require("../../log.php");

echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title></title>
<script type="text/javascript" src="../jquery-1.6.4.min.js"></script>
<link rel="stylesheet" type="text/css" href="../jquery-ui/theme/jquery-ui-1.8.16.custom.css" />
<script type="text/javascript" src="../jquery-ui/js/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="../textinputs_jquery.js"></script>

<style type="text/css">
/* <![CDATA[ */

body { font-size:8pt; background-color:#051015; color:white; }
a, li { color:#778899; }
a { text-decoration:none; font-weight:bold; }
a:hover { text-decoration:underline; color:white; }

#bigDiv { margin:0; padding:0; text-align:center; }
#outDiv { width:616px; height:410px; margin:0 auto; padding:0; }
#chatDiv { margin:100px 0 0 0; padding:0; }
#chatForm { width:616px; margin:0 auto; padding:0 0 0 2px; }
#output { width:600px; height:398px; margin:0 auto; padding:0 0 2px 0; text-align:left; overflow-x:hidden; overflow-y:scroll; }
#output div { margin:1px; }
#input { width:566px; height:16px; margin:0; padding:1px; }
#submit { width:30px; margin:0; padding:0; }

.chat { font-family:monospace; background-color:black; color:white; border:2px inset grey; border-radius:4px; }
.time { color:#444444; }
.user { color:#808080; }
.error { color:#FF0000; }
.talk { color:#808080; }
.local-event { color:#444444; }
.server-event { color:#444444; }

#outDiv .ui-icon-gripsmall-diagonal-se { background-image:none; }

/* ]]> */
</style>

<script type="text/javascript">
/* <![CDATA[ */

var CHECK_DELAY = 7000;
var history = [ "" ];
var history_pos = 0;
var clockOffset = 0;
var retrieved;
var timer;

var colors = [ "#FFFFFF", "#000000", "#000080", "#008000", "#FF0000", "#804000", "#8000FF", "#FF8000",
               "#FFFF00", "#00FF00", "#008080", "#00FFFF", "#0000FF", "#FF00FF", "#444444", "#808080" ];

$(function() {
    $(document).ajaxError(function(e, jqxhr, settings, exception) {
        $("#output").append("<div class='error'>"
            + "<span class='time'>[" + timeStamp(serverTime()) + "]</span> "
            + "Error! " + ( exception == undefined ? "" : exception + ": " ) + settings.data + "</div>");
        scrollEnd();
        clearTimeout(timer);
        timer = setTimeout(retrieve, CHECK_DELAY);
    });

    initResize();
    wireKeys();

    $("a.link").live("click", function(e) {
        var url = $(this).prop("href");
        open(url, url);
        return false;
    });

    $("#chatForm").submit(function() {
        input();
        return false;
    });
    
    $("#input").focus();

    $.post("", "join=", function(data, status, jqxhr) {
        var header = jqxhr.getResponseHeader("Date");
        if (header != undefined)
            clockOffset = Math.floor((new Date()).getTime() / 1000) - Math.floor((new Date(header)).getTime() / 1000);
        retrieved = serverTime();
        retrieve();
    });
});

function initResize() {
    var chat_width;
    $("#outDiv").resizable({
        "minHeight": 80,
        "minWidth": 86,
        "alsoResize": "#output",
        "start": function(event, ui) {
            chat_width = $("#outDiv").width();
        },
        "resize": function(event, ui) {
            $("#chatForm, #input").each(function() {
                $(this).width($(this).width() - (chat_width - $("#outDiv").width()));
            });
            chat_width = $("#outDiv").width();
        }
    });
}

function wireKeys() {
    var ctrl = false;
    $("#input").keyup(function(e) {
        if (e.which == 17)
            ctrl = false;
    }).keydown(function(e) {
        if (e.which == 17)
            ctrl = true;
        else if (ctrl) {
            // u:
            if (e.which == 85) {
                $("#input").replaceSelectedText("\x01");
                e.preventDefault();
            }
            // b:
            else if (e.which == 66) {
                $("#input").replaceSelectedText("\x02");
                e.preventDefault();
            }
            // r || i:
            else if (e.which == 82 || e.which == 73) {
                $("#input").replaceSelectedText("\x16");
                e.preventDefault();
            }
            // k:
            else if (e.which == 75) {
                $("#input").replaceSelectedText("\x03");
                e.preventDefault();
            }
            // o:
            else if (e.which == 79) {
                $("#input").replaceSelectedText("\x15");
                e.preventDefault();
            }
            ctrl = false;
        }
        // up:
        else if (e.which == 38) {
            e.preventDefault();
            if (history_pos - 1 >= 0 && history_pos - 1 < history.length)
                $("#input").val(history[--history_pos]);
        }
        // down:
        else if (e.which == 40) {
            e.preventDefault();
            if (history_pos + 1 < history.length)
                $("#input").val(history[++history_pos]);
        }
    });

    $(document).keydown(function(e) {
        $("#input").focus();
    });
}

function command(text) {
    var args = text.match(/\S+/g);

    if (args[0] == "/all") {
        retrieved = 0;
        retrieve();
    }
    else if (args[0] == "/who" || args[0] == "/w") {
        $.post("", "who=", function(data) {
            if (data && data.length > 0 && data[data.length - 1].time) {
                retrieved = data[data.length - 1].time;
                $("#output").append(formatRows(data));
                scrollEnd();
            }
        }, "json");
    }
    else if (args[0] == "/clear") {
        $("#output").empty();
    }
    else if (args[0] == "/nick") {
        if (args.length == 2) {
            $.post("", { "nick": args[1] }, function(data) {
                if (data) {
                    $("#output").append(formatLocalEvent("/nick failed, might be taken: " + args[1]));
                    scrollEnd();
                }
                else
                    retrieve();
            });
        }
        else if (args.length == 1) {
            $.post("", "nick=", function(data) {
                $("#output").append(formatLocalEvent("your nick is: " + data));
                scrollEnd();
            });
        }
    }
    else if (args[0] == "/colors" || args[0] == "/colours") {
        $("#output").append(formatLocalEvent("\x03000\x03011\x03022\x03033\x03044\x03055\x03066\x03077"
                + "\x03088\x03099\x031010\x031111\x031212\x031313\x031414\x031515"));
        scrollEnd();
    }
    else if (args[0] == "/admin") {
        $.post("", "auth=" + obfu(args[1]) + "&" + args[2] + "=" + ( args.length > 3 ? "&args=" + args.slice(3).join("+") : "" ));
    }
}

function obfu(text) {
    return text.replace(/[a-zA-Z]/g, function(c) {
        return String.fromCharCode((c <= "Z" ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26);
    });
}

function input() {
    var text = $("#input").val();

    for (var i = 0; i < history.length;) {
        if (history[i] == text || history[i] == "")
            history.splice(i, 1);
        else
            i++;
    }

    history.push(text);
    if (text != "")
        history.push("");

    history_pos = history.length - 1;

    text = $.trim(text);
    
    $("#input").val("");

    if (/^\/\w+/.test(text))
        command(text);
    else if (text != "") {
        clearTimeout(timer);
        $.post("", { "input": text }, function() {
            clearTimeout(timer);
            timer = setTimeout(retrieve, 1000);
        });
    }
}

function retrieve() {
    clearTimeout(timer);
    $.post("", { "retrieve": retrieved }, function(data) {
        if (data && data.length > 0 && data[data.length - 1].time) {
            retrieved = data[data.length - 1].time;
            $("#output").append(formatRows(data));
            scrollEnd();
        }
        clearTimeout(timer);
        timer = setTimeout(retrieve, CHECK_DELAY);
    }, "json");
}


function scrollEnd() {
    $("#output").scrollTop($("#output").prop("scrollHeight"));
}

function pad(number, length) {
    var str = String(number);
    while (str.length < length) {
        str = "0" + str;
    }
    return str;
}

function serverTime() {
    return (new Date()).getTime() / 1000 - clockOffset;
}

function timeStamp(secs) {
    var dt = new Date(Number(secs) * 1000);
    var hrs = dt.getHours();
    if (hrs > 12) hrs -= 12;
    if (hrs == 0) hrs = 12;
    var str = String(dt.getFullYear()) + "-"
            + pad(dt.getMonth() + 1, 2) + "-"
            + pad(dt.getDate(), 2) + " "
            + pad(hrs, 2) + ":"
            + pad(dt.getMinutes(), 2) + ":"
            + pad(dt.getSeconds(), 2) + ":"
            + ( dt.getHours() > 12 ? "pm" : "am" );
    return str;
}

function formatServerEvent(time, text) {
    return "<div class='server-event'>"
            + "<span class='time'>[" + timeStamp(time) + "]</span> * "
            + style(text) + "</div>";
}

function formatLocalEvent(text) {
    return "<div class='local-event'>"
            + "<span class='time'>[" + timeStamp(serverTime()) + "]</span> "
            + style(text) + "</div>";
}

function formatRows(data) {
    var html = "";
    for (var i = 0; i < data.length; i++) {
        if (data[i].event == "1") {
            html += formatServerEvent(data[i].time, data[i].text);
        }
        else if (data[i].event == "0") {
            html += "<div class='talk'>"
                 + "<span class='time'>[" + timeStamp(data[i].time) + "]</span> "
                 + "<span class='user'>&lt;" + data[i].nick + "&gt;</span> "
                 + style(data[i].text) + "</div>";
        }
    }
    return html;
}

function style(text) {
    return colorize(makeLinks(text));
}

function makeLinks(text) {
    var pat = /\bhttps?:\/\/\S+/g;
    var matches = text.match(pat);
    
    if (matches != null) {
        matches = $.unique(matches);
        for (var i = 0; i < matches.length; i++) {
            text = text.replace(new RegExp(matches[i], "g"),
                   "<a class='link' href='" + matches[i] + "'>" + matches[i] + "</a>");
        }
    }
    
    return text;
}

function colorize(text) {
    var color_pat = /\x03(\d{1,2})(?:,(\d{1,2}))?/;
    var fg, bg, style;
    var pos;
    var results;

    while ((results = color_pat.exec(text)) != null) {
        fg = results[1];
        bg = results[2];
        if (fg > -1 && fg < colors.length)
            style = "color: " + colors[Number(fg)] + ";";
        if (bg != undefined && bg > -1 && bg < colors.length)
            style += " background-color: " + colors[Number(bg)] + ";";
        text = openSpan(text, color_pat, style);
    }
    while ((pos = text.indexOf("\x01")) != -1)
        text = openSpan(text, /\x01/, "text-decoration: underline;");
    while ((pos = text.indexOf("\x02")) != -1)
        text = openSpan(text, /\x02/, "font-weight: bold;");
    while ((pos = text.indexOf("\x16")) != -1)
        text = openSpan(text, /\x16/, "font-style: italic;");

    text = closeSpans(text);
    text = text.replace(/\x15/g, "");
    text = text.replace(/\x03\d{1,2}(?:,\d{1,2})?/g, "");
    text = text.replace(/\x03/g, "");
    
    return text;
}

function openSpan(text, regex, style) {
    return text.replace(regex, "<span style='" + style + "'>");
}

function closeSpans(text) {
    var span_pos = -5;
    var off_pos;

    while ((span_pos = text.indexOf("<span", span_pos + 5)) != -1) {
        off_pos = text.indexOf("\x15", span_pos + 5);
        if (off_pos != -1)
            text = text.substr(0, off_pos) + "</span>" + text.substr(off_pos);
        else
            text += "</span>";
    }
    return text;
}

/* ]]> */
</script>

</head>

<body>
    <div id="bigDiv">
        <div id="chatDiv">
            <div id="outDiv">
                <div id="output" class="chat"></div>
            </div>
            <form id="chatForm" action="#">
                <div>
                    <input id="input" class="chat" type="text" name="input" size="256" />
                    <input id="submit" type="submit" value="&crarr;" />
                </div>
            </form>
        </div>
    </div>
</body>

</html>