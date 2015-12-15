/*
 * My Moodle -- a user's personal dashboard
 *
 * This file contains common functions for the dashboard and profile pages.
 *
 * @package    local_dashboard
 * @category   local
 * @copyright  2015 Ktree.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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