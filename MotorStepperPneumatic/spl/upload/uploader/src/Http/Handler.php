<?php

/**
 * This file is part of Filepicker.
 *
 * (c) HazzardWeb <hazzardweb@gmail.com>
 *
 * For the full copyright and license information, please visit:
 * http://codecanyon.net/licenses/standard
 */

namespace Hazzard\Filepicker\Http;

use Exception;
use Hazzard\Filepicker\Uploader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\JsonResponse;
use Intervention\Image\Exception\NotSupportedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class Handler
{
	/**
	 * The uploader instance.
	 *
	 * @var \Hazzard\Filepicker\Uploader
	 */
	public $uploader;

	/**
	 * The events array.
	 *
	 * @var array
	 */
	protected $events = array();

	/**
	 * Create a new handler instance.
	 *
	 * @param \Hazzard\Filepicker\Uploader $uploader
	 */
	public function __construct(Uploader $uploader)
	{
		$this->uploader = $uploader;
	}

	/**
	 * Handle an incoming HTTP request.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function handle(Request $request)
	{
		$method = $request->get('_method', $request->getMethod());

		try {
			return $this->{$method.'Action'}($request);
		} catch (Exception $e) {
			if ($e instanceof NotSupportedException) {
				return $this->response($e->getMessage(), 500);
			}

			if ($this->uploader->debug()) throw $e;

			return $this->response($this->uploader->getErrorMessage('error'), 500);
		}
	}

	/**
	 * Handle GET request. Get uploaded files.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function getAction(Request $request)
	{
		if ($request->get($this->getSingularParamName())) {
			return $this->download($request);
		}

		$offset = intval($request->get('offset'));
		$limit  = intval($request->get('limit'));
		$files  = $this->uploader->get($offset, $limit);
		$total  = $this->uploader->getTotal();

		$this->fire('files.get', array(&$files, &$total));

		foreach ($files as $index => $file) {
			$this->fire('file.get', $event = new Event($file));

			if ($event->aborted()) {
				unset($files[$index]);
				$total--;
				continue;
	    	}

	    	$files[$index] = $this->fileToArray($file, null, $event->getData());
		}

		return $this->json(compact('files', 'total'));
	}

	/**
	 * Download file.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function download($request)
	{
		$filename = $this->getFilenameParam($request);

		try {
			$file = $this->uploader->createFile($filename, $request->get('version'));
		} catch (FileNotFoundException $e) {
			return $this->response($this->uploader->getErrorMessage('404'), 404);
		}

		$this->fire('file.download', $event = new Event($file));

		if ($event->aborted()) {
			return $this->response($event->getError($this->uploader->getErrorMessage('401')), 401);
		}

		return $this->uploader->download($file);
	}

	/**
	 * Handle POST request. Upload file(s).
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function postAction(Request $request)
	{
		$files = $request->files->get(
			$this->getParamName(),
			$request->files->get($this->getSingularParamName(), array())
		);

		if (! is_array($files)) $files = array($files);

		$response = array();

		foreach ($files as $file) {
			$response[] = $this->upload($file);
		}

		return $this->json(array('files' => $response), 201);
	}

	/**
	 * Upload file.
	 *
	 * @param  \Symfony\Component\HttpFoundation\File\UploadedFile $file
	 * @return array
	 */
	protected function upload($file)
	{
		$this->fire('upload.before', $event = new Event($file));

		if ($event->aborted()) {
			return $this->fileToArray(
				$file,
				$event->getError($this->uploader->getErrorMessage('abort')),
				$event->getData()
			);
		}

		try {
			$file = $this->uploader->upload($file, $event->getFilename());
		} catch (UploadException $e) {
			$event = new Event($file);
			$event->setError($e->getMessage());

			$this->fire('upload.fail', $event);
			$error = $event->getError($e->getMessage());
		}

		if ($file instanceof File) {
			$this->fire('upload.success', $event = new Event($file));

			if ($event->aborted()) {
				$this->uploader->delete($file->getFilename());
				$error = $event->getError($this->uploader->getErrorMessage('abort'));
			}
		}

		return $this->fileToArray($file, @$error, $event->getData());
	}

	/**
	 * Handle PUT request. Crop image.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function putAction(Request $request)
	{
		$filename = $this->getFileNameParam($request);
		$options = $request->get('options', array());

		try {
			$file = $this->uploader->createFile($filename);
		} catch (FileNotFoundException $e) {
			return $this->json($this->uploader->getErrorMessage('404'), 404);
		}

		$image = $this->uploader->getImageManager()->make($file);

		$this->fire('crop.before', $event = new Event($file, $image));

		if ($event->aborted()) {
			return $this->json($event->getError($this->uploader->getErrorMessage('abort')));
		}

		$x = (int) $request->get('x');
		$y = (int) $request->get('y');
		$width  = (int) $request->get('width');
		$height = (int) $request->get('height');

		if (! empty($width) && ! empty($height)) {
			if ($this->uploader->config('keep_original_image')) {
				$tempImage = clone $image;
			}

			$quality = $this->uploader->config('image_versions..quality');

			$image->crop($width, $height, $x, $y)->save($file, $quality);

			$this->uploader->createImageVersions($event->getFilename() ?: $file->getFilename());

			if ($this->uploader->config('keep_original_image')) {
				$tempImage->save();
			}
		}

		$this->fire('crop.after', $event);

		$file = $this->fileToArray($file, null, $event->getData());

		return $this->json(compact('file'));
	}

	/**
	 * Handle DELETE request. Delete file(s).
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function deleteAction(Request $request)
	{
		$filenames = $this->getFilenamesParams($request);
		$response  = array();

		foreach ($filenames as $filename) {
			try {
				$file = $this->uploader->createFile($filename);
			} catch (FileNotFoundException $e) {
				$response[$filename] = false;
				continue;
			}

			$this->fire('file.delete', $event = new Event($file));

			if (! $event->aborted()) {
				$response[$filename] = $this->uploader->delete($file);
			}
		}

		return $this->json($response, 204);
	}

	/**
	 * Convert a file instance into array.
	 *
	 * @param  mixed  $file
	 * @param  string $error
	 * @param  mixed  $customData
	 * @return array
	 */
	protected function fileToArray($file, $error = null, $customData = null)
	{
		if ($file instanceof UploadedFile) {
			$data = array(
				'name'  => $file->getClientOriginalName(),
				'size'  => $file->getClientSize(),
				'type'  => $file->getClientMimeType(),
				'error' => $error,
				'extension' => $file->getClientOriginalExtension()
			);
		} elseif ($error) {
			$data = array(
				'name'  => $file->getFilename(),
				'error' => $error,
				'extension' => $file->getExtension()
			);
		} else {
			$data = array(
				'name' => $file->getFilename(),
				'url'  => $this->getFileUrl($file->getFilename()),
				'size' => $file->getSize(),
				'time' => $file->getMTime(),
				'type' => $file->getMimeType(),
				'extension' => $file->getExtension(),
			);

			if ($this->uploader->imageFileType($data['name'])) {
				$size = getimagesize($file);

				$data['width'] = $size[0];
				$data['height'] = $size[1];
				$data['versions'] = $this->getImageVersions($data['name']);
			}
		}

		if ($customData) $data['data'] = $customData;

		return $data;
	}

	/**
	 * @param  string $filename
	 * @return array
	 */
	protected function getImageVersions($filename)
	{
		$versions = array();

		foreach ($this->uploader->getImageVersions($filename) as $version => $file) {
			$size = getimagesize($file);

			$versions[$version] = array(
				'name' => $file->getFilename(),
				'url'  => $this->getFileUrl($file->getFilename(), $version),
				'size' => $file->getSize(),
				'width'  => $size[0],
				'height' => $size[1],
			);
		}

		return $versions;
	}

	/**
     * Get file name param.
     *
     * @param  \Symfony\Component\HttpFoundation\Request $request
     * @return string|null
     */
    protected function getFilenameParam($request)
    {
		$params = $this->getFilenamesParams($request);

		return is_array($params) && count($params) ? $params[0] : null;
    }

    /**
     * Get file names params.
     *
     * @param  \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
	protected function getFilenamesParams($request)
	{
		$params = $request->get(
			$this->getParamName(),
			$request->get($this->getSingularParamName(), array())
		);

		return is_array($params) ? $params : array($params);
    }

    /**
     * Get singular param name.
     *
     * @return string
     */
	protected function getSingularParamName()
	{
		return substr($this->getParamName(), 0, -1);
	}

	/**
     * Get param name.
     *
     * @return string
     */
	protected function getParamName()
	{
		return $this->uploader->config('param_name', 'files');
	}

	/**
	 * Get file url.
	 *
	 * @param  string $filename
	 * @param  string $version
	 * @return string
	 */
	protected function getFileUrl($filename, $version = null)
	{
		$filename = rawurlencode($filename);
		$version  = rawurlencode($version);

		if ($this->uploader->config('php_download')) {
			$url  = $this->uploader->config('script_url');
			$url .= strpos($url, '?') === false ? '?' : '&';
			$url .= $this->getSingularParamName().'='.$filename;
			$url .= $version ? '&version='.$version : '';

			return $url;
		}

		if (! empty($version)) {
			if ($url = $this->uploader->config("image_versions.$version.upload_url")) {
				return $url.$this->uploader->getFilename($filename, $version);
			}

			return $this->uploader->config('upload_url').'/'.$version.'/'.$filename;
		}

		return $this->uploader->config('upload_url').'/'.$filename;
	}

	/**
	 * Register an event listener.
	 *
	 * @param  string|array    $event
	 * @param  \Closure|string $listener
	 * @return void
	 */
	public function on($type, $listener)
	{
		foreach ((array) $type as $value) {
			$this->events[] = array($value, $listener);
		}
	}

	/**
	 * Fire an event and call the listener.
	 *
	 * @param  string $type
	 * @param  mixed  $payload
	 * @return mixed
	 */
	public function fire($type, $payload)
	{
		$event = $payload;

		if (! is_array($payload)) {
			$payload = array($payload);
		}

		if ($event instanceof Event) {
			$event->setType($type);
		}

		$response = null;

		foreach ($this->events as $value) {
			if ($value[0] === $type) {
				$response = call_user_func_array($value[1], $payload);

				if ($event instanceof Event && $event->isDefaultPrevented()) {
					return $response;
				}
			}
		}

		return $response;
	}

	/**
	 * Get the uploader instance.
	 *
	 * @return \Hazzard\Filepicker\Uploader
	 */
	public function getUploader()
	{
		return $this->uploader;
	}

	/**
	 * Create a new json response.
	 *
	 * @param  mixed $data
	 * @param  int   $status
	 * @param  array $headers
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function json($data = null, $status = 200, array $headers = array())
	{
		return new JsonResponse($data, $status, $headers);
	}

	/**
	 * Create a new response.
	 *
	 * @param  mixed $data
	 * @param  int   $status
	 * @param  array $headers
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function response($data = null, $status = 200, array $headers = array())
	{
		return new Response($data, $status, $headers);
	}
}
