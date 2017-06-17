(function($) {

Matrix.bind("rte", "display", function(cell){
	if (cell.row.isNew && cell.dom.$td.find(".WysiHat-editor").length === 0) {
		cell.dom.$td.find(".WysiHat-field").wysihat({
			buttons: ["headings","bold","italic","blockquote","unordered_list","ordered_list","link","image","view_source"]
		});
	}
});

})(jQuery);
