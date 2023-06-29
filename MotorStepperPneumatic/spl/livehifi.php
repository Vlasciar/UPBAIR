<?php
session_start();
$stream=$_GET['hifi'];
?>
<meta charset="UTF-8">
<html>
    <head>
		<script src="js/jquery.min.js"></script>

    <style>
video {
    width: 100%;
    max-height: 100%;
    box-sizing: border-box;
}
#player-overlay {
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
        
    z-index:999;
}

.form-control {
 
  width: 100%;
  height: 20px;
  padding: 6px 8px;
  font-size: 14px;
  line-height: 1;
  color: #ffffff;
  background-color: #434857;
  background-image: none;
  border: 1px solid #434857;
  border-radius: 4px;
  box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
  transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
}
.form-control:focus {
  border-color: #ffffff;
  outline: 0;
  box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(255, 255, 255, 0.6);
}
.form-control::-moz-placeholder {
  color: #cfd2da;
  opacity: 1;
}
.form-control:-ms-input-placeholder {
  color: #cfd2da;
}
.form-control::-webkit-input-placeholder {
  color: #cfd2da;
}
.form-control::-ms-expand {
  border: 0;
  background-color: transparent;
}
.form-control[disabled],
.form-control[readonly],
fieldset[disabled] .form-control {
  background-color: #30343e;
  opacity: 1;
}
.form-control[disabled],
fieldset[disabled] .form-control {
  cursor: not-allowed;
}
textarea.form-control {
  height: auto;
}

.apn {
    color: #1ca8dd;
     padding: 8px 12px;
  font-size: 14px;
  line-height: 1;
    background-color: transparent;
    border-color: #1ca8dd;
    border: 1px solid #1ca8dd;
    border-radius: 4px;
}
button.apn {
}
.apn:focus,
.apn.fd,
.apn:hover,
.apn:active,
.apn.active,
.open > .ff.apn {
  color: #ffffff;
  background-color: #1ca8dd;
  box-shadow: none;
}
.apn.disabled,
.apn[disabled],
fieldset[disabled] .apn,
.apn.disabled:hover,
.apn[disabled]:hover,
fieldset[disabled] .apn:hover,
.apn.disabled:focus,
.apn[disabled]:focus,
fieldset[disabled] .apn:focus,
.apn.disabled.fd,
.apn[disabled].fd,
fieldset[disabled] .apn.fd,
.apn.disabled:active,
.apn[disabled]:active,
fieldset[disabled] .apn:active,
.apn.disabled.active,
.apn[disabled].active,
fieldset[disabled] .apn.active {
  border-color: #1ca8dd;
}
.apn .fg {
  color: #ffffff;
  background-color: #1ca8dd;
}
.el {
  margin-bottom: 10px;
}

.apnr {
    color: #c61931;
     padding: 8px 12px;
  font-size: 14px;
  line-height: 1;
    background-color: transparent;
    border-color: #c61931;
    border: 1px solid #c61931;
    border-radius: 4px;
}
button.apnr {
}
.apnr:focus,
.apnr.fd,
.apnr:hover,
.apnr:active,
.apnr.active,
.open > .ff.apnr {
  color: #ffffff;
  background-color: #c61931;
  box-shadow: none;
}
.apnr.disabled,
.apnr[disabled],
fieldset[disabled] .apnr,
.apnr.disabled:hover,
.apnr[disabled]:hover,
fieldset[disabled] .apnr:hover,
.apnr.disabled:focus,
.apnr[disabled]:focus,
fieldset[disabled] .apnr:focus,
.apnr.disabled.fd,
.apnr[disabled].fd,
fieldset[disabled] .apnr.fd,
.apnr.disabled:active,
.apnr[disabled]:active,
fieldset[disabled] .apnr:active,
.apnr.disabled.active,
.apnr[disabled].active,
fieldset[disabled] .apnr.active {
    color: #ffffff;
  background-color: #c61931;
  box-shadow: none;
}
.apnr .fg {
  color: #ffffff;
  background-color: #c61931;
}
    </style>

<script>
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

const GO_BUTTON_START = "LIVE";
const GO_BUTTON_STOP = "STOP";

var localVideo = null;
var remoteVideo = null;
var peerConnection = null;
var peerConnectionConfig = {'iceServers': []};
var localStream = null;
var wsURL = "wss://5e448368eb02f.streamlock.net:443/webrtc-session.json";
var wsConnection = null;
var streamInfo = {applicationName:"webrtc", streamName:"<?=$stream?>", sessionId:"[empty]"};
var userData = {param1:"value1"};
var videoBitrate = "4096";
var audioBitrate = "192";
var videoFrameRate = "29.97";
var videoChoice = "VP8";
var audioChoice = "opus";
var opus = {stereo: 1};
var videoIndex = -1;
var audioIndex = -1;
var userAgent = null;
var newAPI = false;
var SDPOutput = new Object();

navigator.getUserMedia = navigator.getUserMedia || navigator.mozGetUserMedia || navigator.webkitGetUserMedia;
window.RTCPeerConnection = window.RTCPeerConnection || window.mozRTCPeerConnection || window.webkitRTCPeerConnection;
window.RTCIceCandidate = window.RTCIceCandidate || window.mozRTCIceCandidate || window.webkitRTCIceCandidate;
window.RTCSessionDescription = window.RTCSessionDescription || window.mozRTCSessionDescription || window.webkitRTCSessionDescription;


function pageReady()
{
    
	userAgent = $('#userAgent').val().toLowerCase();

	if ( userAgent == null )
	{
		userAgent="unknown";
	}

	$("#buttonGo").attr('value', GO_BUTTON_START);

	localVideo = document.getElementById('localVideo');

	/*
		// firefox
		video: {
			width: { min: 1280, ideal: 1280, max: 1920 },
			height: { min: 720, ideal: 720, max: 1080 }
		  },

		// chrome
		video: {
			mandatory: {
				minWidth: 1280,
				maxWidth: 1280,
				minHeight: 720,
				maxHeight: 720,
				minFrameRate: 30,
				maxFrameRate: 30
			}
		},

		video: {
			mandatory: {
				minAspectRatio: 1.7777777778
			}
		},

		video: true,
	*/

				var constraints = {
					audio: {
                            mandatory: {
                            			echoCancellation: false,
                						googEchoCancellation: false,
                						googAutoGainControl: false,
                						googAutoGainControl2: false,
                						googNoiseSuppression: false,
                						googHighpassFilter: false,
                						googTypingNoiseDetection: false,
        								}
        					},
					video: {
						
					 deviceId: videoSource ? {exact: videoSource} : undefined
					}
				};

				


    if(navigator.mediaDevices.getUserMedia)
	{
		navigator.mediaDevices.getUserMedia(constraints).then(getUserMediaSuccess).catch(errorHandler);
		newAPI = false;
	}
    else if (navigator.getUserMedia)
    {
        navigator.getUserMedia(constraints, getUserMediaSuccess, errorHandler);
    }
    else
    {
        alert('Your browser does not support getUserMedia API');
    }

	console.log("newAPI: "+newAPI);

}

function wsConnect(url)
{
	wsConnection = new WebSocket(url);
	wsConnection.binaryType = 'arraybuffer';

	wsConnection.onopen = function()
	{
		console.log("wsConnection.onopen");

		peerConnection = new RTCPeerConnection(peerConnectionConfig);
		peerConnection.onicecandidate = gotIceCandidate;

		if (newAPI)
		{
			var localTracks = localStream.getTracks();
			for(localTrack in localTracks)
			{
				peerConnection.addTrack(localTracks[localTrack], localStream);
			}
		}
		else
		{
			peerConnection.addStream(localStream);
		}

		peerConnection.createOffer(gotDescription, errorHandler);


	}

 //var offerOptions = {
    // New spec states offerToReceiveAudio/Video are of type long (due to
    // having to tell how many "m" lines to generate).
    // http://w3c.github.io/webrtc-pc/#idl-def-RTCOfferAnswerOptions.
  //  offerToReceiveAudio: 1,
   // offerToReceiveVideo: 1,
//	codecPayloadType: 0x42E01F,
 // };

	wsConnection.onmessage = function(evt)
	{
		console.log("wsConnection.onmessage: "+evt.data);

		var msgJSON = JSON.parse(evt.data);

		var msgStatus = Number(msgJSON['status']);
		var msgCommand = msgJSON['command'];

		if (msgStatus != 200)
		{
			$("#sdpDataTag").html(msgJSON['statusDescription']);
			stopPublisher();
		}
		else
		{
			$("#sdpDataTag").html("");

			var sdpData = msgJSON['sdp'];
			if (sdpData !== undefined)
			{
				console.log('sdp: '+msgJSON['sdp']);

				peerConnection.setRemoteDescription(new RTCSessionDescription(sdpData), function() {
					//peerConnection.createAnswer(gotDescription, errorHandler);
				}, errorHandler);
			}

			var iceCandidates = msgJSON['iceCandidates'];
			if (iceCandidates !== undefined)
			{
				for(var index in iceCandidates)
				{
					console.log('iceCandidates: '+iceCandidates[index]);

					peerConnection.addIceCandidate(new RTCIceCandidate(iceCandidates[index]));
				}
			}
		}

		if (wsConnection != null)
			wsConnection.close();
		wsConnection = null;
	}

	wsConnection.onclose = function()
	{
		console.log("wsConnection.onclose");
	}

	wsConnection.onerror = function(evt)
	{
		console.log("wsConnection.onerror: "+JSON.stringify(evt));

		$("#sdpDataTag").html('WebSocket connection failed: '+wsURL);
		stopPublisher();
	}
}

function getUserMediaSuccess(stream)
{
	console.log("getUserMediaSuccess: "+stream);
    localStream = stream;
	try{
		localVideo.srcObject = stream;
	} catch (error){
		localVideo.src = window.URL.createObjectURL(stream);
	}
}

function startPublisher()
{
    wsURL = "wss://5e448368eb02f.streamlock.net:443/webrtc-session.json";
	streamInfo.applicationName = "webrtc";
	streamInfo.streamName = "<?=$stream?>";
	videoBitrate = 4096;
	audioBitrate = 192;
	videoFrameRate = 29.97;
	videoChoice = "VP8";
	audioChoice = "opus";
	opus = {stereo: 1};

	console.log("startPublisher: wsURL:"+wsURL+" streamInfo:"+JSON.stringify(streamInfo));

	wsConnect(wsURL);

	$("#buttonGo").attr('value', GO_BUTTON_STOP);
}

function stopPublisher()
{
	if (peerConnection != null)
		peerConnection.close();
	peerConnection = null;

	if (wsConnection != null)
		wsConnection.close();
	wsConnection = null;

	$("#buttonGo").attr('value', GO_BUTTON_START);

	console.log("stopPublisher");
}

function start()
{
	if (peerConnection == null)
		startPublisher();
	else
		stopPublisher();
}

function gotIceCandidate(event)
{
    if(event.candidate != null)
    {
    	console.log('gotIceCandidate: '+JSON.stringify({'ice': event.candidate}));
    }
}

function gotDescription(description)
{
	var enhanceData = new Object();

	if (audioBitrate !== undefined)
		enhanceData.audioBitrate = Number(audioBitrate);
	if (videoBitrate !== undefined)
		enhanceData.videoBitrate = Number(videoBitrate);
	if (videoFrameRate !== undefined)
		enhanceData.videoFrameRate = Number(videoFrameRate);


	description.sdp = enhanceSDP(description.sdp, enhanceData);

	console.log('gotDescription: '+JSON.stringify({'sdp': description}));

    peerConnection.setLocalDescription(description, function () {

		wsConnection.send('{"direction":"publish", "command":"sendOffer", "streamInfo":'+JSON.stringify(streamInfo)+', "sdp":'+JSON.stringify(description)+', "opus":'+JSON.stringify(opus)+', "userData":'+JSON.stringify(userData)+'}');

    }, function() {console.log('set description error')});
}

function addAudio(sdpStr, audioLine)
{
	var sdpLines = sdpStr.split(/\r\n/);
	var sdpSection = 'header';
	var hitMID = false;
	var sdpStrRet = '';
	var done = false;

	for(var sdpIndex in sdpLines)
	{
		var sdpLine = sdpLines[sdpIndex];

		if (sdpLine.length <= 0)
			continue;


		sdpStrRet +=sdpLine;
		sdpStrRet += '\r\n';

		if ( 'a=rtcp-mux'.localeCompare(sdpLine) == 0 && done == false )
		{
			sdpStrRet +=audioLine;
			done = true;
		}


	}
	return sdpStrRet;
}

