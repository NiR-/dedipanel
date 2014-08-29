/*
 * (c) 2010-2014 Dedipanel <http://www.dedicated-panel.net>
 *  
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$(function() {
    if ($('#sidebar ul li.tree').length > 0) {
        $('#sidebar ul li.tree').each(function (el, id) {
            var el = $(this);
            var submenu = el.children('ul.menu_level_1');
            
            el.children('span').bind('click', function () {
                el.toggleClass('in');
                submenu.slideToggle();
            });
        });
    }
    
    if ($('#batch_all').length > 0) {
        var el = $('#batch_all');
        var checkboxes = el.parents('form').find('input[name^=idx]');
        
        el.bind('click', function () {
            if (el.attr('checked')) {
                checkboxes.attr('checked', 'checked');
            }
            else {
                checkboxes.removeAttr('checked');
            }
        });
    }
});
