<?php
session_start();
$fisiere = $_SESSION['fisiere'];
use Hazzard\Filepicker\Uploader;
use Hazzard\Filepicker\Http\Event;
use Hazzard\Filepicker\Http\Handler;
use Intervention\Image\ImageManager;
use Hazzard\Config\Repository as Config;
use Symfony\Component\HttpFoundation\Request;

// Include the autoloader.
require '../vendor/autoload.php';

// Create a new uploader instance.
$uploader = new Uploader($config = new Config, new ImageManager);

// Create a new http handler instance.
$handler = new Handler($uploader);

/**
 * Set some configuration options.
 *
 * http://docs.hazzardweb.com/filepicker/1.0/configphp
 */

$config['upload_dir'] = '../files/'.$fisiere;
$config['upload_url'] = '../upload/files/'.$fisiere;
$path = '../files/'.$fisiere;
// $config['max_file_size'] = 10000000; // 10 MB
// $config['accept_file_types'] = 'png|jpg|gif|zip|txt|pdf';
if (!file_exists($path)) {
    mkdir($path, '0777', 'true');
    chmod($path, 0777);
};
$config['image_versions.medium'] = array(
	'crop' => true,
	'max_width' => 300,
	'max_height' => 300
);

/**
 * Listen for events.
 *
 * http://docs.hazzardweb.com/filepicker/1.0/handler#events
 */

/**
 * Event fired before the file upload starts.
 */
$handler->on('upload.before', function(Event $e) {

});

/**
 * Event fired for a successfully upload.
 */
$handler->on('upload.success', function(Event $e) {

});

/**
 * Called if the upload fails.
 */
$handler->on('upload.fail', function(Event $e) {

});

/**
 * Event fired on file access.
 */
$handler->on('file.get', function(Event $e) {

});

/**
 * Event fired on file download.
 */
$handler->on('file.download', function(Event $e) {

});

/**
 * Event fired on file deletion.
 */
$handler->on('file.delete', function(Event $e) {

});

/**
 * Event fired before the crop starts.
 */
$handler->on('crop.before', function(Event $e) {

});

/**
 * Event fired on crop completion.
 */
$handler->on('crop.after', function(Event $e) {

});

// Handle an incoming HTTP request and send the response.
$handler->handle(Request::createFromGlobals())->send();
