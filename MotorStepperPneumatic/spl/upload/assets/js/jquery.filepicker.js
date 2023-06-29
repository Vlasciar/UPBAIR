/**
 * This file is part of Filepicker.
 *
 * (c) HazzardWeb <hazzardweb@gmail.com>
 *
 * For the full copyright and license information, please visit:
 * http://codecanyon.net/licenses/standard
 */

+(function($, undefined) {
	'use strict';

	/**
	 * Filepicker public class definition.
	 *
	 * @param {Object} element
	 * @param {Array}  options
	 */
	var Filepicker = function(element, options) {
		this.element = $(element);
		this.options = $.extend({}, Filepicker.defaults, options);

		this.initOptions();
		this.initEventHandlers();
	}

	/**
	 * Default options.
	 *
	 * @type {Object}
	 */
	Filepicker.defaults = {
		debug: false,
		url: undefined,
		autoUpload: true,
		autoLoad: false,
		prependFiles: true,

		acceptFileTypes: undefined,
		//acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
		imageFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,

		minFileSize: 1,
		maxFileSize: undefined,
		maxNumberOfFiles: undefined,

		formData: [],

		paramName: 'files',

		previewThumbnailSize: [64, 64],

		messages: {
			acceptFileTypes: 'The file type is not allowed.',
			maxFileSize: 'The file exceeds the maximum allowed file size.',
			minFileSize: 'The file size is too small.',
			maxNumberOfFiles: 'Maximum number of files exceeded.',
			error: 'Oops! Something went wrong.',
			browser: 'Please upgrade your browser!',
		},

		fileInput: undefined,
		startBtn:  undefined,
		cancelBtn: undefined,
		deleteBtn: undefined,
		filesList: undefined,
		dropZone: $(document),
		dropWindow: undefined,

		uploadTemplateId: 'templateUpload',
		downloadTemplateId: 'templateDownload',

		selectors: {
			cancel: '.cancel',
			start: '.start',
			destroy: '.delete',
			preview: '.preview',
			error: '.error',
			progressbar: '.progress-bar'
		},
	}

    /**
     * Initialize options.
     */
	Filepicker.prototype.initOptions = function() {
		var o = this.options;

		if (! o.fileInput) {
			o.fileInput = this.element.is('input[type="file"]') ?
							this.element :
							this.element.find('input[type="file"]');
		}

		if (! o.filesList) {
			o.filesList = this.element.find('.files');
			if (!o.filesList.length) {
				o.filesList = undefined;
			}
		}

		if (! o.startBtn) {
			o.startBtn = this.element.find('.start');
		}

		if (! o.cancelBtn) {
			o.cancelBtn = this.element.find('.cancel');
		}

		if (! o.deleteBtn) {
			o.deleteBtn = this.element.find('.delete');
		}

		if (! o.dropWindow) {
			o.dropWindow = this.element.find('.drop-window');
		}
	}

	/**
	 * Initialize event handlers.
	 */
	Filepicker.prototype.initEventHandlers = function() {
		var o = this.options;

		this.on(o.fileInput, 'change', this.onChange);

		this.on(o.dropZone, 'dragover', this.onDragOver);
		this.on(o.dropZone, 'dragenter', this.onDragEnter);
		this.on(o.dropZone, 'dragleave', this.onDragLeave);
		this.on(o.dropZone, 'drop', this.onDrop);

		this.on(o.filesList, 'click', o.selectors.start, this.onStart);
		this.on(o.filesList, 'click', o.selectors.cancel, this.onCancel);
		this.on(o.filesList, 'click', o.selectors.destroy, this.onDestroy);

		this.on(o.startBtn, 'click', this.onStartAll);
		this.on(o.cancelBtn, 'click', this.onCancelAll);
		this.on(o.deleteBtn, 'click', this.onDestroyAll);

		if (o.autoLoad) {
			this.autoLoad();
		}
	}

	Filepicker.prototype.autoLoad = function(limit, offset) {
		var that = this,
			data = this.formData().add({limit: limit, offset: offset});

		$.get(this.options.url, data, function(result) {
			that.trigger('load', null, result);
		}, 'json');
	}

	/**
	 * Default "load" event.
	 *
	 * @param {Object} e
	 * @param {Object} result
	 */
	Filepicker.prototype.load = function(e, result) {
		if (! e.isDefaultPrevented()) {
			var that = this;
			$.each(result.files, function(i, file) {
				that.render(file);
			});
		}
	}

	/**
	 * On file input change event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onChange = function(e) {
		if (! (!!(window.File && window.FileList && window.FormData))) {
			return alert(this.trans('browser'));
		}

		var files = this.getInputFiles($(e.target));

		this.replaceFileInput($(e.target));

		this.onAdd(e, files);
	}

	/**
	 * Start upload event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onStart = function(e) {
		e.preventDefault();

		var button = $(e.currentTarget),
			item = button.closest('.template-upload'),
			file = item.data('file');

		button.prop('disabled', true);

		if (file && file.send) {
			this.trigger('start', e, file);
		}
	}

	/**
	 * Start all upload event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onStartAll = function(e) {
		e.preventDefault();

		this.trigger('startall', e);
	}

	/**
	 * Cancel upload event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onCancel = function(e) {
		e.preventDefault();

		var item = $(e.currentTarget).closest('.template-upload,.template-download'),
			file = item.data('file') || {};

		file.context = file.context || item;

		if (file.abort) {
			file.abort();
		} else {
			file.errorThrown = 'abort';
			this.trigger('fail', e, file);
		}
	}

	/**
	 * Cancel all upload event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onCancelAll = function(e) {
		e.preventDefault();

		this.options.filesList.find(this.options.selectors.cancel).click();
	}

	/**
	 * Delete file event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onDestroy = function(e) {
		e.preventDefault();

		var item = $(e.currentTarget).closest('.template-download');

		this.trigger('destroy', e, $.extend({context: item}, item.data('file')));
    }

    /**
	 * Delete all files event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onDestroyAll = function(e) {
		e.preventDefault();

		this.trigger('destroyall', e);
	}

	/**
	 * On file drag event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onDragOver  = getDragHandler('dragover');
	Filepicker.prototype.onDragEnter = getDragHandler('dragenter');
	Filepicker.prototype.onDragLeave = getDragHandler('dragleave');

	/**
	 * On file drop event handler.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.onDrop = function(e) {
		e.dataTransfer = e.originalEvent && e.originalEvent.dataTransfer;

		var dataTransfer = e.dataTransfer,
			files = {};

		if (dataTransfer && dataTransfer.files && dataTransfer.files.length) {
			e.preventDefault();

			files = $.makeArray(dataTransfer.files);

			if (this.trigger('drop', $.Event('drop', {delegatedEvent: e, files: files}), files) !== false) {
				this.onAdd(e, files);
			}
		}
	}

	/**
	 * On file add event handler.
	 *
	 * @param {Object} e
	 * @param {Object} files
	 */
	Filepicker.prototype.onAdd = function(e, files) {
		var that = this;

		$.each(files, function(i, file) {
			file.sizeFormatted = that.formatFilesize(file.size);
			file.extension = that.getFileExtension(file.name);
			file.imageFileType = that.options.imageFileTypes.test(file.name);

			that.validate(file);

			that.addMethods(e, file);

			that.trigger('add', $.Event('add', {delegatedEvent: e}), file);
		});
	}

	/**
	 * On file send event handler.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.onSend = function(e, file) {
		if (this.trigger('send', $.Event('send', {delegatedEvent: e}), file) === false) {
			return this.onFail(0, file.errorThrown || 'abort', file);
		}

		this.upload(file);
	}

	/**
	 * On upload progress event handler.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.onProgress = function(e, file) {
		file.progress = {loaded: e.loaded, total: e.total};
		this.trigger('progress', $.Event('progress', {delegatedEvent: e}), file);
	}

	/**
	 * On file upload done event handler.
	 *
	 * @param {Object} result
	 * @param {Number} textStatus
	 * @param {Object} file
	 */
	Filepicker.prototype.onSuccess = function(result, file) {
		if (result && result.files.length) {
			file = $.extend({original: file}, result.files[0]);

			this.extraProps(file);

			this.trigger('success', null, file);
		}
	}

	/**
	 * On upload fail event handler.
	 *
	 * @param {Number} textStatus
	 * @param {String} errorThrown
	 * @param {Object} file
	 */
	Filepicker.prototype.onFail = function(status, errorThrown, file) {
		file.status = status;
		file.errorThrown = errorThrown;

        this.trigger('fail', null, file);
	}

	/**
	 * On upload complete event handler.
	 *
	 * @param {Number} textStatus
	 * @param {Object} jqXHR
	 * @param {Object} file
	 */
	Filepicker.prototype.onAlways = function(xhr, file) {
		file.xhr = xhr;

		this.trigger('always', null, file);
	}

	/**
	 * Default "add" event.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.add = function(e, file) {
		if (e.isDefaultPrevented()) {
			return false;
		}

		if (this.options.filesList) {
			file.context = this.renderTemplate(file, this.options.uploadTemplateId).data('file', file);
			this.renderThumbnailPreview(file);
			this.options.filesList[this.options.prependFiles ? 'prepend' : 'append'](file.context);
			this.transition(file.context);
		}

		if ((this.options.autoUpload || file.autoUpload) && ! file.error) {
			file.send();
		}
	}

	/**
	 * Default "drop" event.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.drop = function(e, file) {
		if (! e.isDefaultPrevented()) {
			this.transition(this.options.dropWindow.hide());
		}
	}

	/**
	 * Default "dragover" event.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.dragover = function(e) {
		if (! e.isDefaultPrevented()) {
			this.options.dropWindow.show().addClass('in');
		}
	}

	/**
	 * Default "dragleave" event.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.dragleave = function(e) {
		if (! e.isDefaultPrevented()) {
			this.options.dropWindow.hide().removeClass('in');
		}
	}

	/**
	 * Default "start" event.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.start = function(e, file) {
		if (e.isDefaultPrevented()) {
			if (file.context) {
				file.context.find(this.options.selectors.start).prop('disabled', false);
			}
		} else {
			file.send();
		}
	}

	/**
	 * Default "startall" event.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.startall = function(e, files) {
		if (! e.isDefaultPrevented() && this.options.filesList) {
			this.options.filesList.find(this.options.selectors.start).click();
		}
	}

	/**
	 * Default "progress" event.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.progress = function(e, file) {
		if (e.isDefaultPrevented() || ! file.context) return false;

		var percentage  = Math.floor((file.progress.loaded / file.progress.total) * 100);
		file.context.find(this.options.selectors.progressbar)
					.text(percentage + '%').css('width', percentage + '%');
	}

	/**
	 * Default "done" event.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.success = function(e, file) {
		if (! e.isDefaultPrevented()) {
			this.render(file);
		}
	}

	/**
	 * Default "fail" event.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.fail = function(e, file) {
		if (e.isDefaultPrevented()) {
			return false;
		}

		if (file.errorThrown === 'abort') {
			this.transition(file.context, function(el) {
				el.remove();
			});
		} else {
			file.context.find(this.options.selectors.error).text(this.trans(file.errorThrown));
			file.context.find(this.options.selectors.start).prop('disabled', false);
			file._state = false;
		}
	}

	/**
	 * Default "destroy" event.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.destroy = function(e, file) {
		if (e.isDefaultPrevented()) {
			return false;
		}

		var that = this,
		data = this.formData().method('DELETE').add(this.getSingularParamName(), file.name);

		$.ajax({
			url: this.options.url,
			type: 'POST',
			dataType: 'json',
			data: data
		})
		.done(function() {
			that.trigger('destroyed', e, file);
		})
		.fail(function() {
			that.trigger('destroyfailed', e, file);
		});
	}

	/**
	 * Default "destroyall" event.
	 *
	 * @param {Object} e
	 */
	Filepicker.prototype.destroyall = function(e) {
		if (! e.isDefaultPrevented()) {
			this.options.filesList.find(this.options.selectors.destroy).click();
		}
	}

	/**
	 * Default "destroyed" event.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.destroyed = function(e, file) {
		this.transition(file.context, function(el) {
			el.remove();
		});
	}

	/**
	 * Upload file.
	 *
	 * @param {Object} file
	 */
	Filepicker.prototype.upload = function(file) {
		var that = this,
			xhr = file.xhr = $.ajaxSettings.xhr();

		xhr.open('POST', this.options.url, true);

		xhr.upload.onprogress = function(e) {
			if (e.lengthComputable) {
		      	that.onProgress($.Event('progress', {loaded: e.loaded, total: e.total}), file);
		 	}
		}

		xhr.onload = function() {
			if (xhr.status == 200 || xhr.status == 201) {
				try {
	               that.onSuccess($.parseJSON(xhr.responseText), file);
	            } catch (e) {
	            }
			} else {
				that.log(xhr.responseText);
            	that.onFail(xhr.status, that.trans('error'), file);
			}

			that.onAlways(xhr, file);
		}

		var formData = new FormData();

		$.each(file.formData, function(index, field) {
			formData.append(field.name, field.value);
		});

		formData.append(this.options.paramName, file, file.name);

		xhr.send(formData);
	}

	/**
	 * Add methods to file.
	 *
	 * @param {Object} e
	 * @param {Object} file
	 */
	Filepicker.prototype.addMethods = function(e, file) {
		var that = this;

		file.abort = function() {
			if (this.xhr) {
				this.xhr.abort();
			}

			that.onFail(0, 'abort', this);
		}

		file.send = function() {
			if (this.state() === 'sending') {
				return false;
			}

			this._state = 'sending';

			if (! this.error) {
				that.onSend(e, this);
			}
		}

		file.state = function() {
			return this._state ? this._state : false;
		}

		file.formData = this.formData().method('POST');
	}

	/**
	 * Validate file.
	 *
	 * @param  {Object} file
	 * @return {Boolean}
	 */
	Filepicker.prototype.validate = function(file) {
    	var o = this.options;

    	if (o.acceptFileTypes && ! o.acceptFileTypes.test(file.name)) {
			file.error = this.trans('acceptFileTypes');
		} else if (file.size !== undefined && o.maxFileSize && file.size > o.maxFileSize) {
			file.error = this.trans('maxFileSize');
		} else if (file.size !== undefined && o.minFileSize && file.size < o.minFileSize) {
			file.error = this.trans('minFileSize');
		} else if (o.maxNumberOfFiles && this.getNumberOfFiles() >= o.maxNumberOfFiles) {
			file.error = this.trans('maxNumberOfFiles');
        }

        return file.error === undefined;
    }

    /**
     * Render template.
     *
     * @param  {Object} file
     * @param  {String} templateId
     * @return {String}
     */
	Filepicker.prototype.renderTemplate = function(file, templateId) {
		if (typeof tmpl === undefined) {
			throw new Error('Filepicker requires tmpl.js');
		}

		return $(tmpl(templateId, {file: file, options: this.options}));
	}

	/**
	 * Render file.
	 *
	 * @param {Object} file
	 */
	Filepicker.prototype.render = function(file) {
		this.extraProps(file);

		var template = this.renderTemplate(file, this.options.downloadTemplateId);

		file.context = template;

		template.data('file', file);

		if (file.original) {
			var that = this;
			this.transition(file.original.context, function(el) {
				el.replaceWith(template);
				that.transition(template);
			});
		} else {
			this.options.filesList.append(template);
			this.transition(template);
		}
	}

	/**
	 * Render image thumbnail preview.
	 *
	 * @param {Object} file
	 */
	Filepicker.prototype.renderThumbnailPreview = function(file) {
    	if (! file.imageFileType) return;

    	var that = this,
    		canvas  = document.createElement('canvas'),
			context = canvas.getContext('2d'),
	    	image   = new Image();

		canvas.width = this.options.previewThumbnailSize[0];
		canvas.height = this.options.previewThumbnailSize[1];

		image.onload = function() {
			context.drawImage(image, 0, 0, 80, 80 * image.height / image.width);
			file.context.find(that.options.selectors.preview).html(canvas);
		}

		image.src = URL.createObjectURL(file);
	}

	/**
	 * Add extra properties to the file.
	 *
	 * @param {Object} file
	 */
	Filepicker.prototype.extraProps = function(file) {
		if (! file.sizeFormatted) {
			file.sizeFormatted = this.formatFilesize(file.size);
			file.timeFormatted = file.time ? this.formatFiletime(file.time) : '';
			file.imageFileType = this.options.imageFileTypes.test(file.name);
		}
	}

	/**
	 * Get form data.
	 *
	 * @return {Object}
	 */
	Filepicker.prototype.formData = function() {
		var formData = this.options.formData;

		if (typeof formData !== 'function') {
			formData = $.extend({}, formData);
		}

		if (typeof formData === 'function') {
			formData = formData();
		}

		if (typeof formData === 'object') {
			var data = [];

			for (var field in formData) {
				data.push({name: field, value: formData[field]});
			};

			formData = data;
		}

		if (formData === undefined) {
			formData = [];
		}

		formData.add = function(name, value) {
			if (typeof name === "object") {
				for (var i in name) {
					this.add(i, name[i]);
				}
			} else {
				this.push({name: name, value: value});
			}

			return this;
		}

		formData.method = function(method) {
			this.add('_method', method);

			return this;
		}

		return formData;
	}

	/**
	 * Add form data.
	 *
	 * @param {Object} formData
	 * @param {String} name
	 * @param {String} value
	 */
	Filepicker.prototype.addFormData = function(formData, name, value) {
		formData.push({name: name, value: value});

		return this;
	}

	/**
	 * Set method.
	 *
	 * @param {Object} formData
	 * @param {String} method
	 */
	Filepicker.prototype.setMethod = function(formData, method) {
		this.addFormData(formData, '_method', method);
	}

	/**
	 * Replace the file input.
	 *
	 * @param {Object} input
	 */
	Filepicker.prototype.replaceFileInput = function(input) {
		var inputClone = input.clone(true);

		$('<form></form>').append(inputClone)[0].reset();

		input.after(inputClone).detach();

		$.cleanData(input.off('remove'));

		this.options.fileInput = this.options.fileInput.map(function (i, el) {
			if (el === input[0]) {
				return inputClone[0];
			}

			return el;
		});

		if (input[0] === this.element[0]) {
			this.element = inputClone;
		}
	}

	/**
	 * Get the files from the file input.
	 *
	 * @param  {Object} fileInput
	 * @return {Array}
	 */
	Filepicker.prototype.getInputFiles = function(input) {
        var files = $.makeArray(input.prop('files'));

        if ( ! files.length) {
            var value = input.prop('value');

            if ( ! value) return [];

			files = [{name: value.replace(/^.*\\/, '')}];
        } else if (files[0].name === undefined && files[0].fileName) {
            $.each(files, function (index, file) {
                file.name = file.fileName;
                file.size = file.fileSize;
            });
        }

        return files;
    }

    /**
     * Translage the given message.
     *
     * @param  {String} message
     * @param  {String} _default
     * @return {String}
     */
	Filepicker.prototype.trans = function(message, _default) {
    	return this.options.messages[message] || (_default || message.toString());
    }

    /**
     * Get the number of uploaded files.
     *
     * @return {Number}
     */
	Filepicker.prototype.getNumberOfFiles = function() {
    	return this.options.filesList.children().length;
    }

    /**
     * Get singular param name.
     *
     * @return {String}
     */
	Filepicker.prototype.getSingularParamName = function() {
    	return this.options.paramName.substr(0, this.options.paramName.length - 1);
    }

    /**
     * Get file extension.
     *
     * @param  {String} file
     * @return {String}
     */
	Filepicker.prototype.getFileExtension = function(file) {
		return file.substr(file.lastIndexOf('.') + 1, file.length);
	}

	/**
	 * Format file size.
	 *
	 * @param  {Number} bytes
	 * @return {String}
	 */
	Filepicker.prototype.formatFilesize = function(bytes) {
		if (typeof bytes !== 'number') {
			return '';
		}

		if (this.options.formatFilesize) {
			return this.options.formatFilesize(bytes);
		}

		var quant = {
			'GB': 1073741824,
			'MB': 1048576,
			'KB': 1024,
			'B': 1
		};

		for (var unit in quant) {
			if (bytes >= quant[unit]) {
				return (Math.round(bytes / quant[unit] * 10) / 10) + ' ' + unit;
			}
		}
	}

	/**
	 * Format file time.
	 *
	 * @param  {Number} timestamp
	 * @return {String}
	 */
	Filepicker.prototype.formatFiletime = function(timestamp) {
		if (this.options.formatFiletime) {
			return this.options.formatFiletime(timestamp);
		}

		// https://gist.github.com/kmaida/6045266
		var d = new Date(timestamp * 1000),
			yyyy = d.getFullYear(),
			mm = ('0' + (d.getMonth() + 1)).slice(-2),
			dd = ('0' + d.getDate()).slice(-2),
			hh = d.getHours(),
			h = hh,
			min = ('0' + d.getMinutes()).slice(-2),
			ampm = 'AM',
			time;

		if (hh > 12) {
			h = hh - 12;
			ampm = 'PM';
		} else if (hh === 12) {
			h = 12;
			ampm = 'PM';
		} else if (hh == 0) {
			h = 12;
		}

		time = yyyy + '-' + mm + '-' + dd + ', ' + h + ':' + min + ' ' + ampm;

		return time;
	}

	/**
	 * Transition element.
	 *
	 * @param {Object}   element
	 * @param {Function} callback
	 */
	Filepicker.prototype.transition = function(element, callback) {
		element.toggleClass('in');

		if (callback) {
			window.setTimeout(function() {
				callback(element);
			}, 150);
		}
	}

	/**
	 * Add event listener.
	 *
	 * @param {Object} element
	 * @param {String} type
	 * @param {String} selector
	 * @param {Function} handler
	 */
	Filepicker.prototype.on = function(element, type, selector, handler) {
		if (! element || ! element instanceof $) {
			return;
		}

		if (! handler) {
			handler = selector;
			selector = null;
		}

		element.on(type, selector, $.proxy(handler, this));
	}

	/**
	 * Trigger event.
	 *
	 * @param  {String} type
	 * @param  {Object} event
	 * @param  {Object} data
	 * @return {Boolean}
	 */
	Filepicker.prototype.trigger = function(type, event, data) {
		var prop, orig,
			callback = this.options[type] || this[type];

		data = data || {};
		event = $.Event(event);
		event.type = ('filepicker.' + type).toLowerCase();
		event.target = this.element[0];

		orig = event.originalEvent;
		if (orig) {
			for (prop in orig) {
				if (! (prop in event)) {
					event[prop] = orig[prop];
				}
			}
		}

		this.element.trigger(event, data);

		return !($.isFunction(callback) &&
				callback.apply(this, [event].concat(data)) === false ||
				event.isDefaultPrevented());
	}

	/**
	 * Debug log.
	 *
	 * @param {String} message
	 */
	Filepicker.prototype.log = function(message) {
		if (this.options.debug) {
			alert(message);
			console.log(message);
		}
	}

	/**
	 * Get drag handler.
	 *
	 * @param  {String} type
	 * @return {Function}
	 */
	function getDragHandler(type) {
		var isDragOver = type === 'dragover';

		return function(e) {
			e.dataTransfer = e.originalEvent && e.originalEvent.dataTransfer;
			var dataTransfer = e.dataTransfer;

			if (dataTransfer && $.inArray('Files', dataTransfer.types) !== -1 &&
				this.trigger(type, $.Event(type, {delegatedEvent: e})) !== false) {
                e.preventDefault();

                if (isDragOver) {
                	dataTransfer.dropEffect = 'copy';
				}
            }
        };
    }

	/**
	 * jQuery plugin definition.
	 *
	 * @param  {Array} options
	 * @return {Object}
	 */
	function Plugin(options) {
		if (! options) {
			return $(this).data('filepicker');
		}

		return this.each(function() {
			var $this = $(this);

			if (! $this.data('filepicker')) {
				$this.data('filepicker', new Filepicker(this, options));
			}
		});
	}

	$.fn.filePicker = Plugin;


	/**
	 * Simple JavaScript Templating
	 * http://ejohn.org/blog/javascript-micro-templating
	 *
	 * John Resig
	 * http://ejohn.org
	 */
    var cache = {};
   	function tmpl(str, data) {
        var fn = !/[^A-Za-z0-9_-]/.test(str) ?
		cache[str] = cache[str] || tmpl(document.getElementById(str).innerHTML) :
			new Function("obj",
				"var p=[],print=function(){p.push.apply(p,arguments);};" +
				"with(obj){p.push('" +
	    		str
					.replace(/[\r\t\n]/g, " ")
				// 	.split("<%").join("\t")
				// 	.replace(/((^|%>)[^\t]*)'/g, "$1\r")
				// 	.replace(/\t=(.*?)%>/g, "',$1,'")
				// 	.split("\t").join("');")
				// 	.split("%>").join("p.push('")
				// 	.split("\r").join("\\'")
					.replace(/'(?=[^%]*%>)/g,"\t")
					.split("'").join("\\'")
					.split("\t").join("'")
					.replace(/<%=(.+?)%>/g, "',$1,'")
					.split("<%").join("');")
					.split("%>").join("p.push('")
			+ "');}return p.join('');");
		return data ? fn(data) : fn;
    }
})(jQuery);
