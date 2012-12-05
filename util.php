<?php
function redirect($url) {
    header("HTTP/1.1 302 Moved Temporarily");
    header("Location: $url");
    exit(0);
}
