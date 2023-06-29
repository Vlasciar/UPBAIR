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

class Event
{
	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var bool
	 */
	protected $defaultPrevented = false;

	/**
	 * @var mixed
	 */
	protected $file;

	/**
	 * @var \Intervention\Image\Image|null
	 */
	protected $image;

	/**
	 * @var bool
	 */
	protected $abort = false;

	/**
	 * @var string
	 */
	protected $error = null;

	/**
	 * @var string
	 */
	protected $filename = null;

	/**
	 * @var mixed
	 */
	protected $data = null;

	/**
	 * Create a new event instance.
	 *
	 * @param mixed $file
	 * @param \Intervention\Image\Image|null $image
	 */
	public function __construct($file = null, $image = null)
	{
		$this->file = $file;
		$this->image = $image;
	}

	/**
	 * Get file.
	 *
	 * @return mixed
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * Get image.
	 *
	 * @return \Intervention\Image\Image|null
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * Abort upload.
	 *
	 * @param  string $error
	 * @return void
	 */
	public function abort($error = null)
	{
		$this->abort = true;
		$this->error = $error;
	}

	/**
	 * Get error message.
	 *
	 * @param  string $default
	 * @return string
	 */
	public function getError($default = '')
	{
		return $this->error ?: $default;
	}

	/**
	 * Set error message.
	 *
	 * @param  string $error
	 * @return void
	 */
	public function setError($error)
	{
		$this->error = $error;
	}

	/**
	 * Set filename.
	 *
	 * @param  string $filename
	 * @param  bool   $originalExtension
	 * @return void
	 */
	public function setFilename($filename, $originalExtension = true)
	{
		$this->filename = $filename;

		if ($originalExtension) {
			$this->filename .= '.'.$this->file->getClientOriginalExtension();
		}
	}

	/**
	 * Get filename.
	 *
	 * @return string|null
	 */
	public function getFilename()
	{
		return $this->filename;
	}

	/**
	 * Set custom data.
	 *
	 * @param  mixed $data
	 * @return void
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * Get custom data.
	 *
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @return bool
	 */
	public function aborted()
	{
		return $this->abort;
	}

	/**
	 * Get the event type.
	 *
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Set the event type.
	 *
	 * @param  string $type
	 * @return void
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * Check whether preventDefault() method was called.
	 *
	 * @return bool
	 */
	public function isDefaultPrevented()
	{
		return $this->defaultPrevented;
	}

	/**
	 * Prevent events with the same type to be fired.
	 *
	 * @return void
	 */
	public function preventDefault()
	{
		$this->defaultPrevented = true;
	}
}
