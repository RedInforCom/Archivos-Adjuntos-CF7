(function($) {
    'use strict';
    $(document).ready(function() {
        // Tabs
        $('.aacf7-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            $('.aacf7-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.aacf7-tab-content').removeClass('active');
            $('.aacf7-tab-content[data-tab="' + tab + '"]').addClass('active');
        });
        
        // Color pickers
        if (typeof $.fn.wpColorPicker !== 'undefined') {
            $('.aacf7-color-picker').wpColorPicker();
        }
        
        // Conditional fields
        $('input[name="aacf7_settings[upload_location]"]').on('change', function() {
            const value = $(this).val();
            $('.aacf7-conditional').hide();
            $('.aacf7-conditional[data-show-when="upload_location"][data-show-value="' + value + '"]').show();
        });
    });
})(jQuery);
