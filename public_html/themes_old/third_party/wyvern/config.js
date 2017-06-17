
(function() {
    // Load our plugin that handles the page and asset linking
    CKEDITOR.plugins.addExternal('wyvern',wyvern_config.theme_url+'plugins/wyvern/', 'plugin.js');
    
    // Load any user defined plugins
    if(wyvern_config.extra_plugins)
    {
        // Create a usable array
        extra_plugins = wyvern_config.extra_plugins.split(',');
        
        for(i = 0; i < extra_plugins.length; i++)
        {
            CKEDITOR.plugins.addExternal(extra_plugins[i], wyvern_config.theme_url+'plugins/'+ extra_plugins[i] +'/', 'plugin.js');
        }
    }
})();

CKEDITOR.editorConfig = function( config )
{
    config.extraPlugins = wyvern_config.extra_plugins != '' ? 'wyvern,'+ wyvern_config.extra_plugins : 'wyvern';

    if (window.Assets != "undefined")
    {
        config.dialog_backgroundCoverColor = 'rgb(255, 255, 255)';
        config.dialog_backgroundCoverOpacity = '0.8';
    }
    // Should match EE's default jQuery UI color
    else
    {
        config.dialog_backgroundCoverColor = 'rgb(38, 38, 38)';
        config.dialog_backgroundCoverOpacity = '0.85';
    }

    // Added b/c of new "feature" in CKEditor 4.1
    // http://docs.ckeditor.com/#!/api/CKEDITOR.feature-property-allowedContent
    config.allowedContent = true; 


    config.toolbarCanCollapse = false;
    config.entities_processNumerical = false;
    config.htmlEncodeEntities = false;
    config.htmlEncodeOutput = false;
    config.entities = false;
    config.entities_latin = false;
    
    // Load our skin from a custom URL, otherwise it looks in the ckeditor folder
    config.skin = 'ee,'+ wyvern_config.theme_url +'skins/ee/';
    
    // http://stackoverflow.com/questions/3339710/how-to-configure-ckeditor-to-not-wrap-content-in-p-block
};