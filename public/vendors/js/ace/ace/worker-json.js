function initBaseUrls(t){require.tlns=t}function initSender(){var t=require(null,"ace/lib/event_emitter").EventEmitter,e=require(null,"ace/lib/oop"),r=function(){};return function(){e.implement(this,t),this.callback=function(t,e){postMessage({type:"call",id:e,data:t})},this.emit=function(t,e){postMessage({type:"event",name:t,data:e})}}.call(r.prototype),new r}if(void 0!==window&&window.document)throw"atempt to load ace worker into main window instead of webWorker";var console={log:function(){var t=Array.prototype.slice.call(arguments,0);postMessage({type:"log",data:t})},error:function(){var t=Array.prototype.slice.call(arguments,0);postMessage({type:"log",data:t})}},window={console:console},normalizeModule=function(t,e){if(-1!==e.indexOf("!")){var r=e.split("!");return normalizeModule(t,r[0])+"!"+normalizeModule(t,r[1])}if("."==e.charAt(0))for(e=t.split("/").slice(0,-1).join("/")+"/"+e;-1!==e.indexOf(".")&&n!=e;){var n=e;e=e.replace(/\/\.\//,"/").replace(/[^\/]+\/\.\.\//,"")}return e},require=function(t,e){if(!e.charAt)throw new Error("worker.js require() accepts only (parentId, id) as arguments");e=normalizeModule(t,e);var r=require.modules[e];if(r)return r.initialized||(r.initialized=!0,r.exports=r.factory().exports),r.exports;var n=e.split("/");n[0]=require.tlns[n[0]]||n[0];var i=n.join("/")+".js";return require.id=e,importScripts(i),require(t,e)};require.modules={},require.tlns={};var main,sender,define=function(t,e,r){if(2==arguments.length?(r=e,"string"!=typeof t&&(e=t,t=require.id)):1==arguments.length&&(r=t,t=require.id),0!==t.indexOf("text!")){var n=function(e,r){return require(t,e,r)};require.modules[t]={factory:function(){var t={exports:{}},e=r(n,t.exports,t);return e&&(t.exports=e),t}}}};onmessage=function(t){var e=t.data;if(e.command){if(!main[e.command])throw new Error("Unknown command:"+e.command);main[e.command].apply(main,e.args)}else if(e.init){initBaseUrls(e.tlns),require(null,"ace/lib/fixoldbrowsers"),sender=initSender();var r=require(null,e.module)[e.classname];main=new r(sender)}else e.event&&sender&&sender._emit(e.event,e.data)},define("ace/lib/fixoldbrowsers",["require","exports","module","ace/lib/regexp","ace/lib/es5-shim"],(function(t,e,r){t("./regexp"),t("./es5-shim")})),define("ace/lib/regexp",["require","exports","module"],(function(t,e,r){var n={exec:RegExp.prototype.exec,test:RegExp.prototype.test,match:String.prototype.match,replace:String.prototype.replace,split:String.prototype.split},i=void 0===n.exec.call(/()??/,"")[1],o=function(){var t=/^/g;return n.test.call(t,""),!t.lastIndex}();o&&i||(RegExp.prototype.exec=function(t){var e,r,s=n.exec.apply(this,arguments);if("string"==typeof t&&s){if(!i&&s.length>1&&function(t,e,r){if(Array.prototype.indexOf)return t.indexOf(e,r);for(var n=r||0;n<t.length;n++)if(t[n]===e)return n;return-1}(s,"")>-1&&(r=RegExp(this.source,n.replace.call(function(t){return(t.global?"g":"")+(t.ignoreCase?"i":"")+(t.multiline?"m":"")+(t.extended?"x":"")+(t.sticky?"y":"")}(this),"g","")),n.replace.call(t.slice(s.index),r,(function(){for(var t=1;t<arguments.length-2;t++)void 0===arguments[t]&&(s[t]=void 0)}))),this._xregexp&&this._xregexp.captureNames)for(var a=1;a<s.length;a++)(e=this._xregexp.captureNames[a-1])&&(s[e]=s[a]);!o&&this.global&&!s[0].length&&this.lastIndex>s.index&&this.lastIndex--}return s},o||(RegExp.prototype.test=function(t){var e=n.exec.call(this,t);return e&&this.global&&!e[0].length&&this.lastIndex>e.index&&this.lastIndex--,!!e}))})),define("ace/lib/es5-shim",["require","exports","module"],(function(t,e,r){function n(t){try{return Object.defineProperty(t,"sentinel",{}),"sentinel"in t}catch(t){}}Function.prototype.bind||(Function.prototype.bind=function(t){var e=this;if("function"!=typeof e)throw new TypeError;var r=p.call(arguments,1),n=function(){if(this instanceof n){var i=function(){};i.prototype=e.prototype;var o=new i,s=e.apply(o,r.concat(p.call(arguments)));return null!==s&&Object(s)===s?s:o}return e.apply(t,r.concat(p.call(arguments)))};return n});var i,o,s,a,c,u,l=Function.prototype.call,h=Array.prototype,f=Object.prototype,p=h.slice,d=l.bind(f.toString),m=l.bind(f.hasOwnProperty);if((c=m(f,"__defineGetter__"))&&(i=l.bind(f.__defineGetter__),o=l.bind(f.__defineSetter__),s=l.bind(f.__lookupGetter__),a=l.bind(f.__lookupSetter__)),Array.isArray||(Array.isArray=function(t){return"[object Array]"==d(t)}),Array.prototype.forEach||(Array.prototype.forEach=function(t){var e=P(this),r=arguments[1],n=0,i=e.length>>>0;if("[object Function]"!=d(t))throw new TypeError;for(;n<i;)n in e&&t.call(r,e[n],n,e),n++}),Array.prototype.map||(Array.prototype.map=function(t){var e=P(this),r=e.length>>>0,n=Array(r),i=arguments[1];if("[object Function]"!=d(t))throw new TypeError;for(var o=0;o<r;o++)o in e&&(n[o]=t.call(i,e[o],o,e));return n}),Array.prototype.filter||(Array.prototype.filter=function(t){var e=P(this),r=e.length>>>0,n=[],i=arguments[1];if("[object Function]"!=d(t))throw new TypeError;for(var o=0;o<r;o++)o in e&&t.call(i,e[o],o,e)&&n.push(e[o]);return n}),Array.prototype.every||(Array.prototype.every=function(t){var e=P(this),r=e.length>>>0,n=arguments[1];if("[object Function]"!=d(t))throw new TypeError;for(var i=0;i<r;i++)if(i in e&&!t.call(n,e[i],i,e))return!1;return!0}),Array.prototype.some||(Array.prototype.some=function(t){var e=P(this),r=e.length>>>0,n=arguments[1];if("[object Function]"!=d(t))throw new TypeError;for(var i=0;i<r;i++)if(i in e&&t.call(n,e[i],i,e))return!0;return!1}),Array.prototype.reduce||(Array.prototype.reduce=function(t){var e=P(this),r=e.length>>>0;if("[object Function]"!=d(t))throw new TypeError;if(!r&&1==arguments.length)throw new TypeError;var n,i=0;if(arguments.length>=2)n=arguments[1];else for(;;){if(i in e){n=e[i++];break}if(++i>=r)throw new TypeError}for(;i<r;i++)i in e&&(n=t.call(void 0,n,e[i],i,e));return n}),Array.prototype.reduceRight||(Array.prototype.reduceRight=function(t){var e=P(this),r=e.length>>>0;if("[object Function]"!=d(t))throw new TypeError;if(!r&&1==arguments.length)throw new TypeError;var n,i=r-1;if(arguments.length>=2)n=arguments[1];else for(;;){if(i in e){n=e[i--];break}if(--i<0)throw new TypeError}do{i in this&&(n=t.call(void 0,n,e[i],i,e))}while(i--);return n}),Array.prototype.indexOf||(Array.prototype.indexOf=function(t){var e=P(this),r=e.length>>>0;if(!r)return-1;var n=0;for(arguments.length>1&&(n=E(arguments[1])),n=n>=0?n:Math.max(0,r+n);n<r;n++)if(n in e&&e[n]===t)return n;return-1}),Array.prototype.lastIndexOf||(Array.prototype.lastIndexOf=function(t){var e=P(this),r=e.length>>>0;if(!r)return-1;var n=r-1;for(arguments.length>1&&(n=Math.min(n,E(arguments[1]))),n=n>=0?n:r-Math.abs(n);n>=0;n--)if(n in e&&t===e[n])return n;return-1}),Object.getPrototypeOf||(Object.getPrototypeOf=function(t){return t.__proto__||(t.constructor?t.constructor.prototype:f)}),!Object.getOwnPropertyDescriptor){Object.getOwnPropertyDescriptor=function(t,e){if("object"!=typeof t&&"function"!=typeof t||null===t)throw new TypeError("Object.getOwnPropertyDescriptor called on a non-object: "+t);if(m(t,e)){var r;if(r={enumerable:!0,configurable:!0},c){var n=t.__proto__;t.__proto__=f;var i=s(t,e),o=a(t,e);if(t.__proto__=n,i||o)return i&&(r.get=i),o&&(r.set=o),r}return r.value=t[e],r}}}(Object.getOwnPropertyNames||(Object.getOwnPropertyNames=function(t){return Object.keys(t)}),Object.create)||(u=null===Object.prototype.__proto__?function(){return{__proto__:null}}:function(){var t={};for(var e in t)t[e]=null;return t.constructor=t.hasOwnProperty=t.propertyIsEnumerable=t.isPrototypeOf=t.toLocaleString=t.toString=t.valueOf=t.__proto__=null,t},Object.create=function(t,e){var r;if(null===t)r=u();else{if("object"!=typeof t)throw new TypeError("typeof prototype["+typeof t+"] != 'object'");var n=function(){};n.prototype=t,(r=new n).__proto__=t}return void 0!==e&&Object.defineProperties(r,e),r});if(Object.defineProperty){var w=n({}),g="undefined"==typeof document||n(document.createElement("div"));if(!w||!g)var v=Object.defineProperty}if(!Object.defineProperty||v){Object.defineProperty=function(t,e,r){if("object"!=typeof t&&"function"!=typeof t||null===t)throw new TypeError("Object.defineProperty called on non-object: "+t);if("object"!=typeof r&&"function"!=typeof r||null===r)throw new TypeError("Property description must be an object: "+r);if(v)try{return v.call(Object,t,e,r)}catch(t){}if(m(r,"value"))if(c&&(s(t,e)||a(t,e))){var n=t.__proto__;t.__proto__=f,delete t[e],t[e]=r.value,t.__proto__=n}else t[e]=r.value;else{if(!c)throw new TypeError("getters & setters can not be defined on this javascript engine");m(r,"get")&&i(t,e,r.get),m(r,"set")&&o(t,e,r.set)}return t}}Object.defineProperties||(Object.defineProperties=function(t,e){for(var r in e)m(e,r)&&Object.defineProperty(t,r,e[r]);return t}),Object.seal||(Object.seal=function(t){return t}),Object.freeze||(Object.freeze=function(t){return t});try{Object.freeze((function(){}))}catch(t){Object.freeze=function(t){return function(e){return"function"==typeof e?e:t(e)}}(Object.freeze)}if(Object.preventExtensions||(Object.preventExtensions=function(t){return t}),Object.isSealed||(Object.isSealed=function(t){return!1}),Object.isFrozen||(Object.isFrozen=function(t){return!1}),Object.isExtensible||(Object.isExtensible=function(t){if(Object(t)===t)throw new TypeError;for(var e="";m(t,e);)e+="?";t[e]=!0;var r=m(t,e);return delete t[e],r}),!Object.keys){var y=!0,b=["toString","toLocaleString","valueOf","hasOwnProperty","isPrototypeOf","propertyIsEnumerable","constructor"],x=b.length;for(var _ in{toString:null})y=!1;Object.keys=function(t){if("object"!=typeof t&&"function"!=typeof t||null===t)throw new TypeError("Object.keys called on a non-object");var e=[];for(var r in t)m(t,r)&&e.push(r);if(y)for(var n=0,i=x;n<i;n++){var o=b[n];m(t,o)&&e.push(o)}return e}}Date.prototype.toISOString&&-1!==new Date(-621987552e5).toISOString().indexOf("-000001")||(Date.prototype.toISOString=function(){var t,e,r,n;if(!isFinite(this))throw new RangeError;for(t=[this.getUTCMonth()+1,this.getUTCDate(),this.getUTCHours(),this.getUTCMinutes(),this.getUTCSeconds()],n=((n=this.getUTCFullYear())<0?"-":n>9999?"+":"")+("00000"+Math.abs(n)).slice(0<=n&&n<=9999?-4:-6),e=t.length;e--;)(r=t[e])<10&&(t[e]="0"+r);return n+"-"+t.slice(0,2).join("-")+"T"+t.slice(2).join(":")+"."+("000"+this.getUTCMilliseconds()).slice(-3)+"Z"}),Date.now||(Date.now=function(){return(new Date).getTime()}),Date.prototype.toJSON||(Date.prototype.toJSON=function(t){if("function"!=typeof this.toISOString)throw new TypeError;return this.toISOString()}),864e13!==Date.parse("+275760-09-13T00:00:00.000Z")&&(Date=function(t){var e=function e(r,n,i,o,s,a,c){var u=arguments.length;if(this instanceof t){var l=1==u&&String(r)===r?new t(e.parse(r)):u>=7?new t(r,n,i,o,s,a,c):u>=6?new t(r,n,i,o,s,a):u>=5?new t(r,n,i,o,s):u>=4?new t(r,n,i,o):u>=3?new t(r,n,i):u>=2?new t(r,n):u>=1?new t(r):new t;return l.constructor=e,l}return t.apply(this,arguments)},r=new RegExp("^(\\d{4}|[+-]\\d{6})(?:-(\\d{2})(?:-(\\d{2})(?:T(\\d{2}):(\\d{2})(?::(\\d{2})(?:\\.(\\d{3}))?)?(?:Z|(?:([-+])(\\d{2}):(\\d{2})))?)?)?)?$");for(var n in t)e[n]=t[n];return e.now=t.now,e.UTC=t.UTC,(e.prototype=t.prototype).constructor=e,e.parse=function(e){var n=r.exec(e);if(n){n.shift();for(var i=1;i<7;i++)n[i]=+(n[i]||(i<3?1:0)),1==i&&n[i]--;var o=+n.pop(),s=+n.pop(),a=n.pop(),c=0;if(a){if(s>23||o>59)return NaN;c=6e4*(60*s+o)*("+"==a?-1:1)}var u=+n[0];return 0<=u&&u<=99?(n[0]=u+400,t.UTC.apply(this,n)+c-126227808e5):t.UTC.apply(this,n)+c}return t.parse.apply(this,arguments)},e}(Date));var j="\t\n\v\f\r   ᠎             　\u2028\u2029\ufeff";if(!String.prototype.trim||j.trim()){j="["+j+"]";var O=new RegExp("^"+j+j+"*"),L=new RegExp(j+j+"*$");String.prototype.trim=function(){return String(this).replace(O,"").replace(L,"")}}var E=function(t){return(t=+t)!=t?t=0:0!==t&&t!==1/0&&t!==-1/0&&(t=(t>0||-1)*Math.floor(Math.abs(t))),t},T="a"!="a"[0],P=function(t){if(null==t)throw new TypeError;return T&&"string"==typeof t&&t?t.split(""):Object(t)}})),define("ace/lib/event_emitter",["require","exports","module"],(function(t,e,r){var n={};n._emit=n._dispatchEvent=function(t,e){this._eventRegistry=this._eventRegistry||{},this._defaultHandlers=this._defaultHandlers||{};var r=this._eventRegistry[t]||[],n=this._defaultHandlers[t];if(r.length||n){"object"==typeof e&&e||(e={}),e.type||(e.type=t),e.stopPropagation||(e.stopPropagation=function(){this.propagationStopped=!0}),e.preventDefault||(e.preventDefault=function(){this.defaultPrevented=!0});for(var i=0;i<r.length&&(r[i](e),!e.propagationStopped);i++);return n&&!e.defaultPrevented?n(e):void 0}},n.setDefaultHandler=function(t,e){if(this._defaultHandlers=this._defaultHandlers||{},this._defaultHandlers[t])throw new Error("The default handler for '"+t+"' is already set");this._defaultHandlers[t]=e},n.on=n.addEventListener=function(t,e){this._eventRegistry=this._eventRegistry||{};var r=this._eventRegistry[t];r||(r=this._eventRegistry[t]=[]),-1==r.indexOf(e)&&r.push(e)},n.removeListener=n.removeEventListener=function(t,e){this._eventRegistry=this._eventRegistry||{};var r=this._eventRegistry[t];if(r){var n=r.indexOf(e);-1!==n&&r.splice(n,1)}},n.removeAllListeners=function(t){this._eventRegistry&&(this._eventRegistry[t]=[])},e.EventEmitter=n})),define("ace/lib/oop",["require","exports","module"],(function(t,e,r){e.inherits=function(){var t=function(){};return function(e,r){t.prototype=r.prototype,e.super_=r.prototype,e.prototype=new t,e.prototype.constructor=e}}(),e.mixin=function(t,e){for(var r in e)t[r]=e[r]},e.implement=function(t,r){e.mixin(t,r)}})),define("ace/mode/json_worker",["require","exports","module","ace/lib/oop","ace/worker/mirror","ace/mode/json/json_parse"],(function(t,e,r){var n=t("../lib/oop"),i=t("../worker/mirror").Mirror,o=t("./json/json_parse"),s=e.JsonWorker=function(t){i.call(this,t),this.setTimeout(200)};n.inherits(s,i),function(){this.onUpdate=function(){var t=this.doc.getValue();try{o(t)}catch(t){var e=this.charToDocumentPosition(t.at-1);return void this.sender.emit("error",{row:e.row,column:e.column,text:t.message,type:"error"})}this.sender.emit("ok")},this.charToDocumentPosition=function(t){var e=0,r=this.doc.getLength(),n=this.doc.getNewLineCharacter().length;if(!r)return{row:0,column:0};for(var i=0;e<r;){var o=this.doc.getLine(e),s=o.length+n;if(i+s>t)return{row:e,column:t-i};i+=s,e+=1}return{row:e-1,column:o.length}}}.call(s.prototype)})),define("ace/worker/mirror",["require","exports","module","ace/document","ace/lib/lang"],(function(t,e,r){var n=t("../document").Document,i=t("../lib/lang"),o=e.Mirror=function(t){this.sender=t;var e=this.doc=new n(""),r=this.deferredUpdate=i.deferredCall(this.onUpdate.bind(this)),o=this;t.on("change",(function(t){e.applyDeltas([t.data]),r.schedule(o.$timeout)}))};(function(){this.$timeout=500,this.setTimeout=function(t){this.$timeout=t},this.setValue=function(t){this.doc.setValue(t),this.deferredUpdate.schedule(this.$timeout)},this.getValue=function(t){this.sender.callback(this.doc.getValue(),t)},this.onUpdate=function(){}}).call(o.prototype)})),define("ace/document",["require","exports","module","ace/lib/oop","ace/lib/event_emitter","ace/range","ace/anchor"],(function(t,e,r){var n=t("./lib/oop"),i=t("./lib/event_emitter").EventEmitter,o=t("./range").Range,s=t("./anchor").Anchor,a=function(t){this.$lines=[],0==t.length?this.$lines=[""]:Array.isArray(t)?this.insertLines(0,t):this.insert({row:0,column:0},t)};(function(){n.implement(this,i),this.setValue=function(t){var e=this.getLength();this.remove(new o(0,0,e,this.getLine(e-1).length)),this.insert({row:0,column:0},t)},this.getValue=function(){return this.getAllLines().join(this.getNewLineCharacter())},this.createAnchor=function(t,e){return new s(this,t,e)},0=="aaa".split(/a/).length?this.$split=function(t){return t.replace(/\r\n|\r/g,"\n").split("\n")}:this.$split=function(t){return t.split(/\r\n|\r|\n/)},this.$detectNewLine=function(t){var e=t.match(/^.*?(\r\n|\r|\n)/m);this.$autoNewLine=e?e[1]:"\n"},this.getNewLineCharacter=function(){switch(this.$newLineMode){case"windows":return"\r\n";case"unix":return"\n";case"auto":return this.$autoNewLine}},this.$autoNewLine="\n",this.$newLineMode="auto",this.setNewLineMode=function(t){this.$newLineMode!==t&&(this.$newLineMode=t)},this.getNewLineMode=function(){return this.$newLineMode},this.isNewLine=function(t){return"\r\n"==t||"\r"==t||"\n"==t},this.getLine=function(t){return this.$lines[t]||""},this.getLines=function(t,e){return this.$lines.slice(t,e+1)},this.getAllLines=function(){return this.getLines(0,this.getLength())},this.getLength=function(){return this.$lines.length},this.getTextRange=function(t){if(t.start.row==t.end.row)return this.$lines[t.start.row].substring(t.start.column,t.end.column);var e=this.getLines(t.start.row+1,t.end.row-1);return e.unshift((this.$lines[t.start.row]||"").substring(t.start.column)),e.push((this.$lines[t.end.row]||"").substring(0,t.end.column)),e.join(this.getNewLineCharacter())},this.$clipPosition=function(t){var e=this.getLength();return t.row>=e&&(t.row=Math.max(0,e-1),t.column=this.getLine(e-1).length),t},this.insert=function(t,e){if(!e||0===e.length)return t;t=this.$clipPosition(t),this.getLength()<=1&&this.$detectNewLine(e);var r=this.$split(e),n=r.splice(0,1)[0],i=0==r.length?null:r.splice(r.length-1,1)[0];return t=this.insertInLine(t,n),null!==i&&(t=this.insertNewLine(t),t=this.insertLines(t.row,r),t=this.insertInLine(t,i||"")),t},this.insertLines=function(t,e){if(0==e.length)return{row:t,column:0};if(e.length>65535){var r=this.insertLines(t,e.slice(65535));e=e.slice(0,65535)}var n=[t,0];n.push.apply(n,e),this.$lines.splice.apply(this.$lines,n);var i=new o(t,0,t+e.length,0),s={action:"insertLines",range:i,lines:e};return this._emit("change",{data:s}),r||i.end},this.insertNewLine=function(t){t=this.$clipPosition(t);var e=this.$lines[t.row]||"";this.$lines[t.row]=e.substring(0,t.column),this.$lines.splice(t.row+1,0,e.substring(t.column,e.length));var r={row:t.row+1,column:0},n={action:"insertText",range:o.fromPoints(t,r),text:this.getNewLineCharacter()};return this._emit("change",{data:n}),r},this.insertInLine=function(t,e){if(0==e.length)return t;var r=this.$lines[t.row]||"";this.$lines[t.row]=r.substring(0,t.column)+e+r.substring(t.column);var n={row:t.row,column:t.column+e.length},i={action:"insertText",range:o.fromPoints(t,n),text:e};return this._emit("change",{data:i}),n},this.remove=function(t){if(t.start=this.$clipPosition(t.start),t.end=this.$clipPosition(t.end),t.isEmpty())return t.start;var e=t.start.row,r=t.end.row;if(t.isMultiLine()){var n=0==t.start.column?e:e+1,i=r-1;t.end.column>0&&this.removeInLine(r,0,t.end.column),i>=n&&this.removeLines(n,i),n!=e&&(this.removeInLine(e,t.start.column,this.getLine(e).length),this.removeNewLine(t.start.row))}else this.removeInLine(e,t.start.column,t.end.column);return t.start},this.removeInLine=function(t,e,r){if(e!=r){var n=new o(t,e,t,r),i=this.getLine(t),s=i.substring(e,r),a=i.substring(0,e)+i.substring(r,i.length);this.$lines.splice(t,1,a);var c={action:"removeText",range:n,text:s};return this._emit("change",{data:c}),n.start}},this.removeLines=function(t,e){var r=new o(t,0,e+1,0),n=this.$lines.splice(t,e-t+1),i={action:"removeLines",range:r,nl:this.getNewLineCharacter(),lines:n};return this._emit("change",{data:i}),n},this.removeNewLine=function(t){var e=this.getLine(t),r=this.getLine(t+1),n=new o(t,e.length,t+1,0),i=e+r;this.$lines.splice(t,2,i);var s={action:"removeText",range:n,text:this.getNewLineCharacter()};this._emit("change",{data:s})},this.replace=function(t,e){if(0==e.length&&t.isEmpty())return t.start;if(e==this.getTextRange(t))return t.end;if(this.remove(t),e)var r=this.insert(t.start,e);else r=t.start;return r},this.applyDeltas=function(t){for(var e=0;e<t.length;e++){var r=t[e],n=o.fromPoints(r.range.start,r.range.end);"insertLines"==r.action?this.insertLines(n.start.row,r.lines):"insertText"==r.action?this.insert(n.start,r.text):"removeLines"==r.action?this.removeLines(n.start.row,n.end.row-1):"removeText"==r.action&&this.remove(n)}},this.revertDeltas=function(t){for(var e=t.length-1;e>=0;e--){var r=t[e],n=o.fromPoints(r.range.start,r.range.end);"insertLines"==r.action?this.removeLines(n.start.row,n.end.row-1):"insertText"==r.action?this.remove(n):"removeLines"==r.action?this.insertLines(n.start.row,r.lines):"removeText"==r.action&&this.insert(n.start,r.text)}}}).call(a.prototype),e.Document=a})),define("ace/range",["require","exports","module"],(function(t,e,r){var n=function(t,e,r,n){this.start={row:t,column:e},this.end={row:r,column:n}};(function(){this.isEqual=function(t){return this.start.row==t.start.row&&this.end.row==t.end.row&&this.start.column==t.start.column&&this.end.column==t.end.column},this.toString=function(){return"Range: ["+this.start.row+"/"+this.start.column+"] -> ["+this.end.row+"/"+this.end.column+"]"},this.contains=function(t,e){return 0==this.compare(t,e)},this.compareRange=function(t){var e,r=t.end,n=t.start;return 1==(e=this.compare(r.row,r.column))?1==(e=this.compare(n.row,n.column))?2:0==e?1:0:-1==e?-2:-1==(e=this.compare(n.row,n.column))?-1:1==e?42:0},this.comparePoint=function(t){return this.compare(t.row,t.column)},this.containsRange=function(t){return 0==this.comparePoint(t.start)&&0==this.comparePoint(t.end)},this.intersects=function(t){var e=this.compareRange(t);return-1==e||0==e||1==e},this.isEnd=function(t,e){return this.end.row==t&&this.end.column==e},this.isStart=function(t,e){return this.start.row==t&&this.start.column==e},this.setStart=function(t,e){"object"==typeof t?(this.start.column=t.column,this.start.row=t.row):(this.start.row=t,this.start.column=e)},this.setEnd=function(t,e){"object"==typeof t?(this.end.column=t.column,this.end.row=t.row):(this.end.row=t,this.end.column=e)},this.inside=function(t,e){return 0==this.compare(t,e)&&(!this.isEnd(t,e)&&!this.isStart(t,e))},this.insideStart=function(t,e){return 0==this.compare(t,e)&&!this.isEnd(t,e)},this.insideEnd=function(t,e){return 0==this.compare(t,e)&&!this.isStart(t,e)},this.compare=function(t,e){return this.isMultiLine()||t!==this.start.row?t<this.start.row?-1:t>this.end.row?1:this.start.row===t?e>=this.start.column?0:-1:this.end.row===t?e<=this.end.column?0:1:0:e<this.start.column?-1:e>this.end.column?1:0},this.compareStart=function(t,e){return this.start.row==t&&this.start.column==e?-1:this.compare(t,e)},this.compareEnd=function(t,e){return this.end.row==t&&this.end.column==e?1:this.compare(t,e)},this.compareInside=function(t,e){return this.end.row==t&&this.end.column==e?1:this.start.row==t&&this.start.column==e?-1:this.compare(t,e)},this.clipRows=function(t,e){if(this.end.row>e)var r={row:e+1,column:0};if(this.start.row>e)var i={row:e+1,column:0};if(this.start.row<t)i={row:t,column:0};if(this.end.row<t)r={row:t,column:0};return n.fromPoints(i||this.start,r||this.end)},this.extend=function(t,e){var r=this.compare(t,e);if(0==r)return this;if(-1==r)var i={row:t,column:e};else var o={row:t,column:e};return n.fromPoints(i||this.start,o||this.end)},this.isEmpty=function(){return this.start.row==this.end.row&&this.start.column==this.end.column},this.isMultiLine=function(){return this.start.row!==this.end.row},this.clone=function(){return n.fromPoints(this.start,this.end)},this.collapseRows=function(){return 0==this.end.column?new n(this.start.row,0,Math.max(this.start.row,this.end.row-1),0):new n(this.start.row,0,this.end.row,0)},this.toScreenRange=function(t){var e=t.documentToScreenPosition(this.start),r=t.documentToScreenPosition(this.end);return new n(e.row,e.column,r.row,r.column)}}).call(n.prototype),n.fromPoints=function(t,e){return new n(t.row,t.column,e.row,e.column)},e.Range=n})),define("ace/anchor",["require","exports","module","ace/lib/oop","ace/lib/event_emitter"],(function(t,e,r){var n=t("./lib/oop"),i=t("./lib/event_emitter").EventEmitter,o=e.Anchor=function(t,e,r){this.document=t,void 0===r?this.setPosition(e.row,e.column):this.setPosition(e,r),this.$onChange=this.onChange.bind(this),t.on("change",this.$onChange)};(function(){n.implement(this,i),this.getPosition=function(){return this.$clipPositionToDocument(this.row,this.column)},this.getDocument=function(){return this.document},this.onChange=function(t){var e=t.data,r=e.range;if((r.start.row!=r.end.row||r.start.row==this.row)&&!(r.start.row>this.row||r.start.row==this.row&&r.start.column>this.column)){var n=this.row,i=this.column;"insertText"===e.action?r.start.row===n&&r.start.column<=i?r.start.row===r.end.row?i+=r.end.column-r.start.column:(i-=r.start.column,n+=r.end.row-r.start.row):r.start.row!==r.end.row&&r.start.row<n&&(n+=r.end.row-r.start.row):"insertLines"===e.action?r.start.row<=n&&(n+=r.end.row-r.start.row):"removeText"==e.action?r.start.row==n&&r.start.column<i?i=r.end.column>=i?r.start.column:Math.max(0,i-(r.end.column-r.start.column)):r.start.row!==r.end.row&&r.start.row<n?(r.end.row==n&&(i=Math.max(0,i-r.end.column)+r.start.column),n-=r.end.row-r.start.row):r.end.row==n&&(n-=r.end.row-r.start.row,i=Math.max(0,i-r.end.column)+r.start.column):"removeLines"==e.action&&r.start.row<=n&&(r.end.row<=n?n-=r.end.row-r.start.row:(n=r.start.row,i=0)),this.setPosition(n,i,!0)}},this.setPosition=function(t,e,r){var n;if(n=r?{row:t,column:e}:this.$clipPositionToDocument(t,e),this.row!=n.row||this.column!=n.column){var i={row:this.row,column:this.column};this.row=n.row,this.column=n.column,this._emit("change",{old:i,value:n})}},this.detach=function(){this.document.removeEventListener("change",this.$onChange)},this.$clipPositionToDocument=function(t,e){var r={};return t>=this.document.getLength()?(r.row=Math.max(0,this.document.getLength()-1),r.column=this.document.getLine(r.row).length):t<0?(r.row=0,r.column=0):(r.row=t,r.column=Math.min(this.document.getLine(r.row).length,Math.max(0,e))),e<0&&(r.column=0),r}}).call(o.prototype)})),define("ace/lib/lang",["require","exports","module"],(function(t,e,r){e.stringReverse=function(t){return t.split("").reverse().join("")},e.stringRepeat=function(t,e){return new Array(e+1).join(t)};var n=/^\s\s*/,i=/\s\s*$/;e.stringTrimLeft=function(t){return t.replace(n,"")},e.stringTrimRight=function(t){return t.replace(i,"")},e.copyObject=function(t){var e={};for(var r in t)e[r]=t[r];return e},e.copyArray=function(t){for(var e=[],r=0,n=t.length;r<n;r++)t[r]&&"object"==typeof t[r]?e[r]=this.copyObject(t[r]):e[r]=t[r];return e},e.deepCopy=function(t){if("object"!=typeof t)return t;var e=t.constructor();for(var r in t)"object"==typeof t[r]?e[r]=this.deepCopy(t[r]):e[r]=t[r];return e},e.arrayToMap=function(t){for(var e={},r=0;r<t.length;r++)e[t[r]]=1;return e},e.createMap=function(t){var e=Object.create(null);for(var r in t)e[r]=t[r];return e},e.arrayRemove=function(t,e){for(var r=0;r<=t.length;r++)e===t[r]&&t.splice(r,1)},e.escapeRegExp=function(t){return t.replace(/([.*+?^${}()|[\]\/\\])/g,"\\$1")},e.escapeHTML=function(t){return t.replace(/&/g,"&#38;").replace(/"/g,"&#34;").replace(/'/g,"&#39;").replace(/</g,"&#60;")},e.getMatchOffsets=function(t,e){var r=[];return t.replace(e,(function(t){r.push({offset:arguments[arguments.length-2],length:t.length})})),r},e.deferredCall=function(t){var e=null,r=function(){e=null,t()},n=function(t){return n.cancel(),e=setTimeout(r,t||0),n};return n.schedule=n,n.call=function(){return this.cancel(),t(),n},n.cancel=function(){return clearTimeout(e),e=null,n},n},e.delayedCall=function(t,e){var r=null,n=function(){r=null,t()},i=function(t){r&&clearTimeout(r),r=setTimeout(n,t||e)};return i.delay=i,i.schedule=function(t){null==r&&(r=setTimeout(n,t||0))},i.call=function(){this.cancel(),t()},i.cancel=function(){r&&clearTimeout(r),r=null},i.isPending=function(){return r},i}})),define("ace/mode/json/json_parse",["require","exports","module"],(function(t,e,r){var n,i,o,s,a={'"':'"',"\\":"\\","/":"/",b:"\b",f:"\f",n:"\n",r:"\r",t:"\t"},c=function(t){throw{name:"SyntaxError",message:t,at:n,text:o}},u=function(t){return t&&t!==i&&c("Expected '"+t+"' instead of '"+i+"'"),i=o.charAt(n),n+=1,i},l=function(){var t,e="";for("-"===i&&(e="-",u("-"));i>="0"&&i<="9";)e+=i,u();if("."===i)for(e+=".";u()&&i>="0"&&i<="9";)e+=i;if("e"===i||"E"===i)for(e+=i,u(),"-"!==i&&"+"!==i||(e+=i,u());i>="0"&&i<="9";)e+=i,u();if(t=+e,!isNaN(t))return t;c("Bad number")},h=function(){var t,e,r,n="";if('"'===i)for(;u();){if('"'===i)return u(),n;if("\\"===i)if(u(),"u"===i){for(r=0,e=0;e<4&&(t=parseInt(u(),16),isFinite(t));e+=1)r=16*r+t;n+=String.fromCharCode(r)}else{if("string"!=typeof a[i])break;n+=a[i]}else n+=i}c("Bad string")},f=function(){for(;i&&i<=" ";)u()};return s=function(){switch(f(),i){case"{":return function(){var t,e={};if("{"===i){if(u("{"),f(),"}"===i)return u("}"),e;for(;i;){if(t=h(),f(),u(":"),Object.hasOwnProperty.call(e,t)&&c('Duplicate key "'+t+'"'),e[t]=s(),f(),"}"===i)return u("}"),e;u(","),f()}}c("Bad object")}();case"[":return function(){var t=[];if("["===i){if(u("["),f(),"]"===i)return u("]"),t;for(;i;){if(t.push(s()),f(),"]"===i)return u("]"),t;u(","),f()}}c("Bad array")}();case'"':return h();case"-":return l();default:return i>="0"&&i<="9"?l():function(){switch(i){case"t":return u("t"),u("r"),u("u"),u("e"),!0;case"f":return u("f"),u("a"),u("l"),u("s"),u("e"),!1;case"n":return u("n"),u("u"),u("l"),u("l"),null}c("Unexpected '"+i+"'")}()}},function(t,e){var r;return o=t,n=0,i=" ",r=s(),f(),i&&c("Syntax error"),"function"==typeof e?function t(r,n){var i,o,s=r[n];if(s&&"object"==typeof s)for(i in s)Object.hasOwnProperty.call(s,i)&&(void 0!==(o=t(s,i))?s[i]=o:delete s[i]);return e.call(r,n,s)}({"":r},""):r}}));