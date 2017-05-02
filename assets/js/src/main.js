$(function() {
	$('.spoiler .spoiler__trigger').click(function(e){
		var spoiler = $(this).parent();
		$(spoiler).find('.spoiler__content').toggle();
	});
});