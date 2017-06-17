var Matrix;

(function($) {


var $document = $(document);
var $body = $(document.body);


var callbacks = {
	display: {},
	beforeSort: {},
	afterSort: {},
	remove: {}
};

$.fn.ffMatrix = {
	onDisplayCell: {},
	onBeforeSortRow: {},
	onSortRow: {},
	onDeleteRow: {}
};

// --------------------------------------------------------------------

/**
 * Matrix
 */
Matrix = function(field, inputName, label, cols, rowInfo, minRows, maxRows) {

	// keep a record of this object
	Matrix.instances.push(this);

	var obj = this;
	obj.id = inputName;
	obj.label = label;
	obj.cols = cols;
	obj.totalCols = obj.cols.length;
	obj.rows = [];
	obj.totalRows = 0;
	obj.totalNewRows = 0;
	obj.minRows = minRows;
	obj.maxRows = maxRows;

	obj.focussedCell;
	obj.dragging = false;

	obj.dom = {};
	obj.dom.$field = $(field);
	obj.dom.$table = $('> table:first', obj.dom.$field);
	obj.dom.$tbody = $('> tbody:first', obj.dom.$table);
	obj.dom.$addBtn = $('> a.matrix-add:first', obj.dom.$field);

	obj.$tab = $();
	obj.$ee1Label = $();
	obj.$ee2Label = $();

	// -------------------------------------------
	//  Menu
	// -------------------------------------------

	obj.menu = {};
	obj.menu.$ul = $('<ul id="matrix-menu" />').appendTo($body).css({
		opacity: 0,
		display: 'none'
	});
	obj.menu.$addAbove = $('<li>'+Matrix.lang.add_row_above+'</li>').appendTo(obj.menu.$ul);
	obj.menu.$addBelow = $('<li>'+Matrix.lang.add_row_below+'</li>').appendTo(obj.menu.$ul);
	obj.menu.$ul.append('<li class="br" />');
    obj.menu.$moveToTop = $('<li>'+Matrix.lang.move_to_top+'</li>').appendTo(obj.menu.$ul);
    obj.menu.$moveToBottom = $('<li>'+Matrix.lang.move_to_bottom+'</li>').appendTo(obj.menu.$ul);
    obj.menu.$ul.append('<li class="br" />');
	obj.menu.$delete = $('<li>'+Matrix.lang.delete_row+'</li>').appendTo(obj.menu.$ul);

	obj.menu.reset = function(){
		// unbind any previous menu item events, and
		// prevent mousedown events from propagating to $th's
		var stopPropagation = function(e){ e.stopPropagation(); };
		obj.menu.$addAbove.unbind().bind('mousedown', stopPropagation);
		obj.menu.$addBelow.unbind().bind('mousedown', stopPropagation);
		obj.menu.$moveToTop.unbind().bind('mousedown', stopPropagation);
		obj.menu.$moveToBottom.unbind().bind('mousedown', stopPropagation);
		obj.menu.$delete.unbind().bind('mousedown', stopPropagation);

	};

	obj.menu.showing = false;

	// get the "No rows yet" row if it's there
	obj.dom.$norows = $('> tr.matrix-norows:first-child', obj.dom.$tbody);
	obj.dom.$norows.children().click(function(){
		obj.addRow();
	});

	/**
	 * Initialize Existing Rows
	 */
	obj.initRows = function(){
        $('> tr', obj.dom.$tbody).not(obj.dom.$norows).each(function(index){
            var rowID = $(this).find('th > input').val() || $(this).parents('table').next('input').val();
            var row = new Matrix.Row(obj, index, rowID, rowInfo, this);
            obj.rows.push(row);
        });

		obj.totalRows = obj.rows.length;
	};

	obj.initRowsIfVisible = function(){
		setTimeout(function() {
			if (! obj.initialized && obj.dom.$field.height()) {
				// stop listening for tab/label clicks
				obj.$tab.unbind('click'+obj.namespace);
				obj.$ee1Label.unbind('click'+obj.namespace);
				obj.$ee2Label.unbind('click'+obj.namespace);

				obj.initRows();
			}
		}, 100);
	};

	// only initialize if the field is already visible
	if (obj.dom.$field.height())
		obj.initRows();
	else {
		obj.initialized = false;

		// wait for its tab/label to be clicked on
		var $tabDiv = obj.dom.$field.closest('.main_tab'),
			tabId = 'menu_'+$tabDiv.attr('id');
		obj.$tab = $('#'+tabId+' a');
		obj.$ee1Label = obj.dom.$field.closest('.publishRows').children(':first').find('label');
		obj.$ee2Label = obj.dom.$field.closest('.publish_field').find('label.hide_field span');

		obj.namespace = '.matrix-'+obj.dom.$field.attr('id');
		obj.$tab.bind('click'+obj.namespace, obj.initRowsIfVisible);
		obj.$ee1Label.bind('click'+obj.namespace, obj.initRowsIfVisible);
		obj.$ee2Label.bind('click'+obj.namespace, obj.initRowsIfVisible);
	}

	// -------------------------------------------
	//  Row Management
	// -------------------------------------------

    obj.moveRow = function (rowObject, direction) {

        // beforeSort callback
        rowObject.callback('beforeSort', 'onBeforeSortRow');

        if (direction == "bottom") {

            targetIndex = rowObject.field.totalRows - 1;
            rowObject.field.rows[targetIndex].dom.$tr.after(rowObject.field.rows[rowObject.index].dom.$tr);

        } else {

            targetIndex = 0;
            rowObject.field.rows[targetIndex].dom.$tr.before(rowObject.field.rows[rowObject.index].dom.$tr);

        }

        // update field.rows array
        rowObject.field.rows.splice(rowObject.index, 1);
        rowObject.field.rows.splice(targetIndex, 0, rowObject);

        for (var i = 0; i <= rowObject.field.totalRows; i++) {
            if (typeof rowObject.field.rows[i] != "undefined") {
                rowObject.field.rows[i].updateIndex(i);
             }
        }

        // afterSort callback
        rowObject.callback('afterSort', 'onSortRow');

    };

	/**
	 * Add Row
	 */
	obj.addRow = function(index){
		// deny if we're already at the maximum rows
		if (obj.maxRows && obj.totalRows >= obj.maxRows) return;

		// is this the first row?
		if (obj.totalRows == 0) {
			obj.dom.$norows.hide();
		}

		if (typeof index != 'number' || index > obj.totalRows) {
			index = obj.totalRows;
		}
		else if (index < 0) {
			index = 0;
		}

		// -------------------------------------------
		//  Create the row
		// -------------------------------------------

		var rowId = 'row_new_'+obj.totalNewRows,
			rowCount = index + 1,
			cellSettings = {};

		var $tr = $('<tr class="matrix">'
		          +   '<th class="matrix matrix-first matrix-tr-header">'
		          +     '<div><span>'+rowCount+'</span><a title="'+Matrix.lang.options+'"></a></div>'
		          +     '<input type="hidden" name="'+obj.id+'[row_order][]" value="'+rowId+'" />'
		          +   '</th>'
		          + '</tr>');

		for (var colIndex = 0; colIndex < obj.cols.length; colIndex++) {
			var col = obj.cols[colIndex],
				colId = col.id,
				colCount = parseInt(colIndex) + 1;

			if (col.newCellSettings) {
				cellSettings[colId] = col.newCellSettings;
			}

			var tdClass = 'matrix';
			if (colCount == 1) tdClass += ' matrix-firstcell';
			if (colCount == obj.totalCols) tdClass += ' matrix-last';

			if (col.newCellClass) {
				tdClass += ' '+col.newCellClass;
			}

			var cellName = obj.id+'['+rowId+']['+colId+']',
				cellHtml = col.newCellHtml.replace(/\{DEFAULT\}/g, cellName);

			$tr.append('<td class="'+tdClass+'">'+cellHtml+'</td>');
		}

		// -------------------------------------------
		//  Insert and initialize it
		// -------------------------------------------

		// is this the new last row?
		if (index == obj.totalRows) {
			$tr.appendTo(obj.dom.$tbody).addClass('matrix-last');

			// was there a previous last row?
			if (obj.totalRows > 1) {
				obj.rows[obj.totalRows-1].dom.$tr.removeClass('matrix-last');
			}
		} else {
			$tr.insertBefore(obj.rows[index].dom.$tr);

			// is this the new first row?
			if (index == 0) {
				obj.rows[0].dom.$tr.removeClass('matrix-first');
				$tr.addClass('matrix-first');
			}
		}

		var row = new Matrix.Row(obj, index, rowId, cellSettings, $tr);
		obj.rows.splice(index, 0, row);

		obj.totalRows++;
		obj.totalNewRows++;

		// update the following rows' indices
		for (var i = index+1; i < obj.totalRows; i++) {
			obj.rows[i].updateIndex(i);
		}

		// update the add row button state
		obj.setAddBtnState();

		return row;
	};

	/**
	 * Remove Row
	 */
	obj.removeRow = function(index) {
		// deny if the row doesn't exist (somehow?), or if we're at the minimum
		if (typeof index == 'undefined' || typeof obj.rows[index] == 'undefined' || obj.totalRows <= obj.minRows) return false;

		var row = obj.rows[index];

		// remove callback
		row.callback('remove', 'onDeleteRow');

		if (! row.isNew) {
			// keep a record of the row_id so we can delete it from the database
			$('<input type="hidden" name="'+obj.id+'[deleted_rows][]" value="'+row.id+'" />').appendTo(obj.dom.$field);
		}

		// forgedaboudit!
		obj.rows.splice(index, 1);
		obj.totalRows--;
		row.remove();
		delete row;

		// update the following rows' indices
		for (var i = index; i < obj.totalRows; i++) {
			obj.rows[i].updateIndex(i);
		}

		// are there no rows left?
		if (!obj.totalRows) {
			obj.dom.$norows.show();
		} else {
			// was this the first row?
			if (index == 0) {
				obj.rows[0].dom.$tr.addClass('matrix-first');
			}

			// was this the last row?
			if (index == obj.totalRows) {
				obj.rows[obj.totalRows-1].dom.$tr.addClass('matrix-last');
			}
		}

		// update the add row button state
		obj.setAddBtnState();
	};

	obj.setAddBtnState = function(){
		if (obj.maxRows && obj.totalRows >= obj.maxRows) {
			obj.dom.$addBtn.addClass('matrix-btn-disabled');
		} else {
			obj.dom.$addBtn.removeClass('matrix-btn-disabled');
		}
	};

	// Add Row button
	obj.dom.$addBtn.click(obj.addRow);

	// click anywhere to blur the focussed cell
	$document.mousedown(function(){
		if (obj.ignoreThisClick) {
			obj.ignoreThisClick = false;
			return;
		}

		if (obj.focussedCell) {
			obj.focussedCell.blur();
		}
	});

};

Matrix.instances = [];

// --------------------------------------------------------------------

/**
 * Row
 */
Matrix.Row = function(field, index, id, cellSettings, tr){

	var obj = this;
	obj.field = field;
	obj.index = index;
	obj.id = id;
	obj.isNew = (obj.id.substr(0, 8) == 'row_new_');

	obj.cells = [];

	obj.dom = {};
	obj.dom.$tr = $(tr);
	obj.dom.$th = $('> th:first', obj.dom.$tr);
	obj.dom.$div = $('> div:first', obj.dom.$th);
	obj.dom.$span = $('> span:first', obj.dom.$div);
	obj.dom.$menuBtn = $('> a', obj.dom.$div);
	obj.dom.$tds = $('> td', obj.dom.$tr);

	obj.showingMenu = false;
	obj.dragging = false;

	// --------------------------------------------------------------------

	/**
	 * Callback
	 */
	obj.callback = function(callback, oldCallback) {
		for (var i = 0; i < obj.cells.length; i++) {
			obj.cells[i].callback(callback, oldCallback);
		}
	};

	// --------------------------------------------------------------------

	/**
	 * Update Index
	 */
	obj.updateIndex = function(index){
		obj.index = index;
		obj.dom.$span.html(index+1);

		// is this the new first?
		if (obj.index == 0) obj.dom.$tr.addClass('matrix-first');
		else obj.dom.$tr.removeClass('matrix-first');

		// is this the new last?
		if (obj.index == obj.field.totalRows-1) obj.dom.$tr.addClass('matrix-last');
		else obj.dom.$tr.removeClass('matrix-last');
	};

	// --------------------------------------------------------------------

	/**
	 * Remove
	 */
	obj.remove = function(){
		// Simply removing the <tr> causes issues with succeeding CKEditor instances
		// for some reason (the <iframe> contentWindow properties become null??).
		// So rather than calling $tr.remove(), we'll just hide it and remove its row_order input.
		obj.dom.$tr.hide();
		obj.dom.$tr.children('th:first').children('input:first').remove();
	};

	// -------------------------------------------
	//  Menu
	// -------------------------------------------

	/**
	 * Show Menu Button
	 */
	obj.showMenuBtn = function(){
        obj.dom.$menuBtn.stop().fadeIn(100);
	};

	/**
	 * Hide Menu Button
	 */
	obj.hideMenuBtn = function(){
        obj.dom.$menuBtn.stop(true).fadeOut(100);
	};

	/**
	 * Menu Button hovers
	 */
	obj.dom.$th.hover(
		function(){
			// set "on" state unless the menu is already visible somewhere
			if (! obj.field.menu.showing && ! obj.field.dragging) {
				obj.showMenuBtn();
			}
		},
		function(){
			// hide "on" state unless the menu is visible on this button
			if (! obj.showingMenu) {
				obj.hideMenuBtn();
			}
		}
	);

	// --------------------------------------------------------------------

	/**
	 * Show Menu
	 */
	obj.showMenu = function(event){
		if (obj.field.menu.showing) return;

		obj.showMenuBtn();

		var offset = obj.dom.$menuBtn.offset();

		obj.field.menu.$ul.show().css({
			left: offset.left + 2,
			top: offset.top + 11,
			display: 'block'
		});

		obj.field.menu.$ul.stop(true).animate({ opacity: 1 }, 100);

		obj.showingMenu = obj.field.menu.showing = true;

		// -------------------------------------------
		//  Bind listeners
		// -------------------------------------------

		obj.field.menu.reset();

        if (obj.field.totalRows < 2) {
            obj.field.menu.$moveToTop.addClass('disabled');
            obj.field.menu.$moveToBottom.addClass('disabled');
        } else {
            obj.field.menu.$moveToTop.removeClass('disabled');
            obj.field.menu.$moveToBottom.removeClass('disabled');

            obj.field.menu.$moveToTop.unbind('click').bind('click', function () {
                obj.field.moveRow(obj, 'top');
                getRowAttributes();

            });
            obj.field.menu.$moveToBottom.unbind('click').bind('click', function () {
                obj.field.moveRow(obj, 'bottom');
                getRowAttributes();
            });
        }

		if (obj.field.minRows && obj.field.totalRows <= obj.field.minRows) {
			// disable Delete Row option
			obj.field.menu.$delete.addClass('disabled');
		} else {
			// Delete Row
			obj.field.menu.$delete.removeClass('disabled').bind('click', function(){
				obj.field.removeRow(obj.index);
			});
		}

		if (obj.field.maxRows && obj.field.totalRows >= obj.field.maxRows) {
			// disable Add Row options
			obj.field.menu.$addAbove.addClass('disabled');
			obj.field.menu.$addBelow.addClass('disabled');
		} else {
			// Add Row Above
			obj.field.menu.$addAbove.removeClass('disabled').bind('click', function(){
				obj.field.addRow(obj.index);
			});

			// Add Row Below
			obj.field.menu.$addBelow.removeClass('disabled').bind('click', function(){
				obj.field.addRow(obj.index+1);
			});
		}

		setTimeout(function(){
			$document.bind('click.matrix-row', obj.hideMenu);
		}, 0);
	};

	/**
	 * Hide Menu
	 */
	obj.hideMenu = function(){
		obj.field.menu.$ul.stop(true).animate({ opacity: 0 }, 100, function(){
			obj.field.menu.$ul.css('display', 'none');
		});
		obj.hideMenuBtn();
		obj.showingMenu = obj.field.menu.showing = false;
		$document.unbind('click.matrix-row');
	};

	// listen for click
	obj.dom.$menuBtn.mousedown(function(event){
		// prevent this from triggering $th.mousedown()
		event.stopPropagation();
	});

	obj.dom.$menuBtn.click(obj.showMenu);

	// -------------------------------------------
	//  Dragging
	// -------------------------------------------

	var fieldOffset,
		mousedownY,
		mouseY,
		mouseOffset,
		helperPos,
		rowAttr,
		$helper, $placeholder,
		updateHelperPosInterval;

	/**
	 * Mouse down
	 */
	obj.dom.$th.mousedown(function(event){
		if (obj.field.totalRows < 2) return;

		obj.hideMenu();

		mousedownY = event.pageY;

		$document.bind('mousemove.matrix-row', onMouseMove);
		$document.bind('mouseup.matrix-row', onMouseUp);

		$body.addClass('matrix-grabbing');
	});

	/**
	 * Get Row Attributes
	 */
	var getRowAttributes = function(){
		rowAttr = [];

		for (var i = 0; i < obj.field.rows.length; i++) {
			var row = obj.field.rows[i],
				$tr = (row == obj && !! $placeholder ? $placeholder : row.dom.$tr);

			rowAttr[i] = {};
			rowAttr[i].offset = $tr.offset();
			rowAttr[i].height = $tr.outerHeight();
			rowAttr[i].midpoint = rowAttr[i].offset.top + Math.floor(rowAttr[i].height / 2);;
		}
	};

	/**
	 * Mouse move
	 */
	var onMouseMove = function(event){
		// prevent this from causing a selections
		event.preventDefault();

		mouseY = event.pageY;

		if (! obj.dragging) {
			// has the cursor traveled 1px yet?
			if (Math.abs(mousedownY - mouseY) > 1) {

				// beforeSort callback
				obj.callback('beforeSort', 'onBeforeSortRow');

				obj.dragging = obj.field.dragging = true;

				getRowAttributes();

				// create a placeholder row
				$placeholder = $('<tr class="matrix-placeholder">'
				               +   '<td colspan="'+(obj.field.totalCols+1)+'" style="height: '+rowAttr[obj.index].height+'px;"></td>'
				               + '</tr>');

				// hardcode the cell widths
				for (var i = 0; i < obj.cells.length; i++) {
					obj.cells[i].saveWidth();
				}

				// create a floating helper table
				$helper = $('<table class="matrix matrix-helper" cellspacing="0" cellpadding="0" border="0">'
				          +   '<tbody class="matrix"></tbody>'
				          + '</table>');

				fieldOffset = obj.field.dom.$field.offset();
				mouseOffset = mousedownY - rowAttr[obj.index].offset.top;
				helperPos = rowAttr[obj.index].offset.top;

				$helper.css({
					position: 'absolute',
					left:     fieldOffset.left - (rowAttr[obj.index].offset.left-1),
					width:    obj.field.dom.$table.outerWidth()
				});

				// put it all in place
				$placeholder.insertAfter(obj.dom.$tr);
				$helper.appendTo(obj.field.dom.$field);
				obj.dom.$tr.appendTo($('> tbody', $helper));

				updateHelperPos();
				updateHelperPosInterval = setInterval(updateHelperPos, 25);
			}
		}

		if (obj.dragging) {

			if (obj.index > 0 && mouseY < rowAttr[obj.index-1].midpoint) {
				var swapIndex = obj.index - 1,
					swapRow = obj.field.rows[swapIndex];

				$placeholder.insertBefore(swapRow.dom.$tr);
			}
			else if (obj.index < obj.field.totalRows-1 && mouseY > rowAttr[obj.index+1].midpoint) {
				var swapIndex = obj.index + 1,
					swapRow = obj.field.rows[swapIndex];

				$placeholder.insertAfter(swapRow.dom.$tr);
			}

			if (typeof swapIndex != 'undefined') {
				// update field.rows array
				obj.field.rows.splice(obj.index, 1);
				obj.field.rows.splice(swapIndex, 0, obj);

				// update the rows themselves
				swapRow.updateIndex(obj.index);
				obj.updateIndex(swapIndex);

				// offsets have changed, so fetch them again
				getRowAttributes();
			}
		}
	};

	/**
	 * Update Helper Position
	 */
	var updateHelperPos = function(){
		var dist = mouseY - rowAttr[obj.index].midpoint,
			target = rowAttr[obj.index].offset.top + Math.round(dist / 6);

		helperPos += (target - helperPos) / 2;
		$helper.css('top', helperPos - fieldOffset.top);
	};

	/**
	 * Mouse up
	 */
	var onMouseUp = function(event){
		$document.unbind('.matrix-row');
		$body.removeClass('matrix-grabbing');

		if (obj.dragging) {

			obj.dragging = obj.field.dragging = false;

			clearInterval(updateHelperPosInterval);

			// animate the helper back to the placeholder
			var top = (rowAttr[obj.index].offset.top-1) - fieldOffset.top;
			$helper.animate({ top: top }, 'fast', function(){
				$placeholder.replaceWith(obj.dom.$tr);
				$placeholder = null;

				$helper.remove();

				// clear the cell widths
				for (var i = 0; i < obj.cells.length; i++) {
					obj.cells[i].clearWidth();
				}

				// afterSort callback
				obj.callback('afterSort', 'onSortRow');

			});
		}
	};

	// -------------------------------------------
	//  Initialize cells
	// -------------------------------------------

	obj.dom.$tds.each(function(index){
		var col = obj.field.cols[index],
			settings = $.extend({}, col.settings, cellSettings[col.id]),
			cell = new Matrix.Cell(obj.field, col.type, settings, this, obj, col);

		obj.cells.push(cell);
	});
};

// --------------------------------------------------------------------

/**
 * Cell
 */
Matrix.Cell = function(field, type, settings, td, row, col){

	var obj = this;
	obj.field = field;
	obj.type = type;
	obj.settings = settings;

	obj.row = row;
	obj.col = col;

	obj.dom = {};
	obj.dom.$td = $(td);
	obj.dom.$inputs = $('*[name][type!=hidden]', obj.dom.$td);

	obj.focussed = false;

	// --------------------------------------------------------------------

	/**
	 * Callback
	 */
	obj.callback = function(callback, oldCallback){
		// display callback
		if (typeof callbacks[callback][obj.type] == 'function') {
			callbacks[callback][obj.type].call(obj.dom.$td, obj);
		}
		else if (typeof $.fn.ffMatrix[oldCallback][obj.type] == 'function') {
			$.fn.ffMatrix[oldCallback][obj.type](obj.dom.$td, obj);
		}
	};

	// --------------------------------------------------------------------

	/**
	 * Save Width
	 */
	obj.saveWidth = function(){
		obj.dom.$td.width(obj.dom.$td.width());
	};

	/**
	 * Clear Width
	 */
	obj.clearWidth = function(){
		obj.dom.$td.width('auto');
	};

	// --------------------------------------------------------------------

	/**
	 * Focus
	 */
	obj.focus = function(){
		if (obj.focussed || obj.dom.$td.hasClass('matrix-disabled')) return false;

		if (obj.field.focussedCell) {
			obj.field.focussedCell.blur();
		}

		obj.focussed = true;
		obj.field.focussedCell = obj;
		obj.dom.$td.addClass('matrix-focussed');

		return true;
	};

	/**
	 * Blur
	 */
	obj.blur = function(){
		if (! obj.focussed) return false;

		obj.focussed = false;
		obj.field.focussedCell = null;
		obj.dom.$td.removeClass('matrix-focussed');

		return true;
	};

	/**
	 * Mousedown
	 */
	obj.dom.$td.mousedown(function(event){
		obj.field.ignoreThisClick = true;

		// focus the first visible input
		if (obj.dom.$inputs.length && obj.focus() && event.target == this) {
			setTimeout(function(){
				$(obj.dom.$inputs[0]).focus();
			}, 0);
		}
	});

	/**
	 * Input focus
	 */
	obj.dom.$inputs.focus(function(){
		obj.focus();
	});

	// display callback
	obj.callback('display', 'onDisplayCell');

};

// --------------------------------------------------------------------

/**
 * Bind
 */
Matrix.bind = function(celltype, event, callback){
	// is this a legit event?
	if (typeof callbacks[event] == 'undefined') return;

	callbacks[event][celltype] = callback;
};

/**
 * Unbind
 */
Matrix.unbind = function(celltype, event){
	// is this a legit event?
	if (typeof callbacks[event] == 'undefined') return;

	// is the celltype even listening?
	if (typeof callbacks[event][celltype] == 'undefined') return;

	delete callbacks[event][celltype];
};

})(jQuery);
