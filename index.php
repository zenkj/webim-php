<?php
require('./util.php');
session_start();
if (!isset($_SESSION['userid'])) {
    redirect("./login.php");
}

$userid = $_SESSION['userid'];

if (!isset($_SESSION['last_read_msg_id'])) {
    $_SESSION['last_read_msg_id'] = 0;
}
$last_read_msg_id = $_SESSION['last_read_msg_id'];

function logout_user() {
    setcookie(session_name(), '');
    session_destroy();
}

$currenttime = time();

function check_frequency() {
    global $currenttime;
    if (!isset($_SESSION['last_access_time'])) {
        $_SESSION['last_access_time'] = $currenttime;
    }
    $last_access_time = $_SESSION['last_access_time'];

    if (!isset($_SESSION['load_check_begin_time'])) {
        $_SESSION['load_check_begin_time'] = $currenttime;
    }
    $load_check_begin_time = $_SESSION['load_check_begin_time'];

    if (!isset($_SESSION['load_check_count'])) {
        $_SESSION['load_check_count'] = 0;
    }
    $load_check_count = $_SESSION['load_check_count'];

    // session timeout if no action for 1 minute.
    if ($currenttime - $last_access_time > 60) {
        logout_user();
        redirect('./login.php');
    }

    // overload if request number > 100 in 10 seconds.
    if ($load_check_count > 100) {
        logout_user();
        die('too much requests in a short time, please relogin.');
    }

    // update time and count
    if ($currenttime - $load_check_begin_time > 10) {
        $_SESSION['load_check_begin_time'] = $currenttime;
        $_SESSION['load_check_count'] = 0;
    } else {
        $_SESSION['load_check_count'] = $load_check_count + 1;
    }
    $_SESSION['last_access_time'] = $currenttime;
}

check_frequency();


require('./db.php');
mysql_query("update webim_users set last_access_time = $currenttime where id = $userid");
$result = mysql_query("select * from webim_users where id = $userid");
if (!$result || mysql_num_rows($result) != 1) {
    setcookie(session_name(), '');
    session_destroy();
    redirect("./login.php");
}

$array = mysql_fetch_array($result);
$username = $array['name'];

function get_info() {
    global $userid, $last_read_msg_id, $currenttime;
    $ret = array('msgcount' => 0, 'friendstatus' => 'inactive');
    $sql = "select u.last_access_time from webim_friends as f, webim_users as u where f.userid = $userid and u.id = f.friendid";
    $result = mysql_query($sql) or die("invalid get last_access_time");
    while ($item = mysql_fetch_array($result, MYSQL_NUM)) {
        if ($currenttime - $item[0] < 60) {
            $ret['friendstatus'] = 'active';
            break;
        }
    }

    $sql = "select * from webim_messages_$userid where id > $last_read_msg_id";
    $result = mysql_query($sql) or die("invalid get messages");
    if (mysql_num_rows($result) == 0) {
        echo json_encode($ret);
        return;
    }
    $ret['msgcount'] = mysql_num_rows($result);
    while ($msg = mysql_fetch_array($result, MYSQL_ASSOC)) {
        if ($msg['id'] > $last_read_msg_id)
            $last_read_msg_id = $msg['id'];
        $msgs[] = $msg;
    }
    $_SESSION['last_read_msg_id'] = $last_read_msg_id;
    $ret['msgs'] = $msgs;
    echo json_encode($ret);
}

if (isset($_POST['request'])) {
    $cmd = $_POST['request'];

    if ($cmd == 'get_friend') {
        $result = mysql_query("select * from webim_friends where userid = $userid") or die("invalid userid1");
        $array = mysql_fetch_array($result);
        $fid = $array['friendid'];
        $result = mysql_query("select * from webim_users where id = $fid") or die("invalid userid2");
        $array = mysql_fetch_array($result, MYSQL_ASSOC);
        $ret = array('userid' => $array['id'], 'username' => $array['name']);
        echo json_encode($ret);
        exit(0);
    } else if ($cmd == 'get_all_info') {
        $last_read_msg_id = 0;
        get_info();
        exit(0);
    } else if ($cmd == 'get_info') {
        get_info();
        exit(0);
    } else if ($cmd == 'put_message') {
        $fromid = $_POST['fromid'];
        $toid = $_POST['toid'];
        $content = $_POST['content'];
        $time = $_POST['time'];
        mysql_query("insert into webim_messages_$fromid(fromid, toid, content, time) values($fromid, $toid, '$content', '$time')");
        mysql_query("insert into webim_messages_$toid(fromid, toid, content, time) values($fromid, $toid, '$content', '$time')");
        get_info();
        exit(0);
    } else if ($cmd == 'clear_messages') {
        get_info();
        mysql_query("delete from webim_messages_$userid where 1") or die('delete failed: ' . mysql_error());
        exit(0);
    }
}

?>

