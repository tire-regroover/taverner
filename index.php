<?php require("../log.php"); ?>
<?php echo '<?xml version="1.0" encoding="utf-8"?>'."\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title></title>
<script type="text/javascript" src="jquery-1.5.1.min.js"></script>
<style type="text/css">
/* <![CDATA[ */

html, body { padding: 0; margin: 0; }
body { background-color: #051015; color: white; font-family: monospace; font-size: 8pt; }
a, li { color: #778899; }
a { text-decoration: none; font-weight: bold; }
a:hover { text-decoration: underline; color: white; }

#bigDiv { margin: 50px 0 0 0;  padding: 0; text-align: center; }
#linkDiv { margin: 0 auto; padding: 0; width: 130px; text-align: left; }
.videoDiv { margin: 0; padding: 0; }
.video { margin: 20px 0 10px 0; padding: 0; width: 450px; height: 366px; border: 2px solid white; }

/* ]]> */
</style>

<script type="text/javascript">
/* <![CDATA[ */

$(function() {
    $(".popup").click(function(e) {
        var url = $(e.target).attr("href");
        open(url, url);
        return false;
    });
});

/* ]]> */
</script>

</head>

<body>
    <div id="bigDiv">
        <div id="linkDiv">
            <ul>
                <li><a href="/sudoku">sudoku</a></li>
                <li><a href="/conway">conway</a></li>
                <li><a href="/fish">fish</a></li>
            </ul>
            <ul>
<!--                <li><a class="popup" href="http://tenderbidtrade.com">tenderbidtrade</a></li>-->
            </ul>
            <ul>
                <li><a class="popup" href="http://www.clementine-player.org">clementine</a></li>
            </ul>
        </div>
        <div class="videoDiv">
            <div>
<!--
                <object class="video" type="application/x-shockwave-flash" data="http://www.youtube.com/v/MhwRu5jlIqs?fs=1">
                    <param name="movie" value="http://www.youtube.com/v/MhwRu5jlIqs?fs=1" />
                </object>
-->
            </div>
        </div>
    </div>
</body>

</html>