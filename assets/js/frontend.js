(function($) {
    'use strict';
    class AACF7FileUpload {
        constructor(container) {
            this.container = $(container);
            this.fieldName = this.container.data('field-name');
            this.maxSize = this.container.data('max-size') * 1024;
            this.maxFiles = this.container.data('max-files');
            this.allowedTypes = this.container.data('allowed-types').split(',');
            this.required = this.container.data('required') === 1;
            this.dropzone = this.container.find('.aacf7-dropzone');
            this.input = this.container.find('.aacf7-input');
            this.filesContainer = this.container.find('.aacf7-files');
            this.errorsContainer = this.container.find('.aacf7-errors');
            this.selectedFiles = [];
            this.init();
        }
        init() {
            const self = this;
            this.dropzone.on('click', function(e) {
                if (e.target.tagName !== 'BUTTON') {
                    self.input.trigger('click');
                }
            });
            this.input.on('change', function(e) {
                self.handleFileSelect(e.target.files);
            });
            this.dropzone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragging');
            });
            this.dropzone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragging');
            });
            this.dropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragging');
                const files = e.originalEvent.dataTransfer.files;
                self.handleFileSelect(files);
            });
        }
        handleFileSelect(files) {
            this.clearErrors();
            if (this.selectedFiles.length + files.length > this.maxFiles) {
                this.showError('Solo puedes subir un máximo de ' + this.maxFiles + ' archivo(s).');
                return;
            }
            Array.from(files).forEach(file => {
                const validation = this.validateFile(file);
                if (validation.valid) {
                    this.addFile(file);
                } else {
                    this.showError(validation.error);
                }
            });
        }
        validateFile(file) {
            if (file.size > this.maxSize) {
                return { valid: false, error: 'El archivo "' + file.name + '" excede el tamaño máximo de ' + this.formatSize(this.maxSize) + '.' };
            }
            const extension = file.name.split('.').pop().toLowerCase();
            const allowedTypes = this.allowedTypes.map(t => t.trim());
            if (!allowedTypes.includes(extension)) {
                return { valid: false, error: 'El tipo de archivo .' + extension + ' no está permitido.' };
            }
            return { valid: true };
        }
        addFile(file) {
            this.selectedFiles.push(file);
            const fileItem = this.createFileItem(file);
            this.filesContainer.append(fileItem);
            this.simulateProgress(fileItem);
        }
        createFileItem(file) {
            const extension = file.name.split('.').pop().toLowerCase();
            const iconClass = this.getIconClass(extension);
            const html = '<div class="aacf7-file-item" data-filename="' + this.escapeHtml(file.name) + '"><div class="aacf7-file-info"><div class="aacf7-file-icon ' + iconClass + '"></div><div class="aacf7-file-details"><div class="aacf7-file-name">' + this.escapeHtml(file.name) + '</div><div class="aacf7-file-size">' + this.formatSize(file.size) + '</div></div></div><div class="aacf7-file-status uploading"><span class="aacf7-loading"></span> Preparando...</div><button type="button" class="aacf7-remove-btn" style="display: none;">✕ Eliminar</button></div>';
            return $(html);
        }
        simulateProgress(fileItem) {
            const progressBar = $('<div class="aacf7-progress-bar"><div class="aacf7-progress-fill" style="width: 0%"></div></div>');
            fileItem.find('.aacf7-file-details').append(progressBar);
            const fill = progressBar.find('.aacf7-progress-fill');
            let progress = 0;
            const interval = setInterval(() => {
                progress += Math.random() * 30;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    setTimeout(() => {
                        progressBar.fadeOut(() => progressBar.remove());
                        fileItem.find('.aacf7-file-status').removeClass('uploading').addClass('success').html('✓ Listo');
                        fileItem.find('.aacf7-remove-btn').fadeIn();
                        this.setupRemoveButton(fileItem);
                    }, 300);
                }
                fill.css('width', progress + '%');
            }, 200);
        }
        setupRemoveButton(fileItem) {
            const self = this;
            fileItem.find('.aacf7-remove-btn').on('click', function() {
                const filename = fileItem.data('filename');
                self.selectedFiles = self.selectedFiles.filter(f => f.name !== filename);
                fileItem.fadeOut(300, function() { $(this).remove(); });
                self.input.val('');
            });
        }
        getIconClass(extension) {
            const imageTypes = ['jpg', 'jpeg', 'png', 'webp', 'bmp', 'gif'];
            const excelTypes = ['xlsx', 'xls'];
            const wordTypes = ['doc', 'docx'];
            if (imageTypes.includes(extension)) return 'image';
            if (extension === 'pdf') return 'pdf';
            if (excelTypes.includes(extension)) return 'excel';
            if (wordTypes.includes(extension)) return 'word';
            return '';
        }
        formatSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        showError(message) {
            const errorHtml = '<div class="aacf7-error-item">• ' + this.escapeHtml(message) + '</div>';
            this.errorsContainer.addClass('has-errors').append(errorHtml);
        }
        clearErrors() {
            this.errorsContainer.removeClass('has-errors').empty();
        }
        escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    }
    $(document).ready(function() {
        $('.aacf7-container').each(function() {
            new AACF7FileUpload(this);
        });
    });
    $(document).on('wpcf7mailsent', function() {
        $('.aacf7-container').each(function() {
            const container = $(this);
            container.find('.aacf7-files').empty();
            container.find('.aacf7-input').val('');
            container.find('.aacf7-errors').removeClass('has-errors').empty();
        });
    });
})(jQuery);
