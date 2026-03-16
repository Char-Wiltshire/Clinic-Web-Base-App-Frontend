<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
session_unset();
session_destroy();
header('Location: ../pages/login.html');
exit;   