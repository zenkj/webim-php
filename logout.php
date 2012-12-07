<?php
require('./util.php');
session_start();

if (!isset($_SESSION['userid'])) {
    redirect("./login.php");
}

$userid = $_SESSION['userid'];

require('./db.php');
mysql_query("update webim_users set last_access_time = 0 where id = $userid");

setcookie(session_name(), '');
session_destroy();
redirect('./login.php');
