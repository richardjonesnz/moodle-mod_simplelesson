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
use \mod_simplelesson\local\attempts;
use \mod_simplelesson\output\display_options;
use \mod_simplelesson\output\attempt_summary;
use \mod_simplelesson\event\attempt_completed;

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

echo $OUTPUT->header();

// Get all the attempts by the current user.
if ($simplelesson->allowreview) {
    $records = attempts::get_all_attempts_user($simplelessonid);

    var_dump($records);
    /* Navigation.
    $navoptions = new \stdClass();
    $navoptions->review = true;
    $navoptions->home = false;
    $navoptions->homeurl = $returnview;
    echo $OUTPUT->render(new attempt_summary($navoptions, $records,
            display_options::get_options()->markdp, $sessiondata));
    */
} else {
    redirect($returnview, get_string('noreview', $simplelesson), 2);
}

echo $OUTPUT->footer();