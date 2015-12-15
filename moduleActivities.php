<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local plugin "dashboard" - Settings
 *
 * @package    local_dashboard
 * @copyright   2015 KTree Computer Solutions <opensource@ktree.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
redirect_if_major_upgrade_required();
$edit   = optional_param('edit', null, PARAM_BOOL);
require_login();
$strmymoodle = 'Dashboard';
if (isguestuser()) {
    if (empty($CFG->allowguestmymoodle)) {
        redirect(new moodle_url('/', array('redirect' => 0)));
    }
    $userid = null;
    $USER->editing = $edit = 0;
    $context = context_system::instance();
    $PAGE->set_blocks_editing_capability('moodle/dashboard:configsyspages');
    $header = "$strmymoodle (GUEST)";
} else {
    $userid = $USER->id;
    $context = context_user::instance($USER->id);
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = "$strmymoodle";
}
if (!$currentpage->userid) {
    $context = context_system::instance();
}
$params = array();
$PAGE->set_context($context);
$PAGE->set_url('/local/dashboard/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($header);
$PAGE->set_heading($header);
if (!isguestuser()) {
    if (get_home_page() != HOMEPAGE_MY) {
        if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
            set_user_preference('user_home_page_preference', HOMEPAGE_MY);
        } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
            $PAGE->settingsnav->get('usercurrentsettings')->add(get_string('makethismyhome'), new moodle_url('/my/',
            array('setdefaulthome' => true)), navigation_node::TYPE_SETTING);
        }
    }
}
if ($PAGE->user_allowed_editing()) {
    if ($edit !== null) {
        $USER->editing = $edit;
        if (!$currentpage->userid && $edit) {
            if (!$currentpage = my_copy_page($USER->id, MY_PAGE_PRIVATE)) {
                print_error('mymoodlesetup');
            }
            $context = context_user::instance($USER->id);
            $PAGE->set_context($context);
            $PAGE->set_subpage($currentpage->id);
        }
    } else {
        if ($currentpage->userid) {
            if (!empty($USER->editing)) {
                $edit = 1;
            } else {
                $edit = 0;
            }
        } else {
            $USER->editing = $edit = 0;
        }
    }
    $params = array('edit' => !$edit);
    if (!$currentpage->userid) {
        $editstring = get_string('updatemymoodleon');
        $params['edit'] = 1;
    } else if (empty($edit)) {
        $editstring = get_string('updatemymoodleon');
    } else {
        $editstring = get_string('updatemymoodleoff');
    }
    $url = new moodle_url("$CFG->wwwroot/local/dashboard/index.php", $params);
    $button = $OUTPUT->single_button($url, $editstring);
    $PAGE->set_button($button);

} else {
    $USER->editing = $edit = 0;
}
if ($currentpage->userid == 0) {
    $CFG->blockmanagerclass = 'my_syspage_block_manager';
}
echo $OUTPUT->header();
$enrolresults = enrol_get_my_courses('summary', 'visible DESC,sortorder ASC');
$coursesarray = array();
foreach ($enrolresults as $enrol) {
    $coursesarray[] = $enrol->id;
}
$courses = implode(",", $coursesarray);
$flag = 0;
if ($courses != null) {
    $moddetails = $DB->get_records_sql("select id ,name from {modules}");
    $table = "<table style='width: 70%;'  align='center'><tr><th> ".get_string('tblheadcoursename',
    'local_dashboard')."</th><th> ".get_string('tblheadmodulename', 'local_dashboard')."</th></tr>";
    foreach ($moddetails as $modkey => $modval) {
        $param['moduleid'] = $modval->id;
        $sqlquery = "SELECT a.name AS name,a.course AS courseid,cm.id AS id,cs.fullname AS cfname
                     FROM {course} cs join {".$modval->name."} a
					      ON cs.id=a.course JOIN {course_modules} cm
						  ON a.course= cm.course AND a.id=cm.instance
						  WHERE cm.module = :moduleid AND a.course
						  IN($courses)";
        $assign = $DB->get_records_sql($sqlquery , $param);
        if ($assign != null) {
            $flag = 1;
            $table .= "<tr><td colspan=2 align=center><b>".ucfirst($modval->name)."</b></td></tr>";
            foreach ($assign as $name => $val) {
                $table .= '<tr><td align = "center"><a class = "dashboardlink" href="'.
                $url = new moodle_url($CFG->wwwroot.'/course/view.php' , array('id' => $val->courseid)).'">'.
                $val->cfname.'</a></td>';
                $table .= '<td align = "center"><a class = "dashboardlink" href = "'.
                $url = new moodle_url($CFG->wwwroot.'/mod/'.$modval->name.'/view.php' , array('id' => $val->id)).'">'.
                $val->name.'</a></td></tr>';
            }
        }
    }
    $table .= "</table>";
}
if ($flag == 0) {
    echo get_string('noactivities', 'local_dashboard');
} else {
    echo $table;
}
echo $OUTPUT->footer();
