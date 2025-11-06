<?php
require_once 'includes/functions.php';

iniciarSessao();
session_destroy();
header("Location: login.php");
exit;
