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
 * Manage the attempt records.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\reporting;
use \mod_simplelesson\local\attempts;
use \mod_simplelesson\forms\manage_attempts_select;
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'none', PARAM_ALPHA);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/manage_attempts.php', ['courseid' => $courseid, 'action' => $action]);

require_login($course, true);
$coursecontext = context_course::instance($courseid);

require_capability('mod/simplelesson:manageattempts', $coursecontext);

$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->set_description('');

$simplelesson = $PAGE->cm;

$returnmanage = new moodle_url('/mod/simplelesson/manage_attempts.php', ['courseid' => $courseid,
        'action' => $action]);

if ( ($action == 'delete') && ($attempt != 0) ) {
    $message = attempts::delete_attempt($attemptid) ? get_string('attempt_deleted', 'mod_simplelesson')
                                                    : get_string('attempt_not_deleted', 'mod_simplelesson');
    redirect($returnmanage, $message, 2);

}

$mform = new manage_attempts_select(null, ['courseid' => $courseid, 'action' => $action]);
$sortby = ($data = $mform->get_data()) ? $data->sortby : 'id';
$records = reporting::fetch_course_attempt_data($courseid, $sortby);

echo $OUTPUT->header();
$mform->display();
echo reporting::show_course_attempt_report($records, $courseid);
echo $OUTPUT->footer();
