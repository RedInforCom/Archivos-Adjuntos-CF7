jQuery(document).ready(function($){
	// Tabs
	$('.aacf7-tabs .aacf7-tab').on('click', function(){
		var tab = $(this).data('tab');
		var $wrap = $(this).closest('.aacf7-wrap');
		$wrap.find('.aacf7-tab').removeClass('active');
		$(this).addClass('active');
		$wrap.find('.aacf7-panel').removeClass('aacf7-panel--active');
		$wrap.find('.aacf7-panel[data-panel="'+tab+'"]').addClass('aacf7-panel--active');
	});

	// Subfield show/hide based on select
	$('select[name="storage[type]"]').on('change', function(){
		var val = $(this).val();
		var $wrap = $(this).closest('.aacf7-wrap');
		$wrap.find('.aacf7-subfield').hide();
		$wrap.find('.aacf7-subfield').each(function(){
			var when = $(this).data('when');
			if (when && when.split(',').indexOf(val) !== -1) {
				$(this).show();
			}
		});
	}).trigger('change');

	// Save config
	$('.aacf7-save').on('click', function(){
		var $wrap = $(this).closest('.aacf7-wrap');
		var form_id = $wrap.data('form-id');
		var config = {};
		// Gather fields (simple approach)
		$wrap.find('select, input[type="text"], input[type="number"], input[type="checkbox"]:checked, input[type="checkbox"]').each(function(){
			var name = $(this).attr('name');
			if (!name) return;
		});
		// Serialize form-like by building config structure manually
		config.storage = {
			type: $wrap.find('select[name="storage[type]"]').val(),
			path: $wrap.find('input[name="storage[path]"]').val(),
			attach_to_mail: $wrap.find('input[name="storage[attach_to_mail]"]').is(':checked') ? 1 : 0,
			retention_days: $wrap.find('input[name="storage[retention_days]"]').val()
		};
		config.options = {
			max_size_mb: $wrap.find('input[name="options[max_size_mb]"]').val(),
			max_files: $wrap.find('input[name="options[max_files]"]').val(),
			allowed_types: []
		};
		$wrap.find('input[name="options[allowed_types][]"]:checked').each(function(){
			config.options.allowed_types.push($(this).val());
		});
		config.texts = {
			title: $wrap.find('input[name="texts[title]"]').val(),
			drop_text: $wrap.find('input[name="texts[drop_text]"]').val(),
			button_text: $wrap.find('input[name="texts[button_text]"]').val(),
			note: $wrap.find('input[name="texts[note]"]').val()
		};
		config.validations = {
			required: $wrap.find('input[name="validations[required]"]').val(),
			size_exceeded: $wrap.find('input[name="validations[size_exceeded]"]').val(),
			count_exceeded: $wrap.find('input[name="validations[count_exceeded]"]').val(),
			type_not_allowed: $wrap.find('input[name="validations[type_not_allowed]"]').val()
		};
		config.styles = {
			container_bg: $wrap.find('input[name="styles[container_bg]"]').val(),
			dropzone_bg: $wrap.find('input[name="styles[dropzone_bg]"]').val(),
			button_bg: $wrap.find('input[name="styles[button_bg]"]').val()
		};

		var data = {
			action: 'aacf7_save_settings',
			nonce: aacf7_admin.nonce,
			form_id: form_id,
			config: config
		};
		$('.aacf7-status').text(aacf7_admin.lang.saving);
		$.post(aacf7_admin.ajax_url, data, function(resp){
			if (resp.success) {
				$('.aacf7-status').text('Guardado');
			} else {
				$('.aacf7-status').text('Error');
			}
		});
	});
});