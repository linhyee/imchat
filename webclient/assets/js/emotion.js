// @author Mr.Lin

(function($){
    var face = [
        {faceName:"微笑", facePath:"0.gif"},
        {faceName:"撇嘴", facePath:"1.gif"},
        {faceName:"色",   facePath:"2.gif"},
        {faceName:"发呆", facePath:"3.gif"},
        {faceName:"得意", facePath:"4.gif"},
        {faceName:"流泪", facePath:"5.gif"},
        {faceName:"害羞", facePath:"6.gif"},
        {faceName:"闭嘴", facePath:"7.gif"},
        {faceName:"大哭", facePath:"9.gif"},
        {faceName:"尴尬", facePath:"10.gif"},
        {faceName:"发怒", facePath:"11.gif"},
        {faceName:"调皮", facePath:"12.gif"},
        {faceName:"龇牙", facePath:"13.gif"},
        {faceName:"惊讶", facePath:"14.gif"},
        {faceName:"难过", facePath:"15.gif"},
        {faceName:"酷",   facePath:"16.gif"},
        {faceName:"冷汗", facePath:"17.gif"},
        {faceName:"抓狂", facePath:"18.gif"},
        {faceName:"吐",   facePath:"19.gif"},
        {faceName:"偷笑", facePath:"20.gif"},
        {faceName:"可爱", facePath:"21.gif"},
        {faceName:"白眼", facePath:"22.gif"},
        {faceName:"傲慢", facePath:"23.gif"},
        {faceName:"饥饿", facePath:"24.gif"},
        {faceName:"困",   facePath:"25.gif"},
        {faceName:"惊恐", facePath:"26.gif"},
        {faceName:"流汗", facePath:"27.gif"},
        {faceName:"憨笑", facePath:"28.gif"},
        {faceName:"大兵", facePath:"29.gif"},
        {faceName:"奋斗", facePath:"30.gif"},
        {faceName:"咒骂", facePath:"31.gif"},
        {faceName:"疑问", facePath:"32.gif"},
        {faceName:"嘘",   facePath:"33.gif"},
        {faceName:"晕",   facePath:"34.gif"},
        {faceName:"折磨", facePath:"35.gif"},
        {faceName:"衰",   facePath:"36.gif"},
        {faceName:"骷髅", facePath:"37.gif"},
        {faceName:"敲打", facePath:"38.gif"},
        {faceName:"再见", facePath:"39.gif"},
        {faceName:"擦汗", facePath:"40.gif"},
        
        {faceName:"抠鼻",   facePath:"41.gif"},
        {faceName:"鼓掌",   facePath:"42.gif"},
        {faceName:"糗大了", facePath:"43.gif"},
        {faceName:"坏笑",   facePath:"44.gif"},
        {faceName:"左哼哼", facePath:"45.gif"},
        {faceName:"右哼哼", facePath:"46.gif"},
        {faceName:"哈欠",   facePath:"47.gif"},
        {faceName:"鄙视",   facePath:"48.gif"},
        {faceName:"委屈",   facePath:"49.gif"},
        {faceName:"快哭了", facePath:"50.gif"},
        {faceName:"阴险",   facePath:"51.gif"},
        {faceName:"亲亲",   facePath:"52.gif"},
        {faceName:"吓",     facePath:"53.gif"},
        {faceName:"可怜",   facePath:"54.gif"},
        {faceName:"菜刀",   facePath:"55.gif"},
        {faceName:"西瓜",   facePath:"56.gif"},
        {faceName:"啤酒",   facePath:"57.gif"},
        {faceName:"篮球",   facePath:"58.gif"},
        {faceName:"乒乓",   facePath:"59.gif"},
        {faceName:"拥抱",   facePath:"78.gif"},
        {faceName:"握手",   facePath:"81.gif"}
    ];

    $.extend($.fn, {
        emotion : function() {
            return this.each(function() {
                $(this).click(function(event) {
                    var s,
                        offset = $(this).offset(),
                        top = offset.top + this.offsetHeight + 2,
                        box = $('#faceDiv');

                    s = '<div id="faceDiv"></div>';

                    if (box.size() == 0) {
                        box = $(s).appendTo($('body'));
                        $(document).bind('click', _close);
                    }

                    if (box.find('img').size() <= 0) {
                        $.each(face, function(i, v){
                            box.append('<img title="'+v.faceName+'" src="./assets/images/face/'+v.facePath+'" />')
                        });

                        box.find('img').click(function(){
                            _insert_at_cursor($('#txt')[0], '['+$(this).attr('title')+']');
                            _close();
                        });
                    }

                    box.css({
                        left: offset.left + this.offsetWidth / 2 + 'px',
                        top: top + 'px'
                    }).click(function(event){
                        event.stopPropagation();
                    });

                    function _insert_at_cursor(myField, myValue) {
                        if (document.selection) {
                            myField.focus();
                            sel = document.selection.createRange();
                            sel.text = myValue;
                            sel.select();
                        } else if (myField.selectionStart || myField.selectionStart == '0') {
                            var startPos = myField.selectionStart;
                            var endPos = myField.selectionEnd;
                            var restoreTop = myField.scrollTop;

                            myField.value = myField.value.substring(0, startPos) +
                                myValue + myField.value.substring(endPos, myField.value.length);
                            if (restoreTop > 0) {
                                myField.scrollTop = restoreTop;
                            }

                            myField.focus();
                            myField.selectionStart = startPos + myValue.length;
                            myField.selectionEnd = startPos + myValue.length;
                        } else {
                            myField.value += myValue;
                            myField.focus();
                        }
                    }

                    function _close() {
                        var box = $('#faceDiv')
                        box.remove();
                        $(document).unbind('click', _close);
                    }
                    event.stopPropagation();
                });
            });
        }
    });
    $.extend({
        parseEmotion : function(str) {
            var p = /\[([\u4e00-\u9fa5]+)\]/gi
            return str.replace(p, function(word,w){
                var i 
                var found;
                for (i = 0; i < face.length; i++) {
                    if (face[i].faceName == w) {
                        found = face[i];
                        break;
                    }
                }
                if (found) {
                    return '<img title="'+w+'" src="./assets/images/face/'+found.facePath+'"/>';
                }
                return word;
            });
        }
    });
})(jQuery);
