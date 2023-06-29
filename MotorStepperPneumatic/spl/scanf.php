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
<!DOCTYPE html>
<!-- include three.js -->
<script src='three.js/build/three.js'></script>

<!-- include js-aruco -->
<script src='js-aruco/svd.js'></script>
<script src='js-aruco/posit1-patched.js'></script>
<script src='js-aruco/cv.js'></script>
<script src='js-aruco/aruco.js'></script>
<script src='jquery.min.js'></script>
<script src='threex/threex.webcamgrabbing.js'></script>
<script src='threex/threex.imagegrabbing.js'></script>
<script src='threex/threex.videograbbing.js'></script>
<script src='threex/threex.jsarucomarker.js'></script>
<script src='features/js/ui.badgesprite.js'></script>
<script src='features/js/badgeInfos.js'></script>
<body style='margin: 0px; overflow: hidden; text-align:center;'>
<div id="new_marker" style="display: none;" ></div>
<div id="latestData" style="display: none;"> </div> 
<div id="container">
<script>
	var renderer	= new THREE.WebGLRenderer({
		antialias	: true,
		alpha		: true,
	});
	renderer.setSize( window.innerWidth, window.innerHeight );
	document.body.appendChild( renderer.domElement );
	var onRenderFcts = [];
	var scene = new THREE.Scene()
	var camera	= new THREE.PerspectiveCamera(30, window.innerWidth / window.innerHeight, 0.01, 1000);
	camera.position.z = 2;
	var badgeSprite = new UI.BadgeSprite();
	scene.add(badgeSprite)
	window.addEventListener('resize', function(){
		renderer.setSize( window.innerWidth, window.innerHeight )
		camera.aspect	= window.innerWidth / window.innerHeight
		camera.updateProjectionMatrix()
	}, false)

	scene.visible	= false

	onRenderFcts.push(function(){
		renderer.render( scene, camera );
	})

	var previousTime = performance.now()
	requestAnimationFrame(function animate(now){

		requestAnimationFrame( animate );

		onRenderFcts.forEach(function(onRenderFct){
			onRenderFct(now, now - previousTime)
		})

		previousTime	= now
	})

	var jsArucoMarker	= new THREEx.JsArucoMarker()

	if( false ){
		var videoGrabbing = new THREEx.VideoGrabbing()
		jsArucoMarker.videoScaleDown = 10
	}else if( true ){
		var videoGrabbing = new THREEx.WebcamGrabbing()
		jsArucoMarker.videoScaleDown = 2
	}else if( true ){
		var videoGrabbing = new THREEx.ImageGrabbing()
		jsArucoMarker.videoScaleDown = 10
	}else console.assert(false)

        document.body.appendChild(videoGrabbing.domElement)


	var previousMarkerId = null;

	onRenderFcts.push(function(){
		var domElement	= videoGrabbing.domElement
		var markers	= jsArucoMarker.detectMarkers(domElement)
		var object3d	= scene

		object3d.visible = false

		markers.forEach(function(marker){
			var badgeInfo = null
			badgeInfos.forEach(function(item){
				if( item.markerId !== marker.id )	return
				badgeInfo = item;
				
			})

			if( badgeInfo === null ){
				console.log('found marker', marker.id, 'but no matching badge found')
				return
			}

			if( marker.id !== previousMarkerId ){
			    	badgeSprite.draw(badgeInfo);
				previousMarkerId = marker.id;
				 $("#new_marker").text(marker.id);
			}

			jsArucoMarker.markerToObject3D(marker, object3d)

			object3d.visible = true;
		})
	});
	
</script>

<script>
var previousValue = '';
    $(document).ready(function(){
        setInterval(function() {
        var x=$("#new_marker").text();
        
            $("#latestData").load("session.php?id="+x);
            
        }, 1000);
    });

</script>

</body>