<html>
<head>
<title>webim - <?php echo $username ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script type="text/javascript" src="jquery-1.8.3.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    var currentfid = null;
    var currentfname = null;
    var userid = <?php echo $userid; ?>;
    var username = <?php echo "'$username'"; ?>;

    $('#my_status').text(username);

    function activeFriend(active) {
        if (active) {
            $('#friend_status').addClass('active').removeClass('inactive');
        } else {
            $('#friend_status').addClass('inactive').removeClass('active');
        }
    }

    function activeMe(active) {
        if (active) {
            $('#my_status').addClass('active').removeClass('inactive');
        } else {
            $('#my_status').addClass('inactive').removeClass('active');
        }
    }

    function activeAll(active) {
        activeMe(active);
        activeFriend(active);
    }

    function request(data, done, fail) {
        var xhr = $.ajax({url: './index.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            cache: false});
        if (done) xhr.done(done);
        if (fail) xhr.fail(fail);
    }

    request({request: 'get_friend'},
            function(data) {
                currentfid = data.userid;
                currentfname = data.username;
                $('#friend_status').text(currentfname);
            },
            function(xhr, err) {
                alert('get friend failed, please relogin');
                activeAll(false);
                window.location.assign('./login.php');
            });

    function userName(uid) {
        if (uid == userid)
            return username;
        if (uid == currentfid)
            return currentfname;
        return uid;
    }

    function updateDisplay(info) {
        if (!info) return;
        if (info.msgs) {
            for (var i in info.msgs) {
                var msg = info.msgs[i];
                var isme = msg.fromid == userid;
                var tobj = new Date(msg.time);
                var t = '' + tobj.getHours() + ':' + tobj.getMinutes() + ':' + tobj.getSeconds();
                var d = '' + tobj.getFullYear() + '-' + (tobj.getMonth()+1) + '-' + tobj.getDate();
                var userclass = isme ? 'isme' : 'isfriend';
                var userstyle = isme ? 'text-align: right;' : 'text-align: left;';
                var str = '<div class="' + userclass + '">'
                    + '<p><span class="username">' + userName(msg.fromid) + '</span>&nbsp;'
                    + '<span class="time">' + t + '</span>&nbsp;'
                    + '<span class="date">' + d + '</span></p>'
                    + '<p class="content">' + msg.content + '</p></div>';
                $(str).appendTo($('#backlog'));
            }
        }

        $('#backlog_container').scrollTop($('#backlog').height());

        activeMe(true);
        activeFriend(info.friendstatus == 'active');
    }

    function clearInputBox() {
        $('#msg_input_box').val('');
        $('#msg_input_box').focus();
    }

    function sendMsg() {
        var data = $.trim($('#msg_input_box').val());
        if (data && data.length > 0) {
            request({request: 'put_message', fromid: userid, toid: currentfid, content: data, time: new Date().toString()},
                    function(result) { updateDisplay(result); },
                    function(xhr, err) {
                        alert("send message failed, please relogin");
                        activeAll(false);
                        window.location.assign('./login.php');
                    });
        }
        clearInputBox();
    }

    $('#msg_send_button').click(function() {
        sendMsg();
    });

    $('#msg_clear_button').click(function() {
        $('#backlog').empty();
        request({request: 'clear_messages'},
                function(result) {updateDisplay(result);},
                function(xhr, err) {
                    alert("clear messages failed, please relogin: " + err);
                    activeAll(false);
                    window.location.assign('./login.php');
                });
    });

    $('#msg_refresh_button').click(function() {
        request({request: 'get_info'},
            function(data) { updateDisplay(data); });
    });

    $('#msg_input_box').keydown(function(e) {
        switch(e.which) {
        case 13: //enter
            sendMsg();
            break;
        case 27: //escape
            clearInputBox();
            break;
        }
    });

    request({request: 'get_all_info'},
        function(data) { updateDisplay(data); });

    var timerID;
    function daemon() {
        request({request: 'get_info'},
                function(data) {
                    updateDisplay(data);
                    if (data.friendstatus == 'active')
                        timerID = setTimeout(daemon, 3000);
                    else
                        timerID = setTimeout(daemon, 15000);
                },
                function(xhr, err) {
                    alert("update information failed, please relogin");
                    activeAll(false);
                    window.location.assign('./login.php');
                });
    }

    daemon();
});
</script>
<style type="text/css">
#logout {
    float: right;
    text-align: center;
    margin: 0;
    padding: 0;
}
#friend_status {
    width: 40px;
    text-align: center;
    float: right;
    margin: 0 10 0 0;
    padding: 0;
}
#my_status {
    width: 40px;
    text-align: center;
    float: right;
    margin: 0 3 0 0;
    padding: 0;
}

#header hr {
    clear: both;
}
.active {
    background-color: green;
}

.inactive {
    background-color: #6D6C67;
}

#backlog_container {
    height: 80%;
    position: relative;
    overflow: scroll;
}
#backlog {
    width: 100%;
    position: absolute;
    top: 0px;
    left: 0px;
}
#backlog .isme {
    text-align: right;
    background-color: #FFE;
}
#backlog .isfriend {
    text-align: left;
    background-color: #EFF;
}
#backlog .username {
    font-weight: bold;
    color: #A32918;
}

#backlog .time {
    /*text-decoration: underline;*/
}

#backlog .date {
    color: #7E7E79;
}

#backlog .content {
    color: #2F2F2F;
    font-weight: bold;
}
</style>
</head>
<body>
<div id="header">
<a id="logout" href="./logout.php">logout</a>
<p id="friend_status" class="inactive"></p>
<p id="my_status" class="active"></p>
<hr />
</div>
<div id="backlog_container">
<div id="backlog"></div>
</div>
<input type="text" id="msg_input_box" />
<input type="button" id="msg_send_button" value="send" />
<input type="button" id="msg_clear_button" value="clear"/>
<input type="button" id="msg_refresh_button" value="refresh"/>
</body>
</html>
