ace.define("ace/ext/static_highlight",["require","exports","module","ace/edit_session","ace/layer/text"],(function(e,t,s){var a=e("../edit_session").EditSession,c=e("../layer/text").Text;t.render=function(e,t,s,r,n){r=parseInt(r||1,10);var i=new a("");i.setMode(t),i.setUseWorker(!1);var o=new c(document.createElement("div"));o.setSession(i),o.config={characterWidth:10,lineHeight:20},i.setValue(e);for(var l=[],d=i.getLength(),u=0;u<d;u++)l.push("<div class='ace_line'>"),n||l.push("<span class='ace_gutter ace_gutter-cell' unselectable='on'>"+(u+r)+"</span>"),o.$renderLine(l,u,!0,!1),l.push("</div>");var p="<div class=':cssClass'>        <div class='ace_editor ace_scroller ace_text-layer'>            :code        </div>    </div>".replace(/:cssClass/,s.cssClass).replace(/:code/,l.join(""));return o.destroy(),{css:".ace_editor {font-family: 'Monaco', 'Menlo', 'Droid Sans Mono', 'Courier New', monospace;font-size: 12px;}.ace_editor .ace_gutter { width: 25px !important;display: block;float: left;text-align: right; padding: 0 3px 0 0; margin-right: 3px;}.ace_line { clear: both; }*.ace_gutter-cell {-moz-user-select: -moz-none;-khtml-user-select: none;-webkit-user-select: none;user-select: none;}"+s.cssText,html:p}}}));