function addVideo(sdpStr, videoLine)
{
	var sdpLines = sdpStr.split(/\r\n/);
	var sdpSection = 'header';
	var hitMID = false;
	var sdpStrRet = '';
	var done = false;

	var rtcpSize = false;
	var rtcpMux = false;

	for(var sdpIndex in sdpLines)
	{
		var sdpLine = sdpLines[sdpIndex];

		if (sdpLine.length <= 0)
			continue;

		if ( sdpLine.includes("a=rtcp-rsize") )
		{
			rtcpSize = true;
		}

		if ( sdpLine.includes("a=rtcp-mux") )
		{
			rtcpMux = true;
		}

	}

	for(var sdpIndex in sdpLines)
	{
		var sdpLine = sdpLines[sdpIndex];

		sdpStrRet +=sdpLine;
		sdpStrRet += '\r\n';

		if ( ('a=rtcp-rsize'.localeCompare(sdpLine) == 0 ) && done == false && rtcpSize == true)
		{
			sdpStrRet +=videoLine;
			done = true;
		}

		if ( 'a=rtcp-mux'.localeCompare(sdpLine) == 0 && done == true && rtcpSize == false)
		{
			sdpStrRet +=videoLine;
			done = true;
		}

		if ( 'a=rtcp-mux'.localeCompare(sdpLine) == 0 && done == false && rtcpSize == false )
		{
			done = true;
		}

	}
	return sdpStrRet;
}

function enhanceSDP(sdpStr, enhanceData)
{
	var sdpLines = sdpStr.split(/\r\n/);
	var sdpSection = 'header';
	var hitMID = false;
	var sdpStrRet = '';

	//console.log("Original SDP: "+sdpStr);

	// Firefox provides a reasonable SDP, Chrome is just odd
	// so we have to doing a little mundging to make it all work
	if ( !sdpStr.includes("THIS_IS_SDPARTA") || videoChoice.includes("VP9") )
	{
		for(var sdpIndex in sdpLines)
		{
			var sdpLine = sdpLines[sdpIndex];

			if (sdpLine.length <= 0)
				continue;

			var doneCheck = checkLine(sdpLine);
			if ( !doneCheck )
				continue;

			sdpStrRet +=sdpLine;
			sdpStrRet += '\r\n';

		}
		sdpStrRet =  addAudio(sdpStrRet, deliverCheckLine(audioChoice,"audio"));
		sdpStrRet =  addVideo(sdpStrRet, deliverCheckLine(videoChoice,"video"));
		sdpStr = sdpStrRet;
		sdpLines = sdpStr.split(/\r\n/);
		sdpStrRet = '';
	}

	for(var sdpIndex in sdpLines)
	{
		var sdpLine = sdpLines[sdpIndex];

		if (sdpLine.length <= 0)
			continue;

		if ( sdpLine.indexOf("m=audio") ==0 && audioIndex !=-1 )
		{
			audioMLines = sdpLine.split(" ");
			sdpStrRet+=audioMLines[0]+" "+audioMLines[1]+" "+audioMLines[2]+" "+audioIndex+"\r\n";
			continue;
		}

		if ( sdpLine.indexOf("m=video") == 0 && videoIndex !=-1 )
		{
			audioMLines = sdpLine.split(" ");
			sdpStrRet+=audioMLines[0]+" "+audioMLines[1]+" "+audioMLines[2]+" "+videoIndex+"\r\n";
			continue;
		}

		sdpStrRet += sdpLine;

		if (sdpLine.indexOf("m=audio") === 0)
		{
			sdpSection = 'audio';
			hitMID = false;
		}
		else if (sdpLine.indexOf("m=video") === 0)
		{
			sdpSection = 'video';
			hitMID = false;
		}
		else if (sdpLine.indexOf("a=rtpmap") == 0 )
		{
			sdpSection = 'bandwidth';
			hitMID = false;
		}

		if (sdpLine.indexOf("a=mid:") === 0 || sdpLine.indexOf("a=rtpmap") == 0 )
		{
			if (!hitMID)
			{
				if ('audio'.localeCompare(sdpSection) == 0)
				{
					if (enhanceData.audioBitrate !== undefined)
					{
						sdpStrRet += '\r\nb=CT:' + (enhanceData.audioBitrate);
						sdpStrRet += '\r\nb=AS:' + (enhanceData.audioBitrate);
					}
					hitMID = true;
				}
				else if ('video'.localeCompare(sdpSection) == 0)
				{
					if (enhanceData.videoBitrate !== undefined)
					{
						sdpStrRet += '\r\nb=CT:' + (enhanceData.videoBitrate);
						sdpStrRet += '\r\nb=AS:' + (enhanceData.videoBitrate);
						if ( enhanceData.videoFrameRate !== undefined )
							{
								sdpStrRet += '\r\na=framerate:'+enhanceData.videoFrameRate;
							}
					}
					hitMID = true;
				}
				else if ('bandwidth'.localeCompare(sdpSection) == 0 )
				{
					var rtpmapID;
					rtpmapID = getrtpMapID(sdpLine);
					if ( rtpmapID !== null  )
					{
						var match = rtpmapID[2].toLowerCase();
						if ( ('vp9'.localeCompare(match) == 0 ) ||  ('vp8'.localeCompare(match) == 0 ) || ('h264'.localeCompare(match) == 0 ) ||
							('red'.localeCompare(match) == 0 ) || ('ulpfec'.localeCompare(match) == 0 ) || ('rtx'.localeCompare(match) == 0 ) )
						{
							if (enhanceData.videoBitrate !== undefined)
								{
								sdpStrRet+='\r\na=fmtp:'+rtpmapID[1]+' x-google-min-bitrate='+(enhanceData.videoBitrate)+';x-google-max-bitrate='+(enhanceData.videoBitrate);
								}
						}

						if ( ('opus'.localeCompare(match) == 0 ) ||  ('isac'.localeCompare(match) == 0 ) || ('g722'.localeCompare(match) == 0 ) || ('pcmu'.localeCompare(match) == 0 ) ||
								('pcma'.localeCompare(match) == 0 ) || ('cn'.localeCompare(match) == 0 ))
						{
							if (enhanceData.audioBitrate !== undefined)
								{
								sdpStrRet+='\r\na=fmtp:'+rtpmapID[1]+' x-google-min-bitrate='+(enhanceData.audioBitrate)+';x-google-max-bitrate='+(enhanceData.audioBitrate);
								}
						}
					}
				}
			}
		}
		sdpStrRet += '\r\n';
	}
	console.log("Resuling SDP: "+sdpStrRet);
	return sdpStrRet;
}

