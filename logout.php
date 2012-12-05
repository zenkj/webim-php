<?php
require('./util.php');
session_start();

setcookie(session_name(), '');
session_destroy();
redirect('./login.php');
