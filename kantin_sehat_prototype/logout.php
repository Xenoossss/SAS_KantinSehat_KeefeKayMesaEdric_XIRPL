<?php
require_once 'config/functions.php';

session_destroy();
redirect('index.php');
?>