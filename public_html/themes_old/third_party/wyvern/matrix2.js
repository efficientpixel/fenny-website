(function($) {

    // Display
    var onDisplay = function(cell){

        var $textarea = $('textarea', cell.dom.$td);

        // eval so we're working with arrays/objects, not a string values
        var toolbar = eval($textarea.attr('data-toolbar'));
        var config = eval($textarea.attr('data-config'))[0];

        var resizable = $textarea.attr('data-resizable');
        var id = cell.field.id+'_'+cell.row.id+'_'+cell.col.id+'_'+Math.floor(Math.random()*100000000);

        $textarea.attr('id', id);

        // Reset the ID with the Matrix provided ID
        config.field_id = id;

        // Duplicate our settings. Settings saved to obj with the col_id as the property,
        // but Matrix creates funky IDs, so dupe the object so the plugin.js can find the data.
        wyvern_config[id] = wyvern_config[cell.col.id];

        init_wyvern(config, toolbar);
    };

    // Make sure contents are restored after sorting
    var beforeSort = function(cell)
    {
        var $textarea = $('textarea', cell.dom.$td);
        contents = $('iframe:first', cell.dom.$td)[0].contentDocument.body.innerHTML;
        $textarea.val(contents);
    }

    var afterSort = function(cell)
    {
        $textarea = $('textarea', cell.dom.$td);
        cell.dom.$td.empty().append($textarea);
        CKEDITOR.remove(CKEDITOR.instances[$textarea.attr('id')]);
        onDisplay(cell);
    }

    Matrix.bind('wyvern', 'display', onDisplay);
    Matrix.bind('wyvern', 'beforeSort', beforeSort);
    Matrix.bind('wyvern', 'afterSort', afterSort);

})(jQuery);