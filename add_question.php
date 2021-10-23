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
 * Add a question to a page
 *
 * @package   mod_simplelesson
 * @copyright 2021 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\lesson;
use \mod_simplelesson\forms\add_question_form;
use \core\output\notification;

require_once('../../config.php');
global $DB;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$sequence = required_param('sequence', PARAM_INT);
$returnto = optional_param('returnto', 'show', PARAM_TEXT);

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

// Get the available questions and check there are some.
$questions = $DB->get_records('question', ['category' => $simplelesson->categoryid]);

if (count($questions) == 0) {
    // Back to where we came from.
    if ($returnto == 'manage') {
        redirect($returnmanage, get_string('noquestions', 'mod_simplelesson'), 2,
                notification::NOTIFY_WARNING);
    } else {
        redirect($returnshow, get_string('noquestions', 'mod_simplelesson'), 2,
                notification::NOTIFY_WARNING);
    }
}

// Instantiate the form.
$mform = new add_question_form(null,
        ['courseid' => $courseid,
              'simplelessonid' => $simplelessonid,
              'sequence' => $sequence,
              'returnto' => $returnto,
              'questions' => $questions,
              'sesskey' => sesskey()]);

// If the cancel button was pressed.
if ($mform->is_cancelled()) {
    // Back to where we came from.
    if ($returnto == 'manage') {
        redirect($returnmanage, get_string('cancelled'), 2);
    } else {
        redirect($returnshow, get_string('cancelled'), 2);
    }
}

// Save the question data.
if ($data = $mform->get_data()) {

    $qdata = new stdClass;
    $i = $data->optradio;
    $qdata->qid = $i;
    $qdata->pageid = $page->id;
    $qdata->simplelessonid = $simplelessonid;
    $qdata->slot = 0;
    $qdata->score = $data->score;

    // Only add the question if it doesn't already exist in this simplelesson.
    $records = $DB->count_records('simplelesson_questions', ['qid' => $qdata->qid,
            'simplelessonid' => $simplelessonid]);
    if ($records == 0) {
        $DB->insert_record('simplelesson_questions', $qdata);
        // Back to where we came from.
        if ($returnto == 'manage') {
            redirect($returnmanage, get_string('question_added', 'mod_simplelesson'), 2,
                    notification::NOTIFY_SUCCESS);
        } else {
            redirect($returnshow, get_string('question_added', 'mod_simplelesson'), 2,
                    notification::NOTIFY_SUCCESS);
        }
    } else {
        if ($returnto == 'manage') {
            redirect($returnmanage, get_string('duplicate_question', 'mod_simplelesson'), 2,
                    notification::NOTIFY_WARNING);
        } else {
            redirect($returnshow, get_string('duplicate_question', 'mod_simplelesson'), 2,
                    notification::NOTIFY_WARNING);
        }
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
