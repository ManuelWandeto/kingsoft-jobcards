<?php
require_once('../utils/redirect.php');

session_start();
session_unset();
session_destroy();
redirect('../Login/brandlogin.php');