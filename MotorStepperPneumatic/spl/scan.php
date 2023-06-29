<?php
session_start();
$marker=$_SESSION['marker'];
if($_GET['w']){
$v=$_GET['w'];
$w=$v-68;
$h=intval($v*3/4)-36;
$_SESSION['w']=$w;
$_SESSION['h']=$h;
}else{
$w=$_SESSION['w'];
$h=$_SESSION['h'];
}
if (!$_SESSION['w']){
header("Location: index.php");
die();
}
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
<script language="javascript" type="text/javascript">
if(navigator.userAgent.match(/Android/i)){
    window.scrollTo(0,1);
}

// Find all iframes
var $iframes = $( "iframe" );
 
// Find &#x26; save the aspect ratio for all iframes
$iframes.each(function () {
  $( this ).data( "ratio", this.height / this.width )
    // Remove the hardcoded width &#x26; height attributes
    .removeAttr( "width" )
    .removeAttr( "height" );
});
 
// Resize the iframes when the window is resized
$( window ).resize( function () {
  $iframes.each( function() {
    // Get the parent container&#x27;s width
    var width = $( this ).parent().width();
    $( this ).width( width )
      .height( width * $( this ).data( "ratio" ) );
  });
// Resize to fix all iframes on page load.
}).resize();
</script>
<script src="js/screenfull.js"></script>
<script>
		$(function () {
			$('#supported').text('Supported/allowed: ' + !!screenfull.enabled);
			if (!screenfull.enabled) {
				return false;
			}
			$('#request').click(function () {
				screenfull.request($('#container')[0]);
				// Does not require jQuery. Can be used like this too:
				// screenfull.request(document.getElementById('container'));
			});
			$('#exit').click(function () {
				screenfull.exit();
			});
			$('#toggle').click(function () {
				screenfull.toggle($('#container')[0]);
			});
			$('#request2').click(function () {
				screenfull.request();
			});
			$('#demo-img').click(function () {
				screenfull.toggle(this);
			});
			function fullscreenchange() {
				var elem = screenfull.element;
				$('#status').text('Is fullscreen: ' + screenfull.isFullscreen);
				if (elem) {
					$('#element').text('Element: ' + elem.localName + (elem.id ? '#' + elem.id : ''));
				}
				if (!screenfull.isFullscreen) {
					$('#external-iframe').remove();
					document.body.style.overflow = 'auto';
				}
			}
			screenfull.on('change', fullscreenchange);
			// Set the initial values
			fullscreenchange();
		});
onload 


		</script>
<div data-role="page" id="testpage" data-theme="b">
	<div data-role="header">
	

		<div data-role="navbar">
			<ul>
				<li><a href="#" class="ui-btn-active ui-state-persist" id="toggle">Scan</a></li>
				<li><a href="library.php">Library</a></li>
				
			</ul>
		</div>
	</div>
 <div>
	<div data-role="content">
	

  <iframe width="100%" height="<?=$h+220?>" src="scanf.php" frameborder="0" allowfullscreen></iframe>


</div>
</div>
</div>
</body> 

</html>