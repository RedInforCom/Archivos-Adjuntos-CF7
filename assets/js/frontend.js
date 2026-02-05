jQuery(document).ready(function($){
	// Initialize each field
	$('.aacf7-field-wrapper').each(function(){
		var $wrap = $(this);
		var form_id = $wrap.data('form-id');
		var field_name = $wrap.data('field-name');
		var $input = $wrap.find('.aacf7-hidden-input');
		var uploaded = []; // array of ids

		var $drop = $wrap.find('.aacf7-dropzone');
		var $fileInput = $wrap.find('.aacf7-file-input');

		$drop.on('click', function(e){
			$fileInput.trigger('click');
		});
		$drop.on('dragover', function(e){
			e.preventDefault();
			$(this).addClass('dragover');
		});
		$drop.on('dragleave drop', function(e){
			e.preventDefault();
			$(this).removeClass('dragover');
		});
		$drop.on('drop', function(e){
			var dt = e.originalEvent.dataTransfer;
			if (dt && dt.files) {
				handleFiles(dt.files);
			}
		});
		$fileInput.on('change', function(e){
			handleFiles(this.files);
			this.value = '';
		});

		function handleFiles(files) {
			for (var i=0;i<files.length;i++){
				uploadFile(files[i]);
			}
		}

		function uploadFile(file) {
			var fd = new FormData();
			fd.append('action','aacf7_upload');
			fd.append('nonce', aacf7_frontend.nonce);
			fd.append('form_id', form_id);
			fd.append('file', file);
			var $item = $('<div class="aacf7-file-item"><div class="aacf7-file-meta"><strong>'+escapeHtml(file.name)+'</strong><div class="aacf7-progress"><span></span></div></div><div><a class="aacf7-file-remove">X</a></div></div>');
			$wrap.find('.aacf7-files-list').append($item);
			var $bar = $item.find('.aacf7-progress span');

			$.ajax({
				url: aacf7_frontend.ajax_url,
				type: 'POST',
				data: fd,
				contentType: false,
				processData: false,
				xhr: function(){
					var xhr = new window.XMLHttpRequest();
					xhr.upload.addEventListener("progress", function(evt){
						if (evt.lengthComputable) {
							var percent = Math.round((evt.loaded / evt.total) * 100);
							$bar.css('width', percent + '%');
						}
					}, false);
					return xhr;
				},
				success: function(resp){
					if (resp.success) {
						$bar.css('width','100%');
						var id = resp.data.id;
						uploaded.push(id);
						$input.val(uploaded.join(','));
						$item.attr('data-file-id', id);
						$item.find('.aacf7-file-remove').on('click', function(){
							deleteFile(id, $item);
						});
					} else {
						$item.remove();
						alert(resp.data && resp.data.message ? resp.data.message : 'Error');
					}
				},
				error: function(){
					$item.remove();
					alert('Error subiendo archivo.');
				}
			});
		}

		function deleteFile(id, $el) {
			$.post(aacf7_frontend.ajax_url, { action:'aacf7_delete', nonce: aacf7_frontend.nonce, form_id: form_id, file_id: id }, function(resp){
				if (resp.success) {
					$el.remove();
					uploaded = uploaded.filter(function(x){ return x !== id; });
					$input.val(uploaded.join(','));
				} else {
					alert('No se pudo eliminar');
				}
			});
		}

		function escapeHtml(text) {
			return text.replace(/[\"&'\/<>]/g, function (a) { return { '"': '&quot;', '&': '&amp;', "'": '&#39;', '/': '&#47;', '<': '&lt;', '>': '&gt;' }[a]; });
		}
	});
});