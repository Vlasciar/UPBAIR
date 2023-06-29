var THREEx = THREEx || {}

THREEx.JsArucoMarker = function(){
	var _this = this

	this.debugEnabled = false
	this.videoScaleDown = 2
	this.modelSize = 15.0 // millimeter

	var canvasElement = document.createElement('canvas')
	var context = canvasElement.getContext("2d");

	// create debug element
	var debugElement	= document.createElement('div')
	debugElement.appendChild(canvasElement)
	debugElement.style.position = 'absolute'
	debugElement.style.top = '0px'
	debugElement.style.left = '0px'
	debugElement.style.opacity = 0.2
	
	var debugInfoElement	= document.createElement('div')
	debugElement.appendChild( debugInfoElement )
	debugInfoElement.classList.add('info')
	debugInfoElement.innerHTML = ''
		+ '<div>canvasSize: <span class="canvasSize">n/a</span></div>'
		+ '<div>videoScaleDown: <span class="videoScaleDown">n/a</span></div>'
		+ '<div>videoSize: <span class="videoSize">n/a</span></div>'

	this.detectMarkers	= function(videoElement){

		if( videoElement instanceof HTMLVideoElement ){

			if (videoElement.readyState !== videoElement.HAVE_ENOUGH_DATA){
				return []
			}

			canvasElement.width = videoElement.videoWidth/_this.videoScaleDown
			canvasElement.height = videoElement.videoHeight/_this.videoScaleDown

		}else if( videoElement instanceof HTMLImageElement ){
			if( videoElement.naturalWidth === 0 ){
				return []
			}

			canvasElement.width = videoElement.naturalWidth/_this.videoScaleDown
			canvasElement.height = videoElement.naturalHeight/_this.videoScaleDown
		}else console.assert(false)


		context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
		var imageData = context.getImageData(0, 0, canvasElement.width, canvasElement.height);

	
		var detector = new AR.Detector();
		var markers = detector.detect(imageData);


		var debugAttached = debugElement.parentNode !== null ? true : false

		if( this.debugEnabled === true && debugAttached === false ){
			document.body.appendChild(debugElement)
		}

		if( this.debugEnabled === false && debugAttached === true ){
			debugElement.parentNode.removeChild( debugElement )
		}

		if( this.debugEnabled === true ){
			debugElement.querySelector('.info .videoScaleDown').innerHTML = this.videoScaleDown
			if( videoElement.videoWidth !== undefined ){
				debugElement.querySelector('.info .videoSize').innerHTML = videoElement.videoWidth + 'x' + videoElement.videoHeight
			}else{
				debugElement.querySelector('.info .videoSize').innerHTML = videoElement.naturalWidth + 'x' + videoElement.naturalHeight				
			}
			debugElement.querySelector('.info .canvasSize').innerHTML = canvasElement.width + 'x' + canvasElement.height
			drawDebug(markers, canvasElement)
		}

		return markers
	}


	this.markerToObject3D = function(marker, object3d){
		
		var corners = []
		for (var i = 0; i < marker.corners.length; ++ i){
			corners.push({
				x : marker.corners[i].x - (canvasElement.width / 2),
				y : (canvasElement.height / 2) - marker.corners[i].y,
			})
		}
	
		var posit = new POS.Posit(this.modelSize, canvasElement.width);
		var pose = posit.pose(corners);
		
		if( pose === null )	return;

		var rotation = pose.bestRotation
		var translation = pose.bestTranslation

		object3d.scale.x = this.modelSize;
		object3d.scale.y = this.modelSize;
		object3d.scale.z = this.modelSize;

		object3d.rotation.x = -Math.asin(-rotation[1][2]);
		object3d.rotation.y = -Math.atan2(rotation[0][2], rotation[2][2]);
		object3d.rotation.z =  Math.atan2(rotation[1][0], rotation[1][1]);

		object3d.position.x =  translation[0];
		object3d.position.y =  translation[1];
		object3d.position.z = -translation[2];
	}

	return

	function drawDebug(markers, canvasElement){
		var context = canvasElement.getContext("2d");
		context.lineWidth = 3;

		for (var i = 0; i < markers.length; ++ i){
			var marker = markers[i]
			var corners = marker.corners;

			context.strokeStyle = "red";
			context.beginPath();

			for (var j = 0; j < corners.length; ++ j){
				var corner = corners[j];
				context.moveTo(corner.x, corner.y);
				corner = corners[(j + 1) % corners.length];
				context.lineTo(corner.x, corner.y);
			}

			context.stroke();
			context.closePath();

			context.strokeStyle = "green";
			context.strokeRect(corners[0].x - 2, corners[0].y - 2, 4, 4);
			// console.log('marker', marker.id)

			context.fillStyle = "blue";
			context.font = "bold 10px Arial";
			context.fillText("id: "+marker.id, corners[0].x, corners[0].y);
		}
	};
}
