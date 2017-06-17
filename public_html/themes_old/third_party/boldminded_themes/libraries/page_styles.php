<?php

/**
 * ExpressionEngine Pages Styles
 *
 * @package     ExpressionEngine
 * @category    Helpers
 * @author      Brian Litzinger
 * @copyright   Copyright 2010 - Brian Litzinger
 * @link        http://boldminded.com/
 */
 
// Shared by the Pages and Structure_Pages classes
// Including the styles in-line so an additional CSS file load isn't required.

$css = '<style type="text/css">
    .dialog.page_listing {
        list-style: none;
        overflow-y: auto;
        overflow-x: hidden;
        max-height: 220px;
        _height: 220px;
        _overflow: auto;
    }
    .dialog.page_listing h4 {
        margin: 12px 0 5px 5px;
    }
    ul.structure_pages {
        margin: 0;
        padding: 0;
    }
    ul.structure_pages,
    ul.structure_pages ul {
        list-style: none;
    }
    ul.structure_pages li .item_wrapper {
        position: relative;
        margin-bottom: 2px;
    }
    ul.structure_pages li ul.listings {
        display: none;
    }
    ul.structure_pages li .item_wrapper.listing {
        opacity: 0.7;
    }
    ul.structure_pages li ul {
        padding-left: 20px
    }
    ul.structure_pages .item_wrapper a {
        display: block;
        padding: 5px;
        text-decoration: none !important;
        border: none !important;
        outline: none;
    }
    ul.structure_pages .item_wrapper a.selected {
        font-weight: bold;
        background: rgba(255, 255, 255, 0.8);
    }
    ul.structure_pages .item_wrapper a.has_listings {
        margin-right: 31px;
    }
    ul.structure_pages .item_wrapper a:hover {
        color: #2A3940 !important;
        /* background: #c2cbd5; */
        background: rgba(0, 0, 0, 0.05);
    }
    ul.structure_pages .item_wrapper a.expand {
        display: block;
        position: absolute;
        top: 0;
        right: 0;
        width: 20px;
        padding: 5px;
        text-align: right;
    }
    ul.structure_pages .round {
        -webkit-border-radius: 3px;
        -moz-border-radius: 3px;
        border-radius: 3px;
    }
    ul.structure_pages a.round_left,
    ul.structure_pages .item_wrapper a.round_left:hover {
        -webkit-border-top-left-radius: 3px;
        -webkit-border-bottom-left-radius: 3px;
        -moz-border-radius-topleft: 3px;
        -moz-border-radius-bottomleft: 3px;
        border-top-left-radius: 3px;
        border-bottom-left-radius: 3px;
    }
    ul.structure_pages a.round_right,
    ul.structure_pages .item_wrapper a.round_right:hover {
        -webkit-border-top-right-radius: 3px;
        -webkit-border-bottom-right-radius: 3px;
        -moz-border-radius-topright: 3px;
        -moz-border-radius-bottomright: 3px;
        border-top-right-radius: 3px;
        border-bottom-right-radius: 3px;
    }
    
    ul.page-list {
        background: url('. $this->EE->config->slash_item('theme_folder_url').'cp_global_images/cat_marker.gif) 5px 0 no-repeat;
    }
    
    .ui-widget-content ul {
        margin: 0;
    }
</style>';