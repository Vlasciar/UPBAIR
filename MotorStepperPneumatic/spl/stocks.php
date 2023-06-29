<?php
session_start();
$marker=$_SESSION['marker'];
?>
<html>

<head>
  <title>AR-M</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/themes/default/jquery.mobile.icons.min.css">
	<link rel="stylesheet" href="css/themes/default/jquery.mobile-1.4.5.min.css">
	<link rel="stylesheet" href="css/themes/default/jquery.mobile.structure-1.4.5.min.css">
	<link rel="shortcut icon" href="../favicon.ico">
	<script src="js/jquery.js"></script>
	<script src="js/jquery.mobile-1.4.5.min.js"></script>
</head>

<body >
<div data-role="page" id="testpage" data-theme="b">
	<div data-role="header">
	

		<div data-role="navbar">
			<ul>
				<li><a href="scan.php">Scan</a></li>
				<li><a href="library.php">Library</a></li>
				<li><a href="#"  class="ui-btn-active ui-state-persist">Stocks</a></li>
				<li><a href="maintenance.php">Maintenance</a></li>
				
			</ul>
		</div>
	</div>
  <div>
	<div class="ui-content" role="main">
	<div id="ui-bar-test" class="ui-bar ui-bar-a ui-corner-all" style="margin-bottom:1em;">
<?php
if ($marker==1){
echo '<img src="https://transmissiongate.com/ar/webar/upload/files//stoc.png" alt="inventory">';

}
?>  
</div>
</div>
</div>
</div>
<script type="text/javascript">
if(location.search.indexOf('reloaded=yes') < 0){
	var hash = window.location.hash;
	var loc = window.location.href.replace(hash, '');
	loc += (loc.indexOf('?') < 0? '?' : '&') + 'reloaded=yes';
	setTimeout(function(){window.location.href = loc + hash;}, 5000);
}
</script>
</body> 
</html>