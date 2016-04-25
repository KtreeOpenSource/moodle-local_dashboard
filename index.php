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
 * @package     local_dashboard
 * @copyright   2015 KTree Computer Solutions <opensource@ktree.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/dashboard/lib.php');
$PAGE->set_pagelayout('frontpage');
redirect_if_major_upgrade_required();
$edit   = optional_param('edit', null, PARAM_BOOL);
require_login();
$strmymoodle = 'Dashboard';
if (!isguestuser()) {
    $userid = $USER->id;
    $context = context_user::instance($USER->id);
    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
    $header = "$strmymoodle";
}
$params = array();
$PAGE->set_url('/local/dashboard/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($header);
$PAGE->set_heading($header);
if (!isguestuser()) {
    if (get_home_page() != HOMEPAGE_MY) {
        if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
            set_user_preference('user_home_page_preference', HOMEPAGE_MY);
        } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
            $PAGE->settingsnav->get('usercurrentsettings')->add(get_string('makethismyhome'),
            new moodle_url('/my/', array('setdefaulthome' => true)), navigation_node::TYPE_SETTING);
        }
    }
}
$PAGE->requires->css('/local/dashboard/css/custom.css', false);
echo $OUTPUT->header();
$ufields = "u.id,u.picture,u.firstname,u.lastname,u.imagealt,u.email,u.lastaccess";
$users = $DB->get_records_sql("SELECT $ufields, COUNT(m.useridfrom) AS count FROM {user} u, {message} m
                               WHERE m.useridto = ? AND u.id = m.useridfrom AND m.notification = 0
							   GROUP BY $ufields", array($USER->id));
$text = '';
if (!empty($users)) {
    $text .= '<table>';
    foreach ($users as $user) {
        $timeago = format_time(time() - $user->lastaccess);
        $text .= '<tr><td><div class = "user"><a class = "dashboardlink" href = "'.
        $url = new moodle_url('/user/view.php' , array('id' => $user->id , 'course' => SITEID ,
        'title' => $timeago)).'">';
        $text .= fullname($user).'</a></div>';
        $link = '/message/index.php?usergroup = unread&id = '.$user->id;
        $anchortagcontents = '<img class = "iconsmall" src = "'.$OUTPUT->pix_url('t/message') . '" alt = "" />&nbsp;'.$user->count;
        $action = null;
        $anchortag = $OUTPUT->action_link($link, $anchortagcontents, $action);
        $text .= '<div class = "message">'.$anchortag.'</div></td></tr>';
    }
        $text .= '</table>';
} else {
        $text = "<table><tr><td class='black'>";
        $text .= get_string('nomessages', 'local_dashboard')."</td></tr>";
        $text .= '<tr><td><a class="dashboardlink" href="'.$url = new moodle_url('/message/index.php').'">'
        .get_string('messages', 'local_dashboard').'</a></td></tr>';
        $text .= "</table>";
}
$enrolresult = enrol_get_my_courses('summary', 'visible DESC,sortorder ASC');
$courses = array();
$workshop = null;
$quiz = null;
$assign = null;
foreach ($enrolresult as $enrol) {
        $courses[] = $DB->get_record('course' , array('id' => $enrol->id));
}
$category = array();
$cours = array();
$shortnames = array();
if (!empty($courses)) {
    $coursecount = 0;
    foreach ($courses as $ci => $cj) {
        if ($coursecount < 6) {
            $modinfo = get_fast_modinfo($cj);
            $mods = $modinfo->get_cms();
            $category = $DB->get_record('course_categories', array('id' => $cj->category));
            $cmid = '';
            foreach ($mods as $modskey => $modvalue) {
                $cmid = $modskey;
                                                   break;
            }
            $cours[$category->name][] = $cj->id.'||'.$cj->shortname.'||'.$cj->format.'||'.$cmid;
            $shortnames[] = "'".$cj->shortname."'";
        }
            $coursecount++;
    }
    $coursetext = "<table class='module-sec'>";
    foreach ($cours as $cname => $cval) {
        foreach ($cval as $vl => $cc) {
            $ts = explode("||", $cc);
            if (is_siteadmin()) {
                    $coursetext .= '<tr><td><a class = "dashboardlink" href = "'.
                    $url = new moodle_url('/course/view.php' , array('id' => $ts[0])).'">'.$ts[1].
                    '</a></td>';
            } else {
                    $coursetext .= '<tr><td><a class = "dashboardlink" href = "'.
                    $url = new moodle_url('/course/view.php' , array('id' => $ts[0])).'">'.$ts[1].'</a></td>';
            }
                    $coursetext .= '</tr>';
        }
    }
    $coursetext .= '</table>';
} else {
    $coursetext = "<table>";
    $coursetext .= '<tr><td>'.get_string('nocourses', 'local_dashboard').'</td></tr>';
    $coursetext .= '<tr><td><a class="dashboardlink" href = "'. $url = new moodle_url('/course/index.php').'">
    Courses</a></td></tr>';
    $coursetext .= "</table>";
}
$blogquery = "select id, subject from {post} where module= 'blog' order by id desc";
$blogrun = $DB->get_records_sql($blogquery);
$blogcount = 1;
if (count($blogrun) > 0) {
    $blogtext = "<table>";
    foreach ($blogrun as $bval) {
        $blogtext .= '<tr><td><a class="dashboardlink" href = "'.
        $url = new moodle_url('/blog/index.php' , array('entryid' => $bval->id)).'">'.$bval->subject.'</a></td></tr>';
        if ($blogcount == 6) {
            break;
        }
        $blogcount ++;
    }
    $blogtext .= '<tr><td><a class = "dashboardlink" href = "'.
    $url = new moodle_url('/blog/edit.php' , array('action' => 'add')).'">'.
    get_string('add_a_new_entry', 'local_dashboard').'</a></td></tr>';
    $blogtext .= "</table>";
} else {
    $blogtext = "<table>";
    $blogtext .= '<tr><td><a class="dashboardlink" href="'.
    $url = new moodle_url('/blog/index.php' , array('userid' => $USER->id)).'">'.
    get_string('view_all_entries', 'local_dashboard').'</a></td></tr>';
    $blogtext .= '<tr><td><a class="dashboardlink" href="'.
    $url = new moodle_url('/blog/edit.php' , array('action' => 'add')).'">'.
    get_string('add_a_new_entry', 'local_dashboard').'</a></td></tr>';
    $blogtext .= "</table>";
}
$enrolresults = enrol_get_my_courses('summary', 'visible DESC,sortorder ASC');
$coursesarray = array();
foreach ($enrolresults as $enrol) {
      $coursesarray[] = $enrol->id;
}
$courses = implode("," , $coursesarray);
if ($courses != null) {
    $assignmoduleid = $DB->get_record_sql("SELECT id as id  from {modules} where name='assign'");
    $assignparam['assignmoduleid'] = $assignmoduleid->id;
    $assignquery = "SELECT a.name AS name,a.course AS courseid,cm.id AS id,cs.fullname AS cfname
	                FROM {course} AS cs join {assign} AS a
					      ON cs.id=a.course
					JOIN {course_modules} AS cm on a.course = cm.course
                          AND a.id = cm.instance
				    WHERE cm.module = :assignmoduleid AND a.course IN($courses)";
    $assign = $DB->get_records_sql($assignquery , $assignparam);
    $quizmoduleid = $DB->get_record_sql("SELECT id AS id  FROM {modules} WHERE name='quiz'");
    $quizparam['quizmoduleid'] = $quizmoduleid->id;
    $quizsql = "SELECT a.name AS name,a.course AS courseid,cm.id AS id,cs.fullname AS cfname
                FROM {course} AS cs
				     JOIN {quiz} AS a
				     ON cs.id=a.course
				JOIN {course_modules} AS cm ON a.course = cm.course
	                 AND a.id=cm.instance
			    WHERE cm.module = :quizmoduleid AND a.course IN($courses)";
    $quiz = $DB->get_records_sql($quizsql , $quizparam);
    $workshopmoduleid = $DB->get_record_sql("SELECT id AS id  FROM {modules} WHERE name='workshop'");
    $workshopparam['workshopmoduleid'] = $workshopmoduleid->id;
    $workshopsql = "SELECT a.name AS name,a.course AS courseid,cm.id AS id,cs.fullname AS cfname
	                FROM {course} AS cs
					     JOIN {workshop} AS a
						      ON cs.id=a.course
						 JOIN {course_modules} AS cm
						 ON a.course= cm.course AND a.id=cm.instance
				    WHERE cm.module= :workshopmoduleid AND a.course IN($courses)";
    $workshop = $DB->get_records_sql($workshopsql , $workshopparam);
}
$asgn = array();
$asgntext = "<table>";
if (!empty($assign)) {
    $asgncount = 0;
    foreach ($assign as $ci => $cj) {
        if ($asgncount < 2) {
            $asgntext .= '<tr>';
            $asgntext .= '<td><a class="dashboardlink" href="'.
            $url = new moodle_url('/mod/assign/view.php' , array('id' => $cj->id)).'">'.$cj->name.'</a></td></tr>';
        }
            $asgncount++;
    }
}
if (!empty($quiz)) {
    $asgncount = 0;
    foreach ($quiz as $ci => $cj) {
        if ($asgncount < 3) {
            $asgntext .= '<tr>';
            $asgntext .= '<td><a class = "dashboardlink" href="'.
            $url = new moodle_url('/mod/quiz/view.php' , array('id' => $cj->id)).'">'.$cj->name.'</a></td></tr>';
        }
            $asgncount++;
    }
}
if (!empty($workshop)) {
    $asgncount = 0;
    foreach ($workshop as $ci => $cj) {
        if ($asgncount < 2) {
            $asgntext .= '<tr>';
            $asgntext .= '<td><a class = "dashboardlink" href = "'.
            $url = new moodle_url('/mod/workshop/view.php' , array('id' => $cj->id)).'">'.$cj->name.'</a></td></tr>';
        }
            $asgncount++;
    }
}
if (($workshop == null) && ($quiz == null) && ($assign == null)) {
    $asgntext .= '<tr><td><td class="black">'.get_string('noassessments', 'local_dashboard').'</td></tr></table>';
}
$asgntext .= "</table>";
?>
<section style="margin-left:center; margin-right:auto;">
    <ul class="sortable grid dashboard dashboard-g">
        <li id="item1" class="widgetli">
            <div class="widget-head" style="min-height: 16% !important;">
              <h3><?php echo get_string('mycourses', 'local_dashboard');?></h3>
                  <span class="right">
<a href="<?php
if (is_siteadmin()) {
    echo $url = new moodle_url('/course/index.php');
} else {
    echo $url = new moodle_url('/my/index.php');
}?>">
            <?php echo get_string('all', 'local_dashboard'); ?></a></span>
            </div>
            <div class="widget-content">
            <p><?php echo $coursetext;?></p>
            </div>
        </li>
        <li id="item2" class="widgetli">
            <div class="widget-head">
                <h3><?php echo get_string('myassessments', 'local_dashboard');?></h3>
                <span class = "right">
                <a href = "<?php echo $url = new moodle_url('/local/dashboard/moduleActivities.php');?>">
                <?php echo get_string('all', 'local_dashboard'); ?></a></span>
            </div>
            <div class="widget-content">
                 <p><?php echo $asgntext;?></p>
            </div>
        </li>
        <li id="item3" class="widgetli">
            <div class="widget-head">
                <h3><?php echo get_string('myblogs', 'local_dashboard');?>
                </h3><span class="right"><a href="<?php echo $url = new moodle_url('/blog/index.php?courseid=1');?>">
                <?php echo get_string('all', 'local_dashboard'); ?></a></span>
            </div>
            <div class="widget-content">
                <p><?php echo $blogtext;?></p>
            </div>
        </li>
        <li id="item4" class="widgetli">
            <div class="widget-head">
                <h3><?php echo get_string('mymessages', 'local_dashboard');?></h3>
                <span class="right"><a href="<?php echo $url = new moodle_url('/message/index.php');?>">
                <?php echo get_string('all', 'local_dashboard'); ?></a></span>
            </div>
            <div class="widget-content">
                <p><?php echo $text;?></p>
            </div>
        </li>
    </ul>
</section>
<?php
$PAGE->requires->js('/local/dashboard/js/zepto.js', false);
$PAGE->requires->js('/local/dashboard/js/zepto.dragswap.js', false);
$PAGE->requires->js('/local/dashboard/js/custom.js', false);
echo $OUTPUT->footer();