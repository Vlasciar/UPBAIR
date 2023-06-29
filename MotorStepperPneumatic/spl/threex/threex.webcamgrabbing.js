function readCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
  }
  return null;
}

console.log(readCookie("vid"));
var videoSource=readCookie("vid");

var THREEx = THREEx || {}

navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
window.URL = window.URL || window.webkitURL;

THREEx.WebcamGrabbing = function(){

        var domElement        = document.createElement('video')
        domElement.setAttribute('autoplay', true)

	domElement.style.zIndex = -1;
        domElement.style.position = 'absolute'

	 domElement.style.top = '50%'
	 domElement.style.left = '50%'
	domElement.style.marginRight = '50%'
	domElement.style.transform = 'translate(-50%, -50%)'
	domElement.style.minWidth = '100%'
	//domElement.style.top = '0px'
	//domElement.style.left = '0px'
	//domElement.style.width = '100%'
	//domElement.style.height = '100%'


        function onResize(){

                if( domElement.videoHeight === 0 )   return

                var videoAspect = domElement.videoWidth / domElement.videoHeight
                var windowAspect = window.innerWidth / window.innerHeight
				
				function onResize(){
                // is the size of the video available ?
                if( domElement.videoHeight === 0 )   return

                var videoAspect = domElement.videoWidth / domElement.videoHeight
                var windowAspect = window.innerWidth / window.innerHeight

// var video = document.querySelector('video')
//                 if( videoAspect < windowAspect ){
//                         domElement.style.left        = '0%'
//                         domElement.style.width       = window.innerWidth + 'px'
//                         domElement.style.marginLeft  = '0px'
//
//                         domElement.style.top         = '50%'
//                         domElement.style.height      =  (window.innerWidth/videoAspect) + 'px'
//                         domElement.style.marginTop   = -(window.innerWidth/videoAspect) /2 + 'px'
// console.log('videoAspect <<<<< windowAspect')
//                 }else{
//                         domElement.style.top         = '0%'
//                         domElement.style.height      = window.innerHeight+'px'
//                         domElement.style.marginTop   =  '0px'
//
//                         domElement.style.left        = '50%'
//                         domElement.style.width       =  (window.innerHeight*videoAspect) + 'px'
//                         domElement.style.marginLeft  = -(window.innerHeight*videoAspect)/2 + 'px'
// console.log('videoAspect >>>> windowAspect')
//                 }
        }
				
        }

        window.addEventListener('resize', function(event){
                onResize()
        })

        setInterval(function(){
                onResize()
        }, 500)

        navigator.mediaDevices.enumerateDevices().then(function(sourceInfos) {

                var constraints = {
                        audio: false,
                        video: { deviceId: videoSource ? {exact: videoSource} : undefined}
                }

                navigator.getUserMedia( constraints, function(stream){
                        //domElement.src = stream;
						domElement.srcObject = stream;
                }, function(error) {
                        console.error("Cant getUserMedia()! due to ", error);
                });
        });

	this.domElement = domElement
}
