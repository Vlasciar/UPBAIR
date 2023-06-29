<?php
session_start();
if ($_SESSION['mobile']){
$meniu = 'off';
}else{
$meniu ='on';
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Multi file uploader</title>

	<!-- CSS -->
	<link rel="stylesheet" href="assets/css/vendor/bootstrap.css">
	<link rel="stylesheet" href="assets/css/vendor/jquery.Jcrop.css">
	<link rel="stylesheet" href="assets/css/filepicker.css">
	<link rel="stylesheet" href="assets/css/file-icons.css">
	<link rel="stylesheet" href="assets/css/demo.css">
	<link href="assets/css/toolkit-inverse.css" rel="stylesheet">
</head>
<body>




		<div id="main">
	<!-- Start Filepicker -->
			
				
			<form id="filepicker" method="POST" enctype="multipart/form-data">
	<?php
	if ($meniu == 'on'){				
?>				
				<div class="btn btn-primary fileinput">
					<span class="glyphicon glyphicon-plus"></span>
					Add files
					<input type="file" name="files[]" multiple>
				</div>

				<button type="button" class="btn btn-primary webcam">
					<span class="glyphicon glyphicon-camera"></span>
					Webcam
				</button>

				<button type="button" class="btn btn-info start">
					<span class="glyphicon glyphicon-upload"></span>
					Start upload
				</button>

				<button type="button" class="btn btn-warning cancel">
					<span class="glyphicon glyphicon-ban-circle"></span>
					Cancel upload
				</button>

				<br>

				<!-- Inline webcam container -->
				<div class="webcam-container" style="display: none;">
					<div class="camera"></div>
					<button type="button" class="btn btn-info snap">Take picture</button>
					<button type="button" class="btn btn-warning close-webcam">Close webcam</button>
				</div>

				<!-- Inline crop container -->
				<div class="crop-container" style="display: none;">
					<div class="crop-preview"></div>
					<br>
					<button type="button" class="btn btn-default cancel">Cancel</button>
					<button type="button" class="btn btn-primary save" data-loading-text="Saving...">Save</button>
				</div>

				<div class="drop-window fade">
					<div class="drop-window-content">
						<h3>Drop files to upload</h3>
					</div>
				</div>
<?php
}
?>
<br/>			</form>

			<div class="table-responsive">
				<table class="cl table-striped">
					<thead>
						<tr>
							<th><font color="434857">Preview</font></th>
							<th class="column-name"><font color="434857">Filename</font></th>
							<th><font color="434857">Actions</font></th>
						</tr>
					</thead>
					<tbody class="files" style="padding:10px;">
					</tbody>
				</table>
			</div>

			<div class="text-center">
				<ul class="pagination pagination-sm"></ul>
			</div>

			<!-- End Filepicker -->
		</div>
	</div>

	<!-- File upload template -->
	<script type="text/html" id="templateUpload">
		<tr class="fade template-upload">
			<td>
				<div class="preview">
					<span class="icon-file-<%= file.extension %>"></span>
				</div>
			</td>
			<td class="column-name">
				<p class="name"><%= file.name %></p>
				<span class="text-danger error">
					<% if (file.error) { %>
						<%= file.error %>
					<% } %>
				</span>
			</td>
			<td colspan="2">
				<p><%= file.sizeFormatted || '' %></p>
				<div class="progress">
					<div class="progress-bar progress-bar-striped active"></div>
				</div>
			</td>
			<td>
				<% if ( ! options.autoUpload && ! file.error) { %>
					<button type="button" class="btn btn-info btn-sm start" title="Upload this file">
						<span class="glyphicon glyphicon-upload"></span>
					</button>
				<% } %>

				<button type="button" class="btn btn-warning btn-sm cancel" title="Cancel this file">
					<span class="glyphicon glyphicon-ban-circle"></span>
				</button>
			</td>
		</tr>
	</script>

	<!-- File download template -->
	<script type="text/html" id="templateDownload">
		<tr class="fade template-download">
			<td>
				<div class="preview">
					<% if (file.versions && file.versions.thumb) { %>
						<a href="<%= file.url %>" target="_blank">
							<img src="<%= file.versions.thumb.url %>" width="64" height="64"></a>
						</a>
					<% } else { %>
						<span class="icon-file-<%= file.extension %>"></span>
					<% } %>
				</div>
			</td>
			<td class="column-name">
				<p class="name">
					<% if (file.url) { %>
						<a href="<%= file.url %>" target="_blank"><%= file.name %></a>
					<% } else { %>
						<%= file.name %>
					<% } %>
				</p>

				<% if (file.error) { %>
					<span class="text-danger"><%= file.error %></span>
				<% } %>
			</td>
<td>
<?php
	if ($meniu == 'on'){				
?>				
				<% if (file.imageFileType && ! file.error) { %>
					<button type="button" class="btn btn-info btn-sm crop" title="Crop this image">
						<span class="glyphicon glyphicon-edit"></span>
					</button>
				<% } else {  %>
					<button type="button" style="visibility:hidden;width:32px"></button>
				<% } %>

				<% if (file.error) { %>
					<button type="button" class="btn btn-warning btn-sm cancel" title="Cancel this file">
						<span class="glyphicon glyphicon-ban-circle"></span>
					</button>
				<% } else { %>
					<button type="button" class="btn btn-danger btn-sm delete" title="Delete this file">
						<span class="glyphicon glyphicon-trash"></span>
					</button>
				<% } %>
<?php
}
?>
</td>		

</script>

	

	<!-- Bootstrap webcam modal -->
	<div class="modal fade" id="webcam-modal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-title">Webcam</h4>
				</div>
				<div class="modal-body">
					<div class="camera"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default close-webcam" data-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-info snap">Take picture</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Bootstrap crop modal -->
	<div class="modal fade" id="crop-modal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-title">Crop image</h4>
				</div>
				<div class="modal-body">
					<div class="crop-preview"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary save" data-loading-text="Saving...">Save</button>
				</div>
			</div>
		</div>
	</div>

	<!-- JavaScript -->
	<script src="assets/js/vendor/jquery.js"></script>
	<script src="assets/js/vendor/bootstrap.js"></script>
	<script src="assets/js/vendor/jquery.Jcrop.js"></script>

	<script src="assets/js/jquery.filepicker.js"></script>

	<script src="assets/js/plugins/pagination.js"></script>
	<script src="assets/js/plugins/webcam.js"></script>
	<script src="assets/js/plugins/crop.js"></script>

	<script>
		jQuery(document).ready(function($) {
			// Initialize the plugin.
			var FP = $('#filepicker').filePicker({
				url: 'uploader/index.php',
				filesList: $('.files'),
			});

			// Filepicker pagination plugin.
			FilepickerPagination(FP, $('.pagination'));

			// Filepicker webcam plugin.
			FilepickerWebcam(FP, {
				container: $('#webcam-modal'),
				openButton: $('.webcam'),
			});

			// Filepicker crop plugin.
			FilepickerCrop(FP, {
				container: $('#crop-modal'),
			});
		});
	</script>
	





</body>
</html>
