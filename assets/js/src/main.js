$(function() {
	$('.spoiler .spoiler__trigger').click(function(e){
		var spoiler = $(this).parent();
		$(spoiler).find('.spoiler__content').toggle();
	});
	
	$('#table-last-activities').tablesorter({
		textExtraction: function(node) {
			var data = node.dataset;
			var value = data.value;
			if (value != undefined) {
				//console.log('value 0: ' + value);
				return value;
			}  else {
				//console.log('value 1: ' + node.textContent);
				return node.textContent;
			}
		}
	});
});