<?php

// MAKE SURE ERRORS ARE SHOWN... MIGHT WANT TO TURN THIS OFF ON A PUBLIC SERVER
error_reporting(E_ALL);
ini_set('display_errors', 'On');
// --

session_start();

$db = new PDO('mysql:host=mysql;dbname=auth', 'user', 'pass');
