ace.define("ace/mode/abap",["require","exports","module","ace/tokenizer","ace/mode/abap_highlight_rules","ace/mode/folding/coffee","ace/range","ace/mode/text","ace/lib/oop"],(function(E,e,t){function N(){this.$tokenizer=new T((new n).getRules(),"i"),this.foldingRules=new I}var T=E("../tokenizer").Tokenizer,n=E("./abap_highlight_rules").AbapHighlightRules,I=E("./folding/coffee").FoldMode,O=E("../range").Range,R=E("./text").Mode;E("../lib/oop").inherits(N,R),function(){this.getNextLineIndent=function(E,e,t){return this.$getIndent(e)},this.toggleCommentLines=function(E,e,t,N){for(var T=new O(0,0,0,0),n=t;n<=N;++n){var I=e.getLine(n);hereComment.test(I)||(I=commentLine.test(I)?I.replace(commentLine,"$1"):I.replace(indentation,"$&#"),T.end.row=T.start.row=n,T.end.column=I.length+1,e.replace(T,I))}}}.call(N.prototype),e.Mode=N})),ace.define("ace/mode/abap_highlight_rules",["require","exports","module","ace/lib/oop","ace/mode/text_highlight_rules"],(function(E,e,t){var N=E("../lib/oop"),T=E("./text_highlight_rules").TextHighlightRules,n=function(){var E=this.createKeywordMapper({"variable.language":"this",keyword:"ADD ALIAS ALIASES ASSERT ASSIGN ASSIGNING AT BACK CALL CASE CATCH CHECK CLASS CLEAR CLOSE CNT COLLECT COMMIT COMMUNICATION COMPUTE CONCATENATE CONDENSE CONSTANTS CONTINUE CONTROLS CONVERT CREATE CURRENCY DATA DEFINE DEFINITION DEFERRED DELETE DESCRIBE DETAIL DIVIDE DO ELSE ELSEIF ENDAT ENDCASE ENDCLASS ENDDO ENDEXEC ENDFORM ENDFUNCTION ENDIF ENDIFEND ENDINTERFACE ENDLOOP ENDMETHOD ENDMODULE ENDON ENDPROVIDE ENDSELECT ENDTRY ENDWHILE EVENT EVENTS EXEC EXIT EXPORT EXPORTING EXTRACT FETCH FIELDS FORM FORMAT FREE FROM FUNCTION GENERATE GET HIDE IF IMPORT IMPORTING INDEX INFOTYPES INITIALIZATION INTERFACE INTERFACES INPUT INSERT IMPLEMENTATION LEAVE LIKE LINE LOAD LOCAL LOOP MESSAGE METHOD METHODS MODIFY MODULE MOVE MULTIPLY ON OVERLAY OPTIONAL OTHERS PACK PARAMETERS PERFORM POSITION PROGRAM PROVIDE PUT RAISE RANGES READ RECEIVE RECEIVING REDEFINITION REFERENCE REFRESH REJECT REPLACE REPORT RESERVE RESTORE RETURNING ROLLBACK SCAN SCROLL SEARCH SELECT SET SHIFT SKIP SORT SORTED SPLIT STANDARD STATICS STEP STOP SUBMIT SUBTRACT SUM SUMMARY SUPPRESS TABLES TIMES TRANSFER TRANSLATE TRY TYPE TYPES UNASSIGN ULINE UNPACK UPDATE WHEN WHILE WINDOW WRITE OCCURS STRUCTURE OBJECT PROPERTY CASTING APPEND RAISING VALUE COLOR CHANGING EXCEPTION EXCEPTIONS DEFAULT CHECKBOX COMMENT ID NUMBER FOR TITLE OUTPUT WITH EXIT USING INTO WHERE GROUP BY HAVING ORDER BY SINGLE APPENDING CORRESPONDING FIELDS OF TABLE LEFT RIGHT OUTER INNER JOIN AS CLIENT SPECIFIED BYPASSING BUFFER UP TO ROWS CONNECTING EQ NE LT LE GT GE NOT AND OR XOR IN LIKE BETWEEN","constant.language":"TRUE FALSE NULL SPACE","support.type":"c n i p f d t x string xstring decfloat16 decfloat34","keyword.operator":"abs sign ceil floor trunc frac acos asin atan cos sin tan abapOperator cosh sinh tanh exp log log10 sqrt strlen xstrlen charlen numofchar dbmaxlen lines"},"text",!0," ");this.$rules={start:[{token:"string",regex:"`",next:"string"},{token:"string",regex:"'",next:"qstring"},{token:"doc.comment",regex:/^\*.+/},{token:"comment",regex:/".+$/},{token:"invalid",regex:"\\.{2,}"},{token:"keyword.operator",regex:/\W[\-+\%=<>*]\W|\*\*|[~:,\.&$]|->*?|=>/},{token:"paren.lparen",regex:"[\\[({]"},{token:"paren.rparen",regex:"[\\])}]"},{token:"constant.numeric",regex:"[+-]?\\d+\\b"},{token:"variable.parameter",regex:/sy|pa?\d\d\d\d\|t\d\d\d\.|innnn/},{token:"keyword",regex:"WITH\\W+(?:HEADER\\W+LINE|FRAME|KEY)|NO\\W+STANDARD\\W+PAGE\\W+HEADING|EXIT\\W+FROM\\W+STEP\\W+LOOP|BEGIN\\W+OF\\W+(?:BLOCK|LINE)|BEGIN\\W+OF|END\\W+OF\\W+(?:BLOCK|LINE)|END\\W+OF|NO\\W+INTERVALS|RESPECTING\\W+BLANKS|SEPARATED\\W+BY|USING\\W+(?:EDIT\\W+MASK)|WHERE\\W+(?:LINE)|RADIOBUTTON\\W+GROUP|REF\\W+TO|(?:PUBLIC|PRIVATE|PROTECTED)(?:\\W+SECTION)?|DELETING\\W+(?:TRAILING|LEADING)(?:ALL\\W+OCCURRENCES)|(?:FIRST|LAST)\\W+OCCURRENCE|INHERITING\\W+FROM|LINE-COUNT|ADD-CORRESPONDING|AUTHORITY-CHECK|BREAK-POINT|CLASS-DATA|CLASS-METHODS|CLASS-METHOD|DIVIDE-CORRESPONDING|EDITOR-CALL|END-OF-DEFINITION|END-OF-PAGE|END-OF-SELECTION|FIELD-GROUPS|FIELD-SYMBOLS|FUNCTION-POOL|MOVE-CORRESPONDING|MULTIPLY-CORRESPONDING|NEW-LINE|NEW-PAGE|NEW-SECTION|PRINT-CONTROL|RP-PROVIDE-FROM-LAST|SELECT-OPTIONS|SELECTION-SCREEN|START-OF-SELECTION|SUBTRACT-CORRESPONDING|SYNTAX-CHECK|SYNTAX-TRACE|TOP-OF-PAGE|TYPE-POOL|TYPE-POOLS|LINE-SIZE|LINE-COUNT|MESSAGE-ID|DISPLAY-MODE|READ(?:-ONLY)?|IS\\W+(?:NOT\\W+)?(?:ASSIGNED|BOUND|INITIAL|SUPPLIED)"},{token:"variable.parameter",regex:/\w+-\w+(?:-\w+)*/},{token:E,regex:"\\w+\\b"}],qstring:[{token:"constant.language.escape",regex:"''"},{token:"string",regex:"'",next:"start"},{token:"string",regex:".|w+"}],string:[{token:"constant.language.escape",regex:"``"},{token:"string",regex:"`",next:"start"},{token:"string",regex:".|w+"}]}};N.inherits(n,T),e.AbapHighlightRules=n})),ace.define("ace/mode/folding/coffee",["require","exports","module","ace/lib/oop","ace/mode/folding/fold_mode","ace/range"],(function(E,e,t){var N=E("../../lib/oop"),T=E("./fold_mode").FoldMode,n=E("../../range").Range,I=e.FoldMode=function(){};N.inherits(I,T),function(){this.getFoldWidgetRange=function(E,e,t){var N=this.indentationBlock(E,t);if(N)return N;var T=/\S/,I=E.getLine(t),O=I.search(T);if(-1!=O&&"#"==I[O]){for(var R=I.length,r=E.getLength(),S=t,o=t;++t<r;){var i=(I=E.getLine(t)).search(T);if(-1!=i){if("#"!=I[i])break;o=t}}if(o>S){var A=E.getLine(o).length;return new n(S,R,o,A)}}},this.getFoldWidget=function(E,e,t){var N=E.getLine(t),T=N.search(/\S/),n=E.getLine(t+1),I=E.getLine(t-1),O=I.search(/\S/),R=n.search(/\S/);if(-1==T)return E.foldWidgets[t-1]=-1!=O&&O<R?"start":"","";if(-1==O){if(T==R&&"#"==N[T]&&"#"==n[T])return E.foldWidgets[t-1]="",E.foldWidgets[t+1]="","start"}else if(O==T&&"#"==N[T]&&"#"==I[T]&&-1==E.getLine(t-2).search(/\S/))return E.foldWidgets[t-1]="start",E.foldWidgets[t+1]="","";return E.foldWidgets[t-1]=-1!=O&&O<T?"start":"",T<R?"start":""}}.call(I.prototype)}));
