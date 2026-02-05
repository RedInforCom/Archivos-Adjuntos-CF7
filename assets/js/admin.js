jQuery(document).ready(function($){
    $('.aacf7-tabs-nav li').on('click', function(){
        var tab = $(this).data('tab');
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
        $(this).closest('.aacf7-tabs').find('.aacf7-tab-panel').removeClass('active');
        $(this).closest('.aacf7-tabs').find('#aacf7-tab-' + tab).addClass('active');
    });
});