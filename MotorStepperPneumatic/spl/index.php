<?php
session_start();
$marker=$_SESSION['marker'];
?>
<html>
<head>
  <title>AR-M</title>
	<meta charset="utf-8">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/themes/default/jquery.mobile.icons.min.css">
	<link rel="stylesheet" href="css/themes/default/jquery.mobile-1.4.5.min.css">
	<link rel="stylesheet" href="css/themes/default/jquery.mobile.structure-1.4.5.min.css">
	<link rel="shortcut icon" href="favicon.ico">
	<script src="js/jquery.js"></script>
	<script src="js/jquery.mobile-1.4.5.min.js"></script>
<script language="javascript" type="text/javascript">
function createCookie(name,value,days) {
  if (days) {
    var date = new Date();
    date.setTime(date.getTime()+(days*24*60*60*1000));
    var expires = "; expires="+date.toGMTString();
  }
  else var expires = "";
  document.cookie = name+"="+value+expires+"; path=/";
}
function eraseCookie(name) {
  createCookie(name,"",-1);
}
eraseCookie("vid");
var w = $( window ).width();
if (w > 1920){
w=1920;
}
openPage = function() {
location.href = "scan.php?w="+w;
}

</script>

</head>
<body bgcolor="#252525" align="center">
<div data-role="page" id="testpage" data-theme="b">
	<div class="ui-content" role="main">
	<div id="ui-bar-test" class="ui-bar ui-bar-a ui-corner-all" style="margin-bottom:1em;">
  <center>
<a href="javascript:openPage()"> <img src="spl.png" alt="Augmented Reality Mentainance" width="40%" ></a>
  </center>
</div>
<div id="latestData"> </div>
<div data-role="collapsibleset" data-content-theme="a">
<div class="select" style="display: none;">
      <label for="audioSource"> </label><select id="audioSource" ></select>
    </div>
    <div class="select" style="display: none;">
      <label for="audioOutput"></label><select id="audioOutput" ></select>
    </div>
			<div data-role="collapsible">
				<h3>Camera Settings</h3>
<div id="container">
    <div class="select">
      <label for="videoSource"></label><select id="videoSource"></select>
    </div>
</div>
  <video id="video" autoplay width="720" height="405"></video>
  </div>
<script>
'use strict';
var videoElement = document.querySelector('video');
var audioInputSelect = document.querySelector('select#audioSource');
var audioOutputSelect = document.querySelector('select#audioOutput');
var videoSelect = document.querySelector('select#videoSource');
var selectors = [audioInputSelect, audioOutputSelect, videoSelect];

function gotDevices(deviceInfos) {

  var values = selectors.map(function(select) {
    return select.value;
  });
  selectors.forEach(function(select) {
    while (select.firstChild) {
      select.removeChild(select.firstChild);
    }
  });
  for (var i = 0; i !== deviceInfos.length; ++i) {
    var deviceInfo = deviceInfos[i];
    var option = document.createElement('option');
    option.value = deviceInfo.deviceId;
    if (deviceInfo.kind === 'audioinput') {
      option.text = deviceInfo.label ||
          'microphone ' + (audioInputSelect.length + 1);
      audioInputSelect.appendChild(option);
    } else if (deviceInfo.kind === 'audiooutput') {
      option.text = deviceInfo.label || 'speaker ' +
          (audioOutputSelect.length + 1);
      audioOutputSelect.appendChild(option);
    } else if (deviceInfo.kind === 'videoinput') {
      option.text = deviceInfo.label || 'camera ' + (videoSelect.length + 1);
      videoSelect.appendChild(option);
    } else {
      console.log('Some other kind of source/device: ', deviceInfo);
    }
  }
  selectors.forEach(function(select, selectorIndex) {
    if (Array.prototype.slice.call(select.childNodes).some(function(n) {
      return n.value === values[selectorIndex];
    })) {
      select.value = values[selectorIndex];
    }
  });
}

navigator.mediaDevices.enumerateDevices().then(gotDevices).catch(handleError);

function attachSinkId(element, sinkId) {
  if (typeof element.sinkId !== 'undefined') {
    element.setSinkId(sinkId)
    .then(function() {
      console.log('Success, audio output device attached: ' + sinkId);
    })
    .catch(function(error) {
      var errorMessage = error;
      if (error.name === 'SecurityError') {
        errorMessage = 'You need to use HTTPS for selecting audio output ' +
            'device: ' + error;
      }
      console.error(errorMessage);
      audioOutputSelect.selectedIndex = 0;
    });
  } else {
    console.warn('Browser does not support output device selection.');
  }
}

function changeAudioDestination() {
  var audioDestination = audioOutputSelect.value;
  attachSinkId(videoElement, audioDestination);
}

function gotStream(stream) {
  window.stream = stream; 
  videoElement.srcObject = stream;

  return navigator.mediaDevices.enumerateDevices();
}

function start() {
  if (window.stream) {
    window.stream.getTracks().forEach(function(track) {
      track.stop();
    });
  }
  var audioSource = audioInputSelect.value;
  var videoSource = videoSelect.value;
createCookie("vid", videoSource, 30);
  var constraints = {
    audio: false,
    video: {deviceId: videoSource ? {exact: videoSource} : undefined}
  };
  navigator.mediaDevices.getUserMedia(constraints).
      then(gotStream).then(gotDevices).catch(handleError);
}
videoSelect.onchange = start;
start();
function handleError(error) {
  console.log('navigator.getUserMedia error: ', error);
}

</script>
</body> 


</html>