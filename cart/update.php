<?php
session_start();

if(!isset($_POST['key']) || !isset($_POST['qty'])) {
    die("Invalid request");
}

?>