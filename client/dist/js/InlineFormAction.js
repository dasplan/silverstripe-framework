(function (global, factory) {
	if (typeof define === "function" && define.amd) {
		define('ss.InlineFormAction', [], factory);
	} else if (typeof exports !== "undefined") {
		factory();
	} else {
		var mod = {
			exports: {}
		};
		factory();
		global.ssInlineFormAction = mod.exports;
	}
})(this, function () {
	'use strict';

	function _defineProperty(obj, key, value) {
		if (key in obj) {
			Object.defineProperty(obj, key, {
				value: value,
				enumerable: true,
				configurable: true,
				writable: true
			});
		} else {
			obj[key] = value;
		}

		return obj;
	}

	Behaviour.register({
		'div.inlineformaction input#$ID': {
			onclick: function onclick() {
				var url = jQuery('base').attr('href') + 'admin-custom/' + this.name.substring(7) + '?ID=' + document.getElementById('Form_EditForm_ID').value + '&ajax=1';

				jQuery.ajax(_defineProperty({
					'url': url,
					success: Ajax.Evaluator
				}, 'success', Ajax.Evaluator));

				return false;
			}
		}
	});
});