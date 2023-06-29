<?php 
session_start();

if($_GET['id']){
$_SESSION['marker']=$_GET['id'];
}
?>
