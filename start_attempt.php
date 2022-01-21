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
 * Start a simplelesson attempt (question anawers recorded)
 *
 * @package   mod_simplelesson
 * @copyright 2021 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\attempts;
use \mod_simplelesson\utility\constants;
use \mod_simplelesson\event\attempt_started;
use \core\output\notification;

require_once('../../config.php');
global $DB, $USER;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/start_attempt.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid]);

require_login($course, true, $cm);

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_heading(format_string($course->fullname));

// Use with redirect.
$returnview = new moodle_url('/mod/simplelesson/view.php', ['simplelessonid' => $simplelessonid]);

// Check attempts (for students).
if (!has_capability('mod/simplelesson:manage', $modulecontext)) {
    $maxattempts = $simplelesson->maxattempts;
    $userattempts = attempts::get_number_of_attempts($USER->id, $simplelessonid);

    if ( ($userattempts >= $maxattempts) && ($maxattempts != 0) ) {
        // Max attempts is exceeded.
        redirect($returnview, get_string('max_attempts_exceeded', 'mod_simplelesson'), 2,
                notification::NOTIFY_ERROR);
    }
}

// Check for questions.
$questionentries = attempts::fetch_attempt_questions($simplelessonid);

if (!empty($questionentries)) {
    // Create usage for this attempt.
    $qubaid = attempts::create_usage($modulecontext, $simplelesson->behaviour, $questionentries,
            $simplelessonid);
} else {
    // No questions.
    redirect($returnview, get_string('no_questions', 'mod_simplelesson', 2));
}

// Count this as starting an attempt, record it.
$attemptdata = new stdClass();
$attemptdata->qubaid = $qubaid;
$attemptdata->courseid = $courseid;
$attemptdata->simplelessonid = $simplelessonid;
$attemptdata->pageid = 0;  // Set this later, per page.
$attemptdata->userid = $USER->id;
$attemptdata->status = constants::MOD_SIMPLELESSON_ATTEMPT_STARTED;
$attemptdata->sessionscore = 0;
$attemptdata->maxscore = attempts::get_maxscore($simplelessonid);
$attemptdata->timetaken = 0;
$attemptdata->timecreated = time();
$attemptdata->timemodified = 0;

// Record an attempt in attempts table.
$attemptid = attempts::set_attempt_start($attemptdata);

// Log the event.
$event = attempt_started::create(['objectid' => $attemptid, 'context' => $modulecontext]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $simplelesson);
$event->trigger();

// Go to to the first lesson page.
$returnshowpage = new moodle_url('/mod/simplelesson/showpage.php',
    ['courseid' => $courseid,
     'simplelessonid' => $simplelessonid,
     'sequence' => 1,
     'mode' => 'attempt',
     'starttime' => time(),
     'attemptid' => $attemptid]);

redirect($returnshowpage, get_string('starting_attempt', 'mod_simplelesson'), 2);
