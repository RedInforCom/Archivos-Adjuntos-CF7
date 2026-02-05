(function($){
    $(document).ready(function(){
        $('.aacf7-container').each(function(){
            var $wrap = $(this);
            var $drop = $wrap.find('.aacf7-dropzone');
            var $input = $wrap.find('.aacf7-file-input');
            var $btn = $wrap.find('.aacf7-button');
            var $list = $wrap.find('.aacf7-list');
            var $hidden = $wrap.find('.aacf7-hidden');

            var filesArr = [];

            $btn.on('click', function(){ $input.trigger('click'); });

            $input.on('change', function(e){
                handleFiles(e.target.files);
                this.value = '';
            });

            $drop.on('dragover', function(e){ e.preventDefault(); $(this).addClass('dragover'); });
            $drop.on('dragleave drop', function(e){ e.preventDefault(); $(this).removeClass('dragover'); });
            $drop.on('drop', function(e){
                var dt = e.originalEvent.dataTransfer;
                if (dt && dt.files) handleFiles(dt.files);
            });

            function handleFiles(list){
                for (var i=0;i<list.length;i++){
                    uploadFile(list[i]);
                }
            }

            function uploadFile(file){
                var formData = new FormData();
                formData.append('action','aacf7_upload');
                formData.append('nonce', aacf7_ajax.nonce);
                formData.append('file', file);
                // post_id from container
                var post_id = $wrap.data('setting-post') || 0;
                formData.append('post_id', post_id);

                var $item = $('<div class="aacf7-item"><div class="aacf7-item-info"></div><div class="aacf7-item-progress"><div class="aacf7-progress-bar"></div></div><button type="button" class="aacf7-remove">X</button></div>');
                $list.append($item);
                var $info = $item.find('.aacf7-item-info');
                $info.text('Subiendo: ' + file.name + ' | ' + Math.round(file.size/1024) + ' KB');

                $.ajax({
                    url: aacf7_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    xhr: function(){
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener('progress', function(evt){
                            if (evt.lengthComputable){
                                var percent = Math.round((evt.loaded / evt.total) * 100);
                                $item.find('.aacf7-progress-bar').css('width', percent + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(res){
                        if (res.success){
                            $info.html('📎 ' + res.data.name + ' | ' + Math.round(res.data.file ? (0) : 0) + ' KB');
                            // store path in filesArr
                            filesArr.push(res.data);
                            $hidden.val(JSON.stringify(filesArr));
                        } else {
                            $info.text('Error: ' + res.data.message);
                            $item.addClass('aacf7-error');
                        }
                    },
                    error: function(){
                        $info.text('Error en la subida.');
                        $item.addClass('aacf7-error');
                    }
                });

                $item.on('click', '.aacf7-remove', function(){
                    var idx = $item.index();
                    filesArr.splice(idx,1);
                    $hidden.val(JSON.stringify(filesArr));
                    $item.remove();
                });
            }
        });
    });
})(jQuery);