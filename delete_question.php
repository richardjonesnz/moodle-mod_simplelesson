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
 * Remove a question from a page
 *
 * @package   mod_simplelesson
 * @copyright 2021 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\lesson;
use \core\output\notification;

require_once('../../config.php');
global $DB;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$sequence = required_param('sequence', PARAM_INT);
$returnto = optional_param('returnto', 'show', PARAM_ALPHA);

// Set course related variables.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);
$thispageurl = new moodle_url('/mod/simplelesson/add_question.php',
        ['courseid' => $courseid, 'simplelessonid' => $simplelessonid]);
// Set up the page page.
$PAGE->set_url($thispageurl);

require_login($course, true, $cm);
require_sesskey();

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/simplelesson:managequestions', $modulecontext);

$PAGE->set_context($modulecontext);

$lesson = new lesson($simplelessonid);
$page = $lesson->get_page_record($sequence);

$returnshow = new moodle_url('/mod/simplelesson/showpage.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sequence' => $sequence]);

$returnmanage = new moodle_url('/mod/simplelesson/edit_lesson.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sequence' => $sequence,
         'sesskey' => sesskey()]);

         // Check if there is a question on this page.
$result = $DB->count_records('simplelesson_questions', ['simplelessonid' => $simplelessonid,
        'pageid' => $page->id]);

$notify = ($result >= 1) ? notification::NOTIFY_SUCCESS : notification::NOTIFY_WARNING;
$DB->delete_records('simplelesson_questions', ['simplelessonid' => $simplelessonid,
        'pageid' => $page->id]);
// Back to where we came from.
if ($returnto == 'manage') {
        redirect($returnmanage, get_string('question_deleted', 'mod_simplelesson'), 2, $notify);
} else {
        redirect($returnshow, get_string('question_deleted', 'mod_simplelesson'), 2, $notify);
}
