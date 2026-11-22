<?php
require_once 'db_connect.php';

$_SESSION = [];
session_destroy();
redirect("index.php");
