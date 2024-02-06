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
 * Show all attempts by current user.
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_simplelesson\local\reporting;
use mod_simplelesson\output\review;
use mod_simplelesson\output\display_options;
use mod_simplelesson\output\attempt_summary;
use mod_simplelesson\event\attempt_completed;

require_once('../../config.php');
global $DB;

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);

$PAGE->set_url('/mod/simplelesson/review.php',
        array('courseid' => $courseid,
              'simplelessonid' => $simplelessonid ));

require_login($course, true, $cm);

// Url for redirect.
$returnview = new moodle_url('/mod/simplelesson/view.php', ['simplelessonid' => $simplelessonid]);

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_heading(format_string($course->fullname));

// This option supresses the Description field (module intro).
$PAGE->activityheader->set_description('');

// Get all the attempts by the current user, if permitted.
if (!$simplelesson->allowreports) {
    redirect($returnview, get_string('noreview', 'mod_simplelesson'), 2);
}

$options = new stdClass();
$options->home = $returnview;
// Second parameter is false for current user answers, true for all answers.
$options->records = reporting::fetch_answer_data($simplelessonid, false);
$options->headers = reporting::fetch_answer_report_headers();

echo $OUTPUT->header();
echo $OUTPUT->render(new review($options));
echo $OUTPUT->footer();
