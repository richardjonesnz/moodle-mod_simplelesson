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
 * Prints the simplelesson summary page following an attempt.
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\attempts;
use \mod_simplelesson\output\display_options;
use \mod_simplelesson\output\lesson_summary;
use \mod_simplelesson\event\attempt_completed;

require_once('../../config.php');
global $DB;

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$mode = optional_param('mode', 'preview', PARAM_ALPHA);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
$sequence = optional_param('sequence', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);

$PAGE->set_url('/mod/simplelesson/summary.php',
        array('courseid' => $courseid,
              'simplelessonid' => $simplelessonid,
              'mode' => $mode,
              'attemptid' => $attemptid));

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

/*
    If we got here, the user got to the last page
    and hit the exit link.
    Mode is either preview or attempt.
*/

if ($mode == 'attempt') {

    $answerdata = attempts::get_lesson_answer_data($attemptid);
    attempts::save_lesson_answerdata($answerdata);
    $sessiondata = attempts::get_sessiondata($answerdata);

    // Get user object to display attempt detail and update gradebook.
    $user = attempts::get_attempt_user($attemptid);

    // Show review page (if allowed).
    if (($simplelesson->allowreview) || has_capability('mod/simplelesson:manage', $modulecontext)) {
        // Navigation.
        $navoptions = new \stdClass();
        if ($mode == 'attempt') {
            // Set exit button url.
            $navoptions->review = true;
            $navoptions->home = false;
        } else {
            // Set home button url.
            $navoptions->review = false;
            $navoptions->home = true;
        }
        $navoptions->homeurl = $returnview;
        echo $OUTPUT->render(new lesson_summary($navoptions, $user, $answerdata,
                display_options::get_options()->markdp, $sessiondata));
    }

    // Log the completion event and update the gradebook.
    $event = attempt_completed::create(['objectid' => $attemptid, 'context' => $modulecontext]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot($cm->modname, $simplelesson);
    $event->trigger();

    // Clean up our attempt data.
    attempts::set_attempt_completed($attemptid, $sessiondata);

    // Update the grade for this attempt.
    simplelesson_update_grades($simplelesson, $user->id);

    // Clean up question usage and attempt data.
    $qubaid = attempts::get_usageid($attemptid);
    attempts::remove_usage_data($qubaid);
    $DB->set_field('simplelesson_attempts', 'qubaid', 0, ['id' => $attemptid]);
}

echo $OUTPUT->footer();
