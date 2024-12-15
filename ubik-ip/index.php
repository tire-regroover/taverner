<?php require("../../log.php"); ?>
<?php
$ip = '99.237.113.34';
$pass = 'Inthemorning23!';
if ($_POST['hello'] == $pass) {
  echo $ip;
}
else {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>hello.</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
</head>

<body>
<div>
    <form action="." method="post">
        <input name="hello" type="password"/>
    </form>
</div>
</body>
</html>
<?php
}
?>