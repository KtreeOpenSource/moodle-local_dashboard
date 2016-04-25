/*!
* Zepto HTML5 Drag and Drop Sortable
* Author: James Doyle(@james2doyle) http://ohdoylerules.com
* Repository: https://github.com/james2doyle/zepto-dragswap
* Licensed under the MIT license
*/
$(function() {
    $('.sortable').dragswap({
        dropAnimation: true
    });
    $('.funcs').dragswap({
        dropAnimation: false,
        dropComplete: function() {
            var sortArray = $('.funcs').dragswap('toArray');
            $('#arrayResults').html('[' + sortArray.join(',') + ']');
            var sortJSON = $('.funcs').dragswap('toJSON');
            $('#jsonResults').html(sortJSON);
        }
    });
});