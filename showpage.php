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
 * Shows a simplelesson page
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
use \mod_simplelesson\event\page_viewed;
use \mod_simplelesson\utility\utility;
use mod_simplelesson\local\lesson;
use mod_simplelesson\local\attempts;
use mod_simplelesson\output\question_form;
use mod_simplelesson\output\display_options;
use mod_simplelesson\output\showpage;

require_once('../../config.php');
require_once($CFG->libdir . '/questionlib.php');

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid  = required_param('simplelessonid', PARAM_INT);
$sequence = required_param('sequence', PARAM_INT);
$mode = optional_param('mode', 'preview', PARAM_TEXT);
$starttime = optional_param('starttime', 0, PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);

global $USER;

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/simplelesson/showpage.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sequence' => $sequence,
         'mode' => $mode,
         'startime' => $starttime,
         'attemptid' => $attemptid]);

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/simplelesson:view', $modulecontext);

// Get question display options.
$displayoptions = new display_options();

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

// For use with the re-direct.
$returnview = new moodle_url('/mod/simplelesson/view.php', ['simplelessonid' => $simplelessonid]);

$lesson = new lesson($simplelessonid);
$pages = $lesson->get_pages();
$page = $lesson->get_page_record($sequence);

// Check if there is a question on this page.
$hasquestion = $DB->get_record('simplelesson_questions', ['pageid' => $page->id], '*', IGNORE_MISSING);

// Load up the usage and get the usage.
if ( ($hasquestion) && ($mode == 'attempt') ) {

    $attempt = $DB->get_record('simplelesson_attempts', ['id' => $attemptid], '*', MUST_EXIST);
    $quba = \question_engine::load_questions_usage_by_activity($attempt->qubaid);
    $qtype = $DB->get_record('question', ['id' => $hasquestion->qid], 'qtype', MUST_EXIST);

    $actionurl = $PAGE->url;

    // Check if data submitted.
    if (data_submitted() && confirm_sesskey()) {
        try {
                $transaction = $DB->start_delegated_transaction();
                $quba = \question_engine::load_questions_usage_by_activity($qubaid);
                $timenow = time();
                $quba->process_all_actions($timenow);
                question_engine::save_questions_usage_by_activity($quba);
                $transaction->allow_commit();

                // Force finish the deferred question on save. But not if
                // it's an essay where we want multiple saves allowed.
                $slot = questions::get_slot($simplelessonid, $pageid);
                if ( ($quba->get_preferred_behaviour() == 'deferredfeedback') && ($qtype != 'essay') ) {
                        $quba->finish_question($slot);
                }
                }
        catch (question_out_of_sequence_exception $e) {
                redirect($viewurl, get_string('outofsequence', 'mod_simplelesson'), 2,
                        notification::NOTIFY_WARNING);
                }
        /* Record results here for each answer.
        qatid is entry in question_attempts table
        attemptid is from start_attempt (includes user id), that's our own question_attempts table.
        pageid gives us also the question info, such as slot and question number.

        We will keep this data because we will remove the attempt data from the question_attempts table during cleanup.
        */
        $qatid = attempts::get_question_attempt_id($qubaid, $slot);
        $answerdata = new stdClass();
        $answerdata->id = 0;
        $answerdata->simplelessonid = $simplelessonid;
        $answerdata->qatid = $qatid;
        $answerdata->attemptid = $attemptid;
        $answerdata->pageid = $page->id;
        $answerdata->maxmark = $quba->get_question_max_mark($slot);

        // Get the score associated with this question (if any).
        $qscore = questions::fetch_question_score($simplelessonid, $page->id);
        // Check if the user has allocated a specific mark from the question management page.
        if ($qscore == 0) {
                $qscore = $answerdata->maxmark;
        } else {
                $answerdata->maxmark = round($qscore, $options->markdp);
        }
        // Calculate a score for the question.
        $mark = (float) $quba->get_question_fraction($slot);
        $answerdata->mark = round($mark * $qscore, $options->markdp);
        $answerdata->questionsummary = $quba->get_question_summary($slot);
        $answerdata->qtype = $qtype; // For manual essay marking.
        $answerdata->rightanswer = $quba->get_right_answer_summary($slot);
        $answerdata->timetaken = 0;
        $answerdata->timestarted = $starttime;
        $answerdata->timecompleted = $timenow;
        // Calculate the elapsed time (s).
        $answerdata->timetaken = ($answerdata->timecompleted - $answerdata->timestarted);

        // Check if question has a valid answer.
        $state = $quba->get_question_state($slot);
        $answerdata->stateclass = $state->get_state_class(false);

        if ($qtype == 'essay') {
                // Special case, has additional save option.
                $submitteddata = $quba->extract_responses($slot);
                $answerdata->youranswer = $submitteddata['answer'];
                // Set mark negative (indicate needs grading).
                $answerdata->mark = -1;

        } else {
                $answerdata->youranswer = $quba->get_response_summary($slot);
        }
        // Save might be done several times. Check if exists.
        $answerdata->id = attempts::update_answer($answerdata);
        redirect($actionurl);
    }
}
/* ---------------- Prepare the page for display ------------------  */

// Now get this record.
$lesson = new lesson($simplelessonid);
$page = $lesson->get_page_record($sequence);
$pages = $lesson->get_pages();