function deliverCheckLine(profile,type)
{
	var outputString = "";
	for(var line in SDPOutput)
	{
		var lineInUse = SDPOutput[line];
		outputString+=line;
		if ( lineInUse.includes(profile) )
		{
			if ( profile.includes("VP9") || profile.includes("VP8"))
			{
				var output = "";
				var outputs = lineInUse.split(/\r\n/);
				for(var position in outputs)
				{
					var transport = outputs[position];
					if (transport.indexOf("transport-cc") !== -1 || transport.indexOf("goog-remb") !== -1 || transport.indexOf("nack") !== -1)
					{
						continue;
					}
					output+=transport;
					output+="\r\n";
				}

				if (type.includes("audio") )
				{
					audioIndex = line;
				}

				if (type.includes("video") )
				{
					videoIndex = line;
				}

				return output;
			}
			if (type.includes("audio") )
			{
				audioIndex = line;
			}

			if (type.includes("video") )
			{
				videoIndex = line;
			}
			return lineInUse;
		}
	}
	return outputString;
}

function checkLine(line)
{
	if ( line.startsWith("a=rtpmap") || line.startsWith("a=rtcp-fb") || line.startsWith("a=fmtp"))
	{
		var res = line.split(":");

		if ( res.length>1 )
		{
			var number = res[1].split(" ");
			if ( !isNaN(number[0]) )
			{
				if ( !number[1].startsWith("http") && !number[1].startsWith("ur") )
				{
					var currentString = SDPOutput[number[0]];
					if (!currentString)
					{
						currentString = "";
					}
					currentString+=line+"\r\n";
					SDPOutput[number[0]]=currentString;
					return false;
				}
			}
		}
	}

	return true;
}

function getrtpMapID(line)
{
	var findid = new RegExp('a=rtpmap:(\\d+) (\\w+)/(\\d+)');
	var found = line.match(findid);
	return (found && found.length >= 3) ? found: null;
}

function errorHandler(error)
{
    console.log(error);
}

        </script>
		<style>
		.div-section {margin-bottom: 8px;}
		</style>
    </head>
    <body scroll="no" style="overflow: hidden; background-color:#000000;">

	
<div id="player-overlay">
		<video id="localVideo" autoplay muted></video>
		<input type="hidden" id="userAgent" name="userAgent" value="" />

		<div style="position: relative; top: -32px; left:2px">
			<input type="button" class="ce apn" id="buttonGo" onclick="start()" value="" />
		</div>
</div>

		<script type="text/javascript">
			document.getElementById("userAgent").value = navigator.userAgent;
			pageReady();
		</script>


		<div style="position: relative; top: -54px; left:50px"><span id="sdpDataTag"></span></div>
	

    </body>
</html>