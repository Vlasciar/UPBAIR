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
	
<style>
.video-responsive{
    overflow:hidden;
    padding-bottom:56.25%;
    position:relative;
    height:0;
}
.video-responsive iframe{
    left:0;
    top:0;
    height:100%;
    width:100%;
    position:absolute;
}
</style>
</head>
<body >
<script language="javascript" type="text/javascript">
if(navigator.userAgent.match(/Android/i)){
    window.scrollTo(0,1);
}
</script>
<div data-role="page" id="testpage" data-theme="b">
	<div data-role="header">
	

		<div data-role="navbar">
			<ul>
				<li><a href="scan.php">Scan</a></li>
				<li><a href="#" class="ui-btn-active ui-state-persist">Library</a></li>
				<li><a href="maintenance.php">Info</a></li>
				
			</ul>
		</div>
	</div><!-- /header -->
 <div>
	<div class="ui-content" role="main">
	<div id="ui-bar-test" class="ui-bar ui-bar-a ui-corner-all" style="margin-bottom:1em;">
<?php
if ($marker==1){
echo '<a href="upload/files/BevelGear.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Bevel Gear / EGSK</a>';

  } else if ($marker==2){
echo '<a href="upload/files/Cylinder.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Cylinders Series 42</a>';

  }else if ($marker==3){
echo '<a href="upload/files/FlexCoupling.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Flexible couplings </a>';

  }else if ($marker==4){
echo '<a href="upload/files/Moeller1PSU.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Moeller1 EASY400-POW </a>';

  }else if ($marker==5){
echo '<a href="upload/files/Moeller2PSU.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Moeller2 Easy 825-DC-TCX </a>';

  }else if ($marker==6){
echo '<a href="upload/files/Moeller3PSU.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Moeller3 EASY621-DC-TC</a>';

  }else if ($marker==7){
echo '<a href="upload/files/MotorBPSMX.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Stepping motor BPS-1620-MX </a>';

  }else if ($marker==8){
echo '<a href="upload/files/Pressure.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Pressure M3A-ABS 40 </a>';

  }else if ($marker==9){
echo '<a href="upload/files/Regulator.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Regulator M008-R00-VS </a>';

  }else if ($marker==10){
echo '<a href="upload/files/ValveMatrix.pdf" class="ui-btn ui-corner-all ui-shadow ui-icon-bullets ui-btn-icon-left ui-btn-active" target="_blank">Solenoid Valve Matrix 768 Vacuum </a>';

  }
?>
<br/><div class="video-responsive"><iframe width="560" height="315" src="https://www.youtube.com/embed/_Q6kR3MzREE" frameborder="0" gesture="media" allow="encrypted-media" allowfullscreen></iframe></div>
</div>
</div>
</div>
</div>

<script type="text/javascript">
if(location.search.indexOf('reloaded=yes') < 0){
	var hash = window.location.hash;
	var loc = window.location.href.replace(hash, '');
	loc += (loc.indexOf('?') < 0? '?' : '&') + 'reloaded=yes';
	setTimeout(function(){window.location.href = loc + hash;}, 5000000);
}
</script>
</body> 
</html>