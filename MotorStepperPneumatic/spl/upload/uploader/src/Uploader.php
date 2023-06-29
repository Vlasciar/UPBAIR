<?php

/**
 * This file is part of Filepicker.
 *
 * (c) HazzardWeb <hazzardweb@gmail.com>
 *
 * For the full copyright and license information, please visit:
 * http://codecanyon.net/licenses/standard
 */

namespace Hazzard\Filepicker;

use Intervention\Image\ImageManager;
use Hazzard\Config\Repository as Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Hazzard\Filepicker\Exception\FileValidationException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class Uploader
{
	/**
	 * Upload move error code.
	 *
	 * @var int
	 */
	const UPLOAD_ERR_MOVE = 9;

	/**
	 * Config instance.
	 *
	 * @var \Hazzard\Config\Repository
	 */
	protected $config;

	/**
	 * The Intervention Image Manager.
	 *
	 * @var \Intervention\Image\ImageManager
	 */
	protected $imageManager;

	/**
	 * Create a new uploader instance.
	 *
	 * @param \Hazzard\Config\Repository $config
	 * @param \Intervention\Image\ImageManager|null $imageManager
	 */
	public function __construct(Config $config, $imageManager = null)
	{
		$this->config = $config;
		$this->imageManager = $imageManager;
		$this->defaultConfig();
	}

	/**
	 * Upload file.
	 *
	 * @param  \Symfony\Component\HttpFoundation\File\UploadedFile|array $file
	 * @param  string|null $name
	 * @return \Symfony\Component\HttpFoundation\File\File
	 *
	 * @throws \Hazzzard\Uploader\Exception\FileValidationException
	 * @throws \Symfony\Component\HttpFoundation\File\Exception\UploadException
	 */
	public function upload($file, $name = null)
	{
		try {
			$file = $this->createUploadedFile($file);
		} catch (FileNotFoundException $e) {
			throw new UploadException($this->getErrorMessage('no_file'));
		}

		$max = $this->config['max_number_of_files'];
		if ($max && $this->getTotal() >= $max) {
			throw new UploadException($this->getErrorMessage('max_number_of_files'));
        }

		$message = $this->getErrorMessage('upload_failed', array($file->getError() ?: self::UPLOAD_ERR_MOVE));

		if (! $file->isValid()) {
			throw new UploadException($this->debug() ? $file->getErrorMessage() : $message);
		}

		$this->validateFile($file);

		$name = $name ?: $this->getUniqueFilename($file->getClientOriginalName());

		try {
			$file = $file->move($this->getPath(), $name);
		} catch (FileException $e) {
			throw new UploadException($this->debug() ? $e->getMessage() : $message);
		}

		if ($this->imageFileType($file)) {
			$this->createImageVersions($file->getFilename());
		}

		return $file;
	}

	/**
	 * Get uploaded files.
	 *
	 * @param  int      $offset
	 * @param  int|null $limit
	 * @return array
	 */
	public function get($offset = 0, $limit = null)
	{
		$files = array_slice($this->scanDir(), $offset, $limit ?: null);

		foreach ($files as $index => $file) {
			$files[$index] = $this->createFile($file);
		}

		return $files;
	}

	/**
	 * Get the total number of uploaded files.
	 *
	 * @return int
	 */
	public function getTotal()
	{
		return count($this->scanDir());
	}

	/**
	 * Get image versions.
	 *
	 * @param  string $filename
	 * @return array
	 */
	public function getImageVersions($filename)
	{
		$files = array();

		foreach ($this->config['image_versions'] as $version => $options) {
			if (empty($version)) continue;

			try {
				$files[$version] = $this->createFile($filename, $version);
			} catch (FileNotFoundException $e) {

			}
		}

		return $files;
	}

	/**
	 * Create file download response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\File\File|string $file
	 * @param  string $version
	 * @param  bool   $prepare
	 * @return \Symfony\Component\HttpFoundation\Response
	 *
	 * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
	 */
	public function download($file, $version = null, $prepare = true)
	{
		$file = $this->createFile($file, $version);

		$headers = array('X-Content-Type-Options' => 'nosniff');

		if ($this->inlineFileType($file->getFilename())) {
			$disposition = 'inline';
		} else {
			$disposition = 'attachment';
		}

		$response = $this->createFileResponse($file, null, $headers, $disposition);

		if ($prepare) {
			return $this->prepareFileResponse($response)->send();
		}

		return $response;
	}

	/**
	 * Delete file.
	 *
	 * @param  string $filename
	 * @return bool
	 *
	 * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
	 */
	public function delete($filename, $version = null)
	{
		$file = $this->createFile($filename, $version);

		$success = @unlink($file);

		if ($success && $this->imageFileType($filename)) {
			$this->deleteImageVersions($filename);
		}

        return $success;
	}

	/**
	 * Validate uploaded file.
	 *
	 * @param  \Symfony\Component\HttpFoundation\File\UploadedFile $file
	 * @return void
	 *
	 * @throws \Hazzzard\Uploader\Exception\FileValidationException
	 */
	protected function validateFile(UploadedFile $file)
	{
		$maxSize = $this->config['max_file_size'];
		$minSize = $this->config['min_file_size'];

		if (! $this->acceptFileType($file->getClientOriginalName())) {
			throw new FileValidationException(
				$this->getErrorMessage('accept_file_types')
			);
		}

		if ($maxSize && $file->getClientSize() > $maxSize) {
			throw new FileValidationException(
				$this->getErrorMessage('max_file_size', array($maxSize / 1024))
			);
		}

		if ($minSize && $file->getClientSize() < $minSize) {
			throw new FileValidationException(
				$this->getErrorMessage('min_file_size')
			);
        }

		if ($this->imageFileType($file->getClientOriginalName())) {
			$this->validateImage($file);
		}
	}

	/**
	 * Validate uploaded file as image.
	 *
	 * @param  \Symfony\Component\HttpFoundation\File\UploadedFile $file
	 * @return void
	 *
	 * @throws \Hazzzard\Uploader\Exception\FileValidationException
	 */
	protected function validateImage(UploadedFile $file)
	{
		if (! $this->imageManager) return;

		$maxWidth  = $this->config['max_width'];
		$maxHeight = $this->config['max_height'];
		$minWidth  = $this->config['min_width'];
		$minHeight = $this->config['min_height'];

		if (! $maxWidth && ! $maxHeight && ! $minWidth && ! $minHeight) {
			return;
		}

		list($imgWidth, $imgHeight) = getimagesize($file);

		// $image = $this->imageManager->make($file);
		// $imgWidth  = $image->width();
		// $imgHeight = $image->height();

		if ($maxWidth && $imgWidth > $maxWidth) {
			throw new FileValidationException(
				$this->getErrorMessage('max_width', array($maxWidth))
			);
		}

		if ($minWidth && $imgWidth < $minWidth) {
			throw new FileValidationException(
				$this->getErrorMessage('min_width', array($minWidth))
			);
		}

		if ($maxHeight && $imgHeight > $maxHeight) {
			throw new FileValidationException(
				$this->getErrorMessage('max_height', array($maxHeight))
			);
		}

		if ($minHeight && $imgHeight < $minHeight) {
			throw new FileValidationException(
				$this->getErrorMessage('min_height', array($minHeight))
			);
		}
	}

	/**
	 * Create image versions.
	 *
	 * @param  string $filename
	 * @return void
	 */
	public function createImageVersions($filename)
	{
		if (! $this->imageManager) return;

		foreach ($this->config['image_versions'] as $version => $options) {
			$this->createImageVersion($filename, $version, $options);
		}
	}

	/**
	 * Create image version.
	 *
	 * @param  string $filename
	 * @param  string $version
	 * @param  array  $options
	 * @return bool
	 */
	public function createImageVersion($filename, $version, $options = array())
	{
		list($filepath, $newFilepath) = $this->getImageVersionPaths($filename, $version);

		$image = $this->imageManager->make($filepath);

		if (! empty($options['before'])) {
			if (call_user_func($options['before'], $image, $version) === false) {
				return false;
			}
		}

		if (! empty($options['auto_orient'])) {
			$image->orientate();
		}

		$maxWidth = $image->width();
		$maxHeight = $image->height();

		if (! empty($options['max_width'])) {
			$maxWidth = $options['max_width'];
		}

		if (! empty($options['max_height'])) {
			$maxHeight = $options['max_height'];
		}

		$quality = isset($options['quality']) ? $options['quality'] : null;

		$scale = min($maxWidth / $image->width(), $maxHeight / $image->height());

		if ($scale >= 1) {
			//
		} elseif (empty($options['crop'])) {
			$newWidth = $image->width() * $scale;
			$newHeight = $image->height() * $scale;
			$image->resize($newWidth, $newHeight);
		} else {
			if (($image->width() / $image->height()) >= ($maxWidth / $maxHeight)) {
				$newWidth = $image->width() / ($image->height() / $maxHeight);
				$newHeight = $maxHeight;
			} else {
				$newWidth = $maxWidth;
				$newHeight = $image->height() / ($image->width() / $maxWidth);
			}

			$image->resize($newWidth, $newHeight);
			$image->crop($maxWidth, $maxHeight);
		}

		$image->save($newFilepath, $quality);

		if (! empty($options['after'])) {
			call_user_func($options['after'], $image, $version);
		}

		$image->destroy();

		return true;
	}

	/**
	 * Get paths for creating image version.
	 *
	 * @param  string $filename
	 * @param  string $version
	 * @return array
	 */
	protected function getImageVersionPaths($filename, $version)
	{
		$versionDir = $this->getPath(null, $version);

		if (! is_dir($versionDir)) {
			@mkdir($versionDir, $this->config['mkdir_mode'], true);
		}

		return array(
			$this->getPath($filename),
			$this->getPath($filename, $version)
		);
	}

	/**
	 * Delete image versions.
	 *
	 * @param  string $filename
	 * @return array
	 */
	public function deleteImageVersions($filename)
	{
		foreach ($this->config['image_versions'] as $version => $options) {
			if (! empty($version)) {
				@unlink($this->getPath($filename, $version));
			}
		}
	}

	/**
	 * Create file instance.
	 *
	 * @param  string $file
	 * @return \Symfony\Component\HttpFoundation\File\File
	 *
	 * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
	 */
	public function createFile($file, $version = null)
	{
		if ($file instanceof File) return $file;

		return new File($this->getPath($file, $version));
	}

	/**
	 * Create uploaded file instance.
	 *
	 * @param  \Symfony\Component\HttpFoundation\File\UploadedFile|array $file
	 * @return \Symfony\Component\HttpFoundation\File\UploadedFile
	 *
	 * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
	 */
	public function createUploadedFile($file)
	{
		if ($file instanceof UploadedFile) return $file;

		return new UploadedFile(
			$file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error']
		);
	}

	/**
	 * Get file name.
	 *
	 * @param  string $filename
	 * @param  string $version
	 * @return string
	 */
	public function getFilename($filename, $version = null)
	{
		$versionDir = $this->config["image_versions.$version.upload_dir"];

		// If the upload_dir is the same as the version upload_dir,
		// add "-<version>" between filename and extension.
		if ($version && $versionDir && $versionDir === $this->getPath()) {
			$ext = pathinfo($filename, PATHINFO_EXTENSION);

			return substr($filename, 0, strlen($filename) - strlen($ext) - 1)."-$version.$ext";
		}

		return $filename;
	}

	/**
	 * 	Get unique file name.
	 *
	 * 	@param  string $name
	 *  @return string
	 */
	protected function getUniqueFilename($name)
	{
		$name = empty($name) ? mt_rand() : $this->normalize($name);

		while (file_exists($this->getPath($name))) {
            $name = preg_replace_callback(
	        	'/(?:(?:([\d]+))?(\.[^.]+))?$/',
	        	function ($matches) {
	        		$end  = isset($matches[1]) ? (int) $matches[1] + 1 : 1;
				    $end .= isset($matches[2]) ? $matches[2] : '';
			    	return $end;
	        	},
		    	$name,
		    	1
		    );
        }

        return $name;
	}

	/**
	 * Check if is an accepted file type.
	 *
	 * @param  string $filename
	 * @return bool
	 */
	protected function acceptFileType($filename)
	{
		if ($types = $this->config['accept_file_types']) {
			return preg_match('/\.('.$types.')$/i', $filename) === 1;
		}

		if ($types = $this->config['accept_file_types_regex']) {
			return preg_match($types, $filename) === 1;
		}

		if ($types = $this->config['reject_file_types']) {
			return !(preg_match('/\.('.$types.')$/i', $filename) === 1);
		}

		return true;
	}

	/**
	 * Check is an image file type.
	 *
	 * @param  string $filename
	 * @return bool
	 */
	public function imageFileType($filename)
	{
		return preg_match('/\.('.$this->config['image_file_types'].')$/i', $filename) === 1;
	}

	/**
	 * Check if is an inline file type.
	 *
	 * @param  string $filename
	 * @return bool
	 */
	protected function inlineFileType($filename)
	{
		return preg_match('/\.('.$this->config['inline_file_types'].')$/i', $filename) === 1;
	}

	/**
	 * Get the files inside upload directory.
	 *
	 * @return array
	 */
	public function scanDir()
	{
		$directory = $this->getPath();

		if (! is_dir($directory)) return array();

		$files = array();

		foreach (scandir($directory, $this->config['sorting_order']) as $file) {
			if (is_file($this->getPath($file))) {
				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * 	Get upload directory path.
	 *
	 *  @param 	string $filename
	 *  @param 	string $version
	 *  @return string
	 */
	public function getPath($filename = null, $version = null)
	{
		$dir = $this->config['upload_dir'];
		$versionPath = '';

		if ($version) {
			$versionDir = $this->config["image_versions.$version.upload_dir"];

			if ($versionDir) {
				$dir = $versionDir;
			} else {
				$versionPath = '/'.$version;
			}
		}

		if ($filename) {
			$filename = $this->getFilename($this->normalize($filename), $version);
		}

		return $dir.$versionPath.($filename ? '/'.$filename : '');
	}

	/**
	 * Create a new file download response.
	 *
	 * @param  \SplFileInfo|string $file
	 * @param  string $name
	 * @param  array  $headers
	 * @param  string $disposition
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 *
	 * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
	 */
	public function createFileResponse($file, $name = null, $headers = array(), $disposition = 'attachment')
	{
		$response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

		return $name ? $response->setContentDisposition($disposition, $name) : $response;
	}

	/**
	 * Prepare the file response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\BinaryFileResponse $response
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function prepareFileResponse(BinaryFileResponse $response)
	{
		return $response->prepare(Request::createFromGlobals());
	}

	/**
     * Normalize file name.
     *
     * @param  string $name
     * @return string
     */
    public function normalize($name)
	{
		return trim(basename(stripslashes($name)), ".\x00..\x20");
	}

	/**
	 * Get error message by id/key.
	 *
	 * @param  string $id
	 * @param  array  $parameters
	 * @return string
	 */
	public function getErrorMessage($id, array $parameters = array())
	{
		$message = $this->config->get("messages.$id", $id);
		$parameters = array_merge(array($message), $parameters);

		if (count($parameters) < 2) $parameters[1] = null;

		return call_user_func_array('sprintf', $parameters);
	}

	/**
	 * Get the Intervention Image Manager.
	 *
	 * @return \Intervention\Image\ImageManager
	 */
	public function getImageManager()
	{
		return $this->imageManager;
	}

	/**
	 * Get config instance / get/set config option.
	 *
	 * @param  mixed $key
	 * @param  mixed $default
	 * @return mixed
	 */
	public function config($key = null, $default = null)
	{
		if (is_null($key)) return $this->config;
		if (is_array($key)) return $this->config->set($key);

		return $this->config->get($key, $default);
	}

	/**
	 * Check debug mode.
	 *
	 * @return bool
	 */
	public function debug()
	{
		return $this->config['debug'];
	}

	/**
	 * Set default config options.
	 *
	 * @return void
	 */
	protected function defaultConfig()
	{
		$defaults = array(
			'debug' => false,
			'upload_dir' => null,
			// 'upload_url' => '',
			'script_url' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'],
			'min_file_size' => 1,
			// 'max_file_size' => null,
			// 'accept_file_types' => '',
			// 'accept_file_types_regex' => '',
			'reject_file_types' => 'php|phtml|php3|php5|phps',
			'image_file_types'  => 'gif|jpg|jpeg|png',
			'inline_file_types' => 'gif|jpg|jpeg|png|pdf|txt|js|css',

			// 'max_number_of_files' => null,
			// 'php_download' => false,

			'min_width' => 1,
			'min_height' => 1,
			// 'max_width' => null,
			// 'max_height' => null,

			'image_versions' => array(
				'' => array(
					'auto_orient' => true,
				),
				'thumb' => array(
					'crop' => true,
					'max_width' => 100,
					'max_height' => 100,
				),
			),

			// 'keep_original_image' => false,
			'sorting_order' => 0,
			'mkdir_mode' => 0777,

			'messages' => array(
				'no_file' => 'No file was uploaded.',
				'max_width' => 'Image exceeds maximum width of %d pixels.',
				'min_width' => 'Image requires a minimum width of %d pixels.',
				'max_height' => 'Image exceeds maximum height of %d pixels.',
				'min_height' => 'Image requires a minimum height of %d pixels.',
				'image_resize' => 'Failed to resize image versions (%s).',
				'upload_failed' => 'Failed to upload the file (error %d).',
				'max_file_size' => 'The file exceeds the maximum allowed file size (limit is %d KB).',
				'min_file_size' => 'The file size is too small.',
				'accept_file_types' => 'The file type is not allowed.',
				'max_number_of_files' => 'Maximum number of files exceeded.',
				'error' => 'Oops! Something went wrong.',
				'abort' => 'The operation was aborted.',
				'404' => 'File not found.',
				'401' => 'Unauthorized.',
			),
		);

		foreach ($defaults as $key => $value) {
			if (! $this->config->has($key)) {
				$this->config[$key] = $value;
			}
		}
	}
}
