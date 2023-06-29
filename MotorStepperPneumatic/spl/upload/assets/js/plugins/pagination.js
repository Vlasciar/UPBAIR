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
	 * @param {Object} container
	 * @param {Number} perPage
	 */
	var Plugin = function(filePicker, container, perPage) {
		this.container = container;
		this.filePicker = filePicker;
		this.currentPage = 1;
		this.perPage = perPage ? perPage : 20;
		this.files = 0;

		this.init();
	}

	/**
	 * Initialize plugin.
	 */
	Plugin.prototype.init = function() {
		this.container.on('click', 'a', $.proxy(this.onClick, this));

		var that = this;

		var added = 0;
		this.filePicker.element.on('filepicker.add', function() {
			added++;
		});

		// Auto refresh after each upload.
		// var success = 0;
		// this.filePicker.element.on('filepicker.success', function(e, file) {
			// if (file.error) return;
			// success++;
			// if (added == success) {
			// 	that.load();
			// 	added = success = 0;
			// }
		// });

		var deleted = 0;
		this.filePicker.element.on('filepicker.destroyed', function() {
			deleted++;
			if (deleted == that.files) {
				that.load();
				deleted = 0;
			}
		});

		this.load();
	}

	/**
	 * On click even handler.
	 *
	 * @param {Object} e
	 */
	Plugin.prototype.onClick = function(e) {
		e.preventDefault();

		if ($(e.target).parent().hasClass('disabled')) {
			return false;
		}

		this.currentPage = parseInt($(e.target).attr('href').replace('#', ''));

		this.load(this.currentPage * this.perPage - this.perPage);
	}

	/**
	 * Load files.
	 *
	 * @param {Number} offset
	 */
	Plugin.prototype.load = function(offset) {
		this.filePicker.element.on('filepicker.load', $.proxy(this.render, this));
		this.filePicker.autoLoad(this.perPage, offset);
	}

	/**
	 * Append element to the pagination list.
	 *
	 * @param {Number} page
	 * @param {String} content
	 * @param {String} klass
	 */
	Plugin.prototype.append = function(page, content, klass) {
		var el = $('<li>', {class: klass}).append($('<a>', {href: '#'+page, html: content}));

		this.container.append(el);
	}

	/**
	 * Render pagination list element.
	 *
	 * @param {Object} result
	 */
	Plugin.prototype.render = function(e, result) {
		var pages = Math.ceil(result.total / this.perPage);

		this.container.children().remove();

		this.files = result.files.length;

		if (pages > 1) {
			this.append(this.currentPage - 1, '&laquo;', this.currentPage == 1 ? 'disabled' : null);

			for (var i = 1; i <= pages; i++) {
				this.append(i, i, this.currentPage == i ? 'active' : null);
			}

			this.append(this.currentPage + 1, '&raquo;', this.currentPage == pages ? 'disabled' : null);
		}

		this.filePicker.options.filesList.children().remove();

	}


	/**
	 * Global export.
	 *
	 * @param {Object} filePicker
	 * @param {Object} container
	 * @param {Number} perPage
	 */
	window.FilepickerPagination = function(filePicker, container, perPage) {
		if (filePicker instanceof $) {
			filePicker = filePicker.filePicker();
		}

		return new Plugin(filePicker, container, perPage);
	}
})(jQuery);
