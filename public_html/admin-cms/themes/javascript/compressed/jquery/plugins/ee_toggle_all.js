/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.3
 * @filesource
 */
!function(e){e.fn.toggle_all=function(){function t(t){var c=t.find("tbody tr").get();t.data("table_config")&&t.bind("tableupdate",function(){c=t.table("get_current_data").html_rows,t.find("input:checkbox").prop("checked",!1).trigger("change")}),this.getColumn=function(t){return e.map(c,function(c){return e(c.cells[t]).has("input[type=checkbox]").size()?c.cells[t]:void 0})}}var c={$table:"",rowCache:"",column:0,tableCells:[],shift:!1,init:function(e,t,c){this.$table=e,this.rowCache=t,this.column=c,this.tableCells=this.rowCache.getColumn(this.column),this.checkboxListen(),this.tableListen(),this.shiftListen()},checkboxListen:function(){var t=this;e(this.tableCells).each(function(c){e(this).find("input[type=checkbox]").unbind("click").click(function(){if(currentlyChecked=t.checkboxChecked(c),t.shift&&currentlyChecked!==!1){var n=currentlyChecked>c?c:currentlyChecked,i=currentlyChecked>c?currentlyChecked:c;e(t.tableCells).slice(n,i).find("input[type=checkbox]").attr("checked",!0).trigger("change")}})})},tableListen:function(){var e=this;this.$table.bind("tableupdate",function(){e.tableCells=e.rowCache.getColumn(e.column),e.checkboxListen()})},shiftListen:function(){var t=this;e(window).bind("keyup keydown",function(e){t.shift=e.shiftKey})},checkboxChecked:function(t){if(e(this.tableCells).find("input[type=checkbox]").not(":eq("+t+")").find(":checked").size()>1)return!1;var c=0;return e(this.tableCells).each(function(n){return n!==t&&e(this).find("input[type=checkbox]").is(":checked")?(c=n,!1):void 0}),c}};return this.each(function(){var n={checkboxes:{},add:function(e,t){return"undefined"==typeof this.checkboxes[e]&&(this.checkboxes[e]=[]),this.checkboxes[e].push(t),!0},get:function(e){return this.checkboxes[e]},each:function(t,c){e.each(this.checkboxes[t],function(t,n){c.call(e(n),t,n)})}},i=e(this),h=new t(i);i.find("th").has("input:checkbox").each(function(){var t=this.cellIndex,o=e(this).find(":checkbox");e(this).on("click","input[type=checkbox]",function(c){var i=o.prop("checked");c.target!=o.get(0)&&(i=!i,o.prop("checked",i).trigger("change")),e(h.getColumn(t)).find(":checkbox").prop("checked",i).trigger("change"),n.each(t,function(){e(this).prop("checked",i).trigger("change")})}),n.add(t,o),c.init(i,h,t)}),i.delegate("td","click",function(t){var c=this.cellIndex,i=!0;return n.get(c)&&e(t.target).is(":checkbox")?t.target.checked?(e.each(h.getColumn(c),function(){return e(this).find(":checkbox").prop("checked")?void 0:(i=!1,!1)}),void n.each(c,function(){e(this).prop("checked",i).trigger("change")})):(n.each(c,function(){e(this).prop("checked",!1).trigger("change")}),!0):!0})})}}(jQuery);