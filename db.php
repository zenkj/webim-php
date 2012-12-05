<?php
$db = mysql_connect("mysql.sql30.eznowdata.com", "sq_zenkju", "ju004589");
if (!$db) {
    die("connect to database failed");
}
mysql_select_db("sq_zenkju");

