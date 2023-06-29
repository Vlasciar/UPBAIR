/**
 * This file is part of Filepicker.
 *
 * (c) HazzardWeb <hazzardweb@gmail.com>
 *
 * For the full copyright and license information, please visit:
 * http://codecanyon.net/licenses/standard
 */

+(function($, undefined) {

	/**
	 * Plugin constructor.
	 *
	 * @param {Object} filePicker
	 * @param {Object} options
	 */
	var Plugin = function(filePicker, options) {
		this.filePicker = filePicker;
		this.container = options.container;
		this.options = $.extend({}, Plugin.defaults, options);

		this.init();
	}

	/**
	 * Default plugin options.
	 *
	 * @type {Object}
	 */
	Plugin.defaults = {
		swf: 'assets/js/plugins/webcam.swf',
		swfSize: [568, 423],

		// container: undefined,
		// camera: undefined,
		// closeButton: undefined,
		// snapButton: undefined,
		// openButton: undefined,
		modal: false,
	}

	/**
	 * Initialize plugin.
	 */
	Plugin.prototype.init = function() {
		// Detect UserMedia support.
		navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia ||
								navigator.mozGetUserMedia || navigator.msGetUserMedia;

		// Detect Bootstrap modal support.
		this.options.modal = this.container.hasClass('modal');

		if (! this.options.camera) {
			this.options.camera = this.container.find('.camera')
		}

		if (! this.options.closeButton) {
			this.options.closeButton = this.container.find('.close-webcam');
		}

		if (! this.options.snapButton) {
			this.options.snapButton = this.container.find('.snap');
		}

		// Register events.
		this.options.openButton.on('click', $.proxy(this.open, this));
		this.options.closeButton.on('click', $.proxy(this.close, this));
		this.options.snapButton.on('click', $.proxy(this.snap, this)).prop('disabled', true);

		// Register Bootstrap modal events.
		if (this.options.modal) {
			this.container.on('shown.bs.modal', $.proxy(this.show, this))
						  .on('hidden.bs.modal', $.proxy(this.close, this));
		}
	}

	/**
	 * Open webcam.
	 */
	Plugin.prototype.open = function() {
		if (this.options.modal) {
			this.container.modal('show');
		} else {
			this.container.show();
			this.show();
		}
	}

	/**
	 * Close webcam.
	 */
	Plugin.prototype.close = function() {
		if (this.options.modal) {
			this.container.modal('hide');
		} else {
			this.container.hide();
		}

		if (this._stream) {
			this._stream.stop();
		}

		this.options.camera.html('');
	}

	/**
	 * Show webcam.
	 */
	Plugin.prototype.show = function() {
		var that = this, video;

		if (! (!!navigator.getUserMedia)) {
        	this.options.camera.html(Webcam.getHtml(this.options.swf, this.options.swfSize)).show();

			Webcam.loaded = function() {
				that.options.snapButton.prop('disabled', false);

	        	that._callback = function() {
	        		return Webcam.snap();
	        	}
	        };

	        return;
        }

        this.options.camera.html(video = $('<video autoplay></video>'));

		navigator.getUserMedia({video: true}, function(stream) {
            video.attr('src', window.URL.createObjectURL(that._stream = stream));

            that.options.camera.show();

            that.options.snapButton.prop('disabled', false);

            that._callback = function() {
            	var canvas = document.createElement('canvas'),
                    context = canvas.getContext('2d');

                canvas.width = video[0].videoWidth;
                canvas.height = video[0].videoHeight;
                context.drawImage(video[0], 0, 0);

                return canvas.toDataURL('image/jpeg');
            }

        }, function() {
        	alert('Could not access webcam.');
			that.close();
        });
	}

	/**
	 * Capture & upload picture.
	 *
	 * @param {Object} e
	 */
	Plugin.prototype.snap = function(e) {
		if (! (!!window.Blob)) {
			return alert('Please upgrade your browser.');
		}

		var dataUri = this._callback();

       	if (! dataUri.match(/^data\:image\/(\w+)/)) {
			return alert('Cannot locate image format in Data URI.');
       	}

		var data = dataUri.replace(/^data\:image\/\w+\;base64\,/, ''),
			file = new Blob([this.base64DecToArr(data)], {type: 'image/png'});

		file.name = new Date().getTime()+'.png';
		file.autoUpload = true;

		this.filePicker.onAdd($.Event, [file]);

		this.close();
	}

	/**
	 * Convert base64 encoded character to 6-bit integer.
	 *
	 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Base64_encoding_and_decoding
	 */
	Plugin.prototype.b64ToUint6 = function(nChr) {
		return nChr > 64 && nChr < 91 ? nChr - 65
			: nChr > 96 && nChr < 123 ? nChr - 71
			: nChr > 47 && nChr < 58 ? nChr + 4
			: nChr === 43 ? 62 : nChr === 47 ? 63 : 0;
	}

	/**
	 * Convert base64 encoded string to Uintarray.
	 *
	 * https://developer.mozilla.org/en-US/docs/Web/JavaScript/Base64_encoding_and_decoding
	 */
	Plugin.prototype.base64DecToArr = function(sBase64, nBlocksSize) {
		var sB64Enc = sBase64.replace(/[^A-Za-z0-9\+\/]/g, ""), nInLen = sB64Enc.length,
			nOutLen = nBlocksSize ? Math.ceil((nInLen * 3 + 1 >> 2) / nBlocksSize) * nBlocksSize : nInLen * 3 + 1 >> 2,
			taBytes = new Uint8Array(nOutLen);

		for (var nMod3, nMod4, nUint24 = 0, nOutIdx = 0, nInIdx = 0; nInIdx < nInLen; nInIdx++) {
			nMod4 = nInIdx & 3;
			nUint24 |= this.b64ToUint6(sB64Enc.charCodeAt(nInIdx)) << 18 - 6 * nMod4;

			if (nMod4 === 3 || nInLen - nInIdx === 1) {
				for (nMod3 = 0; nMod3 < 3 && nOutIdx < nOutLen; nMod3++, nOutIdx++) {
					taBytes[nOutIdx] = nUint24 >>> (16 >>> nMod3 & 24) & 255;
				}
				nUint24 = 0;
			}
		}

		return taBytes;
	}

	/**
	 * Global export.
	 *
	 * @param  {Object} filePicker
	 * @param  {Object} options
	 * @return {Object}
	 */
	window.FilepickerWebcam = function(filePicker, options) {
		if (filePicker instanceof $) {
			filePicker = filePicker.filePicker();
		}

		return new Plugin(filePicker, options);
	}
})(jQuery);

/**
 * JPEGCam
 * Joseph Huckaby
 * https://code.google.com/p/jpegcam/
 */
window.Webcam = {
	isLoaded: false,
	loaded: null,
	complete: null,
	error: null,

	getHtml: function(swf, swfSize) {
		return '<object id="webcam_movie_obj" type="application/x-shockwave-flash" width="'+swfSize[0]+'" height="'+swfSize[1]+'"><param name="allowScriptAccess" value="always"/><param name="allowFullScreen" value="false"/><param name="movie" value="'+swf+'"/><param name="wmode" value="transparent"/><param name="flashvars" value="width='+swfSize[0]+'&height='+swfSize[1]+'&dest_width='+(swfSize[0]*1.5)+'&dest_height='+(swfSize[1]*1.5)+'&image_format=jpeg&jpeg_quality=100&force_flash=false">'+
		'</object>';
	},

	snap: function() {
		if (! this.isLoaded) {
			return alert('JPEGCam ERROR: Movie is not loaded yet.');
		}

		var movie = document.getElementById('webcam_movie_obj'),
			data = '';

		if (! movie) {
			alert('JPEGCam ERROR: Cannot locate movie #webcam_movie_obj in DOM');
		}

		try {
			data = 'data:image/jpeg;base64,' + movie._snap();
		} catch (e) {

		}

		return data;
	},

	flashNotify: function(type, msg) {
		switch (type) {
			case 'flashLoadComplete':
				this.isLoaded = true;
			break;

			case 'cameraLive':
				this.loaded();
			break;

			case 'error':
				this.error(msg);
			break;
		}
	}
};