if (!$page) {
    // page record was not found.
    redirect($returnview, get_string('pagenotfound', 'mod_simplelesson'), 2);
}

// Prepare page text, re-write urls.
$contextid = $modulecontext->id;
$page->pagecontents = \file_rewrite_pluginfile_urls(
        $page->pagecontents,
        'pluginfile.php',
        $contextid,
        'mod_simplelesson',
        'pagecontents',
        $page->id);

// Run the pagecontents through format_text to enable media.
$formatoptions = utility::get_formatting_options($modulecontext);
$page->pagecontents = format_text($page->pagecontents, FORMAT_HTML, $formatoptions);

// Log the page viewed event.
$event = page_viewed::create([
        'objectid' => $sequence,
        'context' => $modulecontext,
    ]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('simplelesson_pages', $page);
$event->trigger();

// Prepare data for output class.
$options = new \stdClass();

// isattempt and ispreview determine how to display any questions.
$options->isattempt = false;

// Check first or last pages reached.
$lesson = new lesson($simplelessonid);
$pages = $lesson->get_pages();
$pagecount = count($pages);
$options->next = ($sequence < $pagecount);
$options->prev = ($sequence > 1);

$baseurl = new \moodle_url('/mod/simplelesson/showpage.php', ['courseid' => $cm->course,
        'simplelessonid' => $simplelessonid, 'mode' => $mode, 'starttime' => $starttime,
         'attemptid' => $attemptid]);

// Set home button url
$options->home = true;
$options->homeurl = $returnview;

// Set next and previous page url's.
if ($options->next) {
    $options->nexturl = $baseurl->out(false, ['sequence' => ($sequence + 1)]);
}
if ($options->prev) {
    $options->prevurl = $baseurl->out(false, ['sequence' => ($sequence - 1)]);
}
// Check for manage capability.
$options->canmanage = has_capability('mod/simplelesson:manage', $modulecontext);

if ($options->canmanage) {
    $addpageurl = new \moodle_url('/mod/simplelesson/add_page.php',
            ['courseid' => $course->id,
             'simplelessonid' => $simplelessonid,
             'sequence' => 0,
             'sesskey' => sesskey()]);
    $options->addpage = $addpageurl->out(false);

    $deletepageurl = new \moodle_url('/mod/simplelesson/delete_page.php',
            ['courseid' => $course->id,
             'simplelessonid' => $simplelesson->id,
             'sequence' => $page->sequence,
             'returnto' => 'view',
             'sesskey' => sesskey()]);
    $options->deletepage = $deletepageurl->out(false);
    $options->delete = true;

    $editpageurl = new \moodle_url('/mod/simplelesson/edit_page.php',
            ['courseid' => $course->id,
            'simplelessonid' => $simplelesson->id,
            'sequence' => $page->sequence,
            'sesskey' => sesskey()]);
    $options->editpage = $editpageurl->out(false);
    $options->edit = true;

    // Show the add button if no question, otherwise delete button.
    if(!$hasquestion) {
        $addquestionurl = new \moodle_url('/mod/simplelesson/add_question.php',
                ['courseid' => $course->id,
                'simplelessonid' => $simplelesson->id,
                'sequence' => $sequence,
                'returnto' => 'show',
                'sesskey' => sesskey()]);
        $options->addquestion = $addquestionurl->out(false);
        $options->addq = true;
        $options->ispreview = false;
    } else {
        $deletequestionurl = new \moodle_url('/mod/simplelesson/delete_question.php',
                ['courseid' => $course->id,
                 'simplelessonid' => $simplelesson->id,
                 'sequence' => $sequence,
                 'returnto' => 'show',
                 'sesskey' => sesskey()]);
        $options->deletequestion = $deletequestionurl->out(false);
        $options->deleteq = true;
        $options->previewurl = new \moodle_url('/question/bank/previewquestion/preview.php',
                ['id' => $hasquestion->qid,
                 'returnurl' => $PAGE->url]);
        $options->ispreview = true;
    }

    $editlessonurl = new \moodle_url('/mod/simplelesson/edit_lesson.php',
            ['courseid' => $course->id,
             'simplelessonid' => $simplelesson->id,
             'sesskey' => sesskey()]);
    $options->editlesson = $editlessonurl->out(false);
}

// Output.
echo $OUTPUT->header();
$answered = false;

// If there is a question and this is an attempt, show the question.
if ($hasquestion) {

    //Set the options relating to the question form.
    $options->slot = $DB->get_field('simplelesson_questions', 'slot',
            ['simplelessonid' => $simplelessonid, 'pageid' => $page->id]);

    $options->headtags = '' . $quba->render_question_head_html($options->slot);
    $options->qhtml = $quba->render_question($options->slot, $displayoptions);

    // Essay type must allow save button enabled, otherwise user selected.
    if ($qtype == 'essay') {
        $options->behaviour = 'deferredfeedback';
    } else {
        $options->behaviour = $simplelesson->behaviour;
    }
    $options->ispreview = false;
    $options->isattempt = true;
   // Check if the question was answered.
   // $answered = attempts::is_answered($simplelessonid, $attemptid, $page->id);
}

echo $OUTPUT->render(new showpage($page, $options));
echo $OUTPUT->footer();