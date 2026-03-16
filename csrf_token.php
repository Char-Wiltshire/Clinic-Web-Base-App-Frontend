<?php

require_once 'C:/xampp/htdocs/emr_webapp/config.php';
jsonResponse(true, 'OK', ['csrf_token'=> getCsrfToken()]);