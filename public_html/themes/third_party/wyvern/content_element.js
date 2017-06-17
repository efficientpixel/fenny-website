(function($) {

    var onDisplay = function(cell)
    {
        var $textarea = cell.find('textarea');

        // eval so we're working with arrays/objects, not a string values
        var toolbar = eval('(' + $textarea.data('toolbar') + ')');
        var config = eval('(' + $textarea.data('config') + ')');
        var field_id = $textarea.data('field-id');
        var id = field_id + '_' + Math.floor(Math.random()*10000);

        $textarea.attr('id', id);

        // Reset the ID
        config.field_id = id;

        // Duplicate our settings so it has the new field_id property
        wyvern_config[id] = wyvern_config[field_id];

        init_wyvern(config, toolbar);
    };

    var beforeSort = function(cell)
    {
        var $textarea = cell.find('textarea');
        var html = cell.find('iframe:first')[0].contentDocument.body.innerHTML;
        $textarea.html(html);
    }

    var afterSort = function(cell)
    {
        var $textarea = cell.find('textarea');
        $textarea.remove();
        cell.find('.content_elements_tile_body').append($textarea);

        cell.find('.cke').remove();
        CKEDITOR.remove(CKEDITOR.instances[$textarea.attr('id')]);
        onDisplay(cell);
    }

    ContentElements.bind('wyvern', 'display', onDisplay);
    ContentElements.bind('wyvern', 'beforeSort', beforeSort);
    ContentElements.bind('wyvern', 'afterSort', afterSort);

})(jQuery);