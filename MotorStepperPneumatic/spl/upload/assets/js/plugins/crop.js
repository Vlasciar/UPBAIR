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
		aspectRatio: 0,
		minSize: [0, 0],
	    maxSize: [0, 0],
	    setSelect: undefined,

	    // container: undefined,
		// preview: undefined,
		// cropBtn: undefined,
		// saveBtn: undefined,
		// cancelBtn: undefined,
		modal: false,
	}

	/**
	 * Initialize plugin.
	 */
	Plugin.prototype.init = function() {
		// Detect Bootstrap modal support.
		this.options.modal = this.container.hasClass('modal');

		if (! (!!$.Jcrop)) {
			return alert('Jcrop not loaded.');
		}


		if (! this.options.preview) {
			this.options.preview = this.container.find('.crop-preview');
		}

		if (! this.options.saveBtn) {
			this.options.saveBtn = this.container.find('.save');
		}

		if (! this.options.cancelBtn) {
			this.options.cancelBtn = this.container.find('.cancel');
		}

		if (this.options.cropBtn) {
			this.options.cropBtn.on('click', $.proxy(this.open, this));
		} else {
			this.filePicker.options.filesList.on('click', '.crop', $.proxy(this.open, this));
		}

		this.options.saveBtn.on('click', $.proxy(this.save, this)).prop('disabled', true);
		this.options.cancelBtn.on('click', $.proxy(this.close, this));

		// Register Bootstrap modal events.
		if (this.options.modal) {
			this.container.on('shown.bs.modal', $.proxy(this.show, this))
						  .on('hidden.bs.modal', $.proxy(this.close, this));
		}
	}

	/**
	 * Open crop.
	 *
	 * @param {Object} e
	 */
	Plugin.prototype.open = function(e) {
		if (this.options.modal) {
			this.container.modal('show', e);
		} else {
			this.container.show();
			this.show(e);
		}
	}

	/**
	 * Show crop.
	 *
	 * @param {Object} e
	 */
	Plugin.prototype.show = function(e) {
		var file,
			target = $((e.relatedTarget ? e.relatedTarget.currentTarget : false) || e.currentTarget);

		if (target.data('file')) {
			file = target.data('file');
		} else {
			file = target.closest('.template-download').data('file');
		}

		if (! file) return alert('Cannot read .data("file").');

		var that = this,

		updateCoords = function(coords) {
			coords = coords || {};

			that.coords = {
				x: coords.x,
				y: coords.y,
				file: file.name,
				width: coords.w,
				height: coords.h,
			};
		},

		options = {
			onChange: updateCoords,
			onRelease: updateCoords,
			bgColor: 'white',
			aspectRatio: this.options.aspectRatio,
  	     	setSelect: this.options.setSelect,
  	    	minSize: this.options.minSize,
     	   	maxSize: this.options.maxSize,
     	   	trueSize: [file.width, file.height],
		}

		updateCoords();
		this.file = file;

		var image = new Image();

    	image.onload = function() {
    		file.url += (file.url.indexOf('?') > -1 ? '&' : '?') + new Date().getTime();
    		var img = $('<img src="'+file.url+'" style="visibility:hidden;">');
			that.options.preview.html(img).show();
            img.Jcrop(options);
            that.options.saveBtn.prop('disabled', false);
        }

        image.onerror = function() {
			alert('Image load error.');
        }

        image.src = file.url;
	}

	/**
	 * Hide crop.
	 */
	Plugin.prototype.close = function() {
		if (this.options.modal) {
			this.container.modal('hide');
		} else {
			this.container.hide();
		}

		this.options.preview.html('');
	}

	/**
	 * Save cropped image.
	 *
	 * @param {Object} e
	 */
	Plugin.prototype.save = function(e) {
		var that = this;

		this.toggleBtn();

		$.ajax({
			url: this.filePicker.options.url,
			type: 'POST',
			dataType: 'json',
			data: this.filePicker.formData().method('PUT').add(this.coords),
		})
		.done($.proxy(this.saveComplete, this))
		.fail(function() {
			alert('Save error.');
		})
		.always($.proxy(this.toggleBtn, this));
	}

	/**
	 * Save complete callback.
	 *
	 * @param {Object} file
	 */
	Plugin.prototype.saveComplete = function(result) {
		var file = result.file;

		if (file.versions.thumb) {
			file.versions.thumb.url += (file.versions.thumb.url.indexOf('?') > -1 ? '&' : '?') + new Date().getTime();
		}

		if (this.filePicker.options.filesList) {
			this.filePicker.render($.extend({original: this.file}, file));
		}

		this.filePicker.trigger('cropsuccess', null, file);

		this.close();
	}

	/**
	 * Toggle save button.
	 */
	Plugin.prototype.toggleBtn = function() {
		var btn = this.options.saveBtn;

		if (!! $.fn.button) {
			// Bootstrap button support.
			if (btn.button().data('bs.button').isLoading) {
				btn.button('reset');
			} else {
				btn.button('loading');
			}
        } else {
        	btn.prop('disabled', ! btn.prop('disabled'));
        }
	}

	/**
	 * Global export.
	 *
	 * @param  {Object} filePicker
	 * @param  {Object} options
	 * @return {Object}
	 */
	window.FilepickerCrop = function(filePicker, options) {
		if (filePicker instanceof $) {
			filePicker = filePicker.filePicker();
		}

		return new Plugin(filePicker, options);
	}
})(jQuery);
