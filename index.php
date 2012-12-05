<?php
require('./util.php');
session_start();
if (!isset($_SESSION['userid'])) {
    redirect("/webim-php/login.php");
}
require('./db.php');
$result = mysql_query("select * from webim_users where id = " . $_SESSION['userid']);
if (!$result || mysql_num_rows($result) != 1) {
    setcookie(session_name(), '');
    session_destroy();
    redirect("/webim-php/login.php");
}

$array = mysql_fetch_array($result);
$username = $array['name'];
$nextmsgid = $array['next_msg_id'];
?>

<html>
<head>
<title>webim - <?php echo $username ?></title>
<style type="text/css">
#header {
    text-align: right;
}
#backlog_container {
    height: 80%;
    position: relative;
}
#backlog {
    width: 100%;
    position: absolute;
    top: 0px;
    left: 0px;
}
</style>
</head>
<body>
<div id="header">
<a href="/webim-php/logout.php">logout</a>
</div>
<div id="backlog_container">
<div id="backlog"></div>
</div>
<input type="text" id="msg_input_box" />
<input type="button" id="msg_send_button" value="send" />
<input type="button" id="msg_clear_button" value="clear"/>
</body>
</html>
