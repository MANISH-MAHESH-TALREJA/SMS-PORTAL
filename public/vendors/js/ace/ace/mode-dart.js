ace.define("ace/mode/dart",["require","exports","module","ace/lib/oop","ace/mode/text","ace/tokenizer","ace/mode/dart_highlight_rules","ace/mode/folding/cstyle"],(function(e,t,r){var o=e("../lib/oop"),n=e("./text").Mode,i=e("../tokenizer").Tokenizer,a=e("./dart_highlight_rules").DartHighlightRules,d=e("./folding/cstyle").FoldMode,g=function(){var e=new a;this.foldingRules=new d,this.$tokenizer=new i(e.getRules())};o.inherits(g,n),function(){}.call(g.prototype),t.Mode=g})),ace.define("ace/mode/dart_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/text_highlight_rules"],(function(e,t,r){var o=e("../lib/oop"),n=e("./text_highlight_rules").TextHighlightRules,i=function(){var e=this.createKeywordMapper({"constant.language.dart":"true|false|null","variable.language.dart":"this|super","keyword.control.dart":"try|catch|finally|throw|break|case|continue|default|do|else|for|if|in|return|switch|while|new","keyword.declaration.dart":"abstract|class|extends|external|factory|implements|interface|get|native|operator|set|typedef","storage.modifier.dart":"static|final|const","storage.type.primitive.dart":"void|bool|num|int|double|Dynamic|var|String"},"identifier"),t={token:"string",regex:".+"};this.$rules={start:[{token:"comment",regex:/\/\/.*$/},{token:"comment",regex:/\/\*/,next:"comment"},{token:["meta.preprocessor.script.dart"],regex:"^(#!.*)$"},{token:["keyword.other.import.dart","meta.declaration.dart"],regex:"#(?:\\b)(?:library|import|source|resource)(?:\\b)"},{token:["keyword.other.import.dart","text"],regex:"(?:\\b)(prefix)(\\s*:)"},{regex:"\\bas\\b",token:"keyword.cast.dart"},{regex:"\\?|:",token:"keyword.control.ternary.dart"},{regex:"(?:\\b)(is\\!?)(?:\\b)",token:["keyword.operator.dart"]},{regex:"(<<|>>>?|~|\\^|\\||&)",token:["keyword.operator.bitwise.dart"]},{regex:"((?:&|\\^|\\||<<|>>>?)=)",token:["keyword.operator.assignment.bitwise.dart"]},{regex:"(===?|!==?|<=?|>=?)",token:["keyword.operator.comparison.dart"]},{regex:"((?:[+*/%-]|\\~)=)",token:["keyword.operator.assignment.arithmetic.dart"]},{regex:"=",token:"keyword.operator.assignment.dart"},{token:"string",regex:"'''",next:"qdoc"},{token:"string",regex:'"""',next:"qqdoc"},{token:"string",regex:"'",next:"qstring"},{token:"string",regex:'"',next:"qqstring"},{regex:"(\\-\\-|\\+\\+)",token:["keyword.operator.increment-decrement.dart"]},{regex:"(\\-|\\+|\\*|\\/|\\~\\/|%)",token:["keyword.operator.arithmetic.dart"]},{regex:"(!|&&|\\|\\|)",token:["keyword.operator.logical.dart"]},{token:"constant.numeric",regex:"0[xX][0-9a-fA-F]+\\b"},{token:"constant.numeric",regex:"[+-]?\\d+(?:(?:\\.\\d*)?(?:[eE][+-]?\\d+)?)?\\b"},{token:e,regex:"[a-zA-Z_$][a-zA-Z0-9_$]*\\b"}],comment:[{token:"comment",regex:".*?\\*\\/",next:"start"},{token:"comment",regex:".+"}],qdoc:[{token:"string",regex:".*?'''",next:"start"},t],qqdoc:[{token:"string",regex:'.*?"""',next:"start"},t],qstring:[{token:"string",regex:"[^\\\\']*(?:\\\\.[^\\\\']*)*'",next:"start"},t],qqstring:[{token:"string",regex:'[^\\\\"]*(?:\\\\.[^\\\\"]*)*"',next:"start"},t]}};o.inherits(i,n),t.DartHighlightRules=i})),ace.define("ace/mode/folding/cstyle",["require","exports","module","ace/lib/oop","ace/range","ace/mode/folding/fold_mode"],(function(e,t,r){var o=e("../../lib/oop"),n=(e("../../range").Range,e("./fold_mode").FoldMode),i=t.FoldMode=function(){};o.inherits(i,n),function(){this.foldingStartMarker=/(\{|\[)[^\}\]]*$|^\s*(\/\*)/,this.foldingStopMarker=/^[^\[\{]*(\}|\])|^[\s\*]*(\*\/)/,this.getFoldWidgetRange=function(e,t,r){var o,n=e.getLine(r);if(o=n.match(this.foldingStartMarker)){var i=o.index;return o[1]?this.openingBracketBlock(e,o[1],r,i):e.getCommentFoldRange(r,i+o[0].length,1)}if("markbeginend"===t&&(o=n.match(this.foldingStopMarker))){i=o.index+o[0].length;return o[1]?this.closingBracketBlock(e,o[1],r,i):e.getCommentFoldRange(r,i,-1)}}}.call(i.prototype)}));
