(function($) {
	Matrix.bind('file', 'display', function(cell){

		var container = $(cell.dom.$td);
		var uploadControls = container.find('.upload_controls');
		var existingFile = container.find('.existing_file');
		var fileName = container.find('.filename');
		var existingThumb = container.find('.matrix-thumb, .matrix-filename');
		var undoRemove = container.find('a.undo_remove');

		if (existingFile.val().length > 1)
		{
			uploadControls.hide();
			undoRemove.hide();
			container.find('.matrix-thumb a').click(function () {
				uploadControls.show();
				existingThumb.hide();
				fileName.attr('name', 'not_' + fileName.attr('name'));
				existingFile.attr('name', 'not_' + existingFile.attr('name'));
				undoRemove.show();
				return false;
			});
		}

		undoRemove.click(function () {
			uploadControls.hide();
			undoRemove.hide();
			existingThumb.show();
			fileName.attr('name', fileName.attr('name').replace(/^not_/, ''));
			existingFile.attr('name', existingFile.attr('name').replace(/^not_/, ''));
			return false;
		});


	});

})(jQuery);
