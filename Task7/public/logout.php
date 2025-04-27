<?php

session_start();

header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

session_destroy();
header('Location: index.php');
exit();
