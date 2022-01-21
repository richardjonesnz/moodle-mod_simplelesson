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
 * Manual grading for essay questions
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\forms\essay_grading_form;
use \mod_simplelesson\local\reporting;
use \mod_simplelesson\local\attempts;
use \mod_simplelesson\output\manual_grading;
use \core\output\notification;
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');

global $DB;

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$answerid = required_param('answerid', PARAM_INT);

$moduleinstance  = $simplelessonid;
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);

$pageurl = new moodle_url('/mod/simplelesson/manual_grading.php',
        ['courseid' => $courseid,
        'simplelessonid' => $simplelessonid,
        'answerid' => $answerid,
        'sesskey' => sesskey()]);

$PAGE->set_url($pageurl);

require_login($course, true, $cm);
require_sesskey();

$reportsurl = new moodle_url('/mod/simplelesson/reports.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'report' => 'menu']);

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/simplelesson:manage', $modulecontext);
$PAGE->set_context($modulecontext);
$PAGE->activityheader->set_description('');

$answerdata = reporting::fetch_essay_answer_record($answerid);

// Process the form.
$mform = new essay_grading_form(null,
        ['maxmark' => $answerdata->maxmark,
         'courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'answerid' => $answerid,
         'action' => 'edit']);

if ($mform->is_cancelled()) {
    redirect($reportsurl, get_string('cancelled'), 2,
            notification::NOTIFY_INFO);
}

if ($data = $mform->get_data()) {
    // Update the attempt and answer data.
    attempts::update_attempt_score($answerid, $data->mark);
    simplelesson_update_grades($simplelesson, $answerdata->userid);
    redirect($reportsurl,
            get_string('grade_saved', 'mod_simplelesson'), 2,
            notification::NOTIFY_SUCCESS);
}

// Find the grader info if it exists.
$record = $DB->get_record('simplelesson_questions',
        ['simplelessonid' => $simplelessonid, 'pageid' => $answerdata->pageid], '*', MUST_EXIST);
$extra = $DB->get_record('qtype_essay_options', ['questionid' => $record->qid], '*', MUST_EXIST);

echo $OUTPUT->header();
echo $OUTPUT->render(new manual_grading($answerdata, $extra->graderinfo));
$mform->display();
echo $OUTPUT->footer();
return;
