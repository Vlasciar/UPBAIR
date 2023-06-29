<?php 
session_start();
$db = new PDO('mysql:host=localhost;dbname=ar', 'root', 'E=mc^2');
if (!$db) {
   die('Database not available: ' . mysql_error());
   exit;
}
$result = $db->query("SET NAMES 'utf8'");
date_default_timezone_set("Europe/Bucharest");


$idc=$_SESSION['marker'];

$selmark=$db->prepare("SELECT * FROM markers WHERE marker_NO = ? limit 1");
$selmark->execute(array($idc));
$sg=$selmark->fetch(PDO::FETCH_ASSOC);
if($sg['componenta']<>''){                       
echo "<a href='library.php' class='ui-btn ui-corner-all ui-shadow ui-icon-gear ui-btn-icon-left ui-btn-active'>".$sg['componenta']."</a>";

}
?>
