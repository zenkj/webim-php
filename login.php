<?php
require('./util.php');
session_start();

if (isset($_SESSION['userid'])) {
    redirect("/webim-php/index.php");
}

if (isset($_POST['userid']) && isset($_POST['password'])) {
    require('./db.php');
    $result = mysql_query("select * from webim_users where id = " . $_POST['userid']);
    if (!$result || mysql_num_rows($result) != 1) {
        $notification = "invalid userid or password";
    } else {
        $array = mysql_fetch_array($result);
        if ($array['password'] != $_POST['password']) {
            $notification = "invalid userid or password";
        } else {
            $_SESSION['userid'] = $_POST['userid'];
            redirect("/webim-php/index.php");
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>zenkim</title>
</head>
<body>
    <?php
        if ($notification) {
          echo "<p>" . $notification . "</p>";
        }
    ?>
    <form action="/webim-php/login.php" method="POST">
    <table>
        <tr><td>id</td><td><input type="text" name="userid" /></td></tr>
        <tr><td>password</td><td><input type="password" name="password" /></td></tr>
        <tr><td colspan="2"><input type="submit" value="login" /></td></tr>
    </table>
    </form>
</body>
</html>
