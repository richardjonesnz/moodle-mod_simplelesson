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
use mod_simplelesson\output\display_options;
use mod_simplelesson\output\showpage;

require_once('../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__).'/lib.php');
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid  = required_param('simplelessonid', PARAM_INT);
$sequence = required_param('sequence', PARAM_INT);
$mode = optional_param('mode', 'preview', PARAM_ALPHA);
$starttime = optional_param('starttime', 0, PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);

global $USER;

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/simplelesson/showpage.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sequence' => $sequence]);

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/simplelesson:view', $modulecontext);

$PAGE->set_context($modulecontext);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->set_description('');

// For use with the re-direct.
$returnview = new moodle_url('/mod/simplelesson/view.php', ['simplelessonid' => $simplelessonid]);

// Get the pages and the currently displayed page.
$lesson = new lesson($simplelessonid);
$pages = $lesson->get_pages();
$page = $lesson->get_page_record($sequence);

// Check if there is a question on this page.
$pagehasquestion = $DB->get_record('simplelesson_questions', ['pageid' => $page->id], '*', IGNORE_MISSING);

// Load up the usage and get the question type.
if ( ($pagehasquestion) && ($mode == 'attempt') ) {
    $attempt = $DB->get_record('simplelesson_attempts', ['id' => $attemptid], '*', MUST_EXIST);
    $quba = \question_engine::load_questions_usage_by_activity($attempt->qubaid);
    $record = $DB->get_record('question', ['id' => $pagehasquestion->qid], 'qtype', MUST_EXIST);
    $qtype = $record->qtype;
}

$actionurl = new moodle_url('/mod/simplelesson/showpage.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sequence' => $sequence,
         'mode' => $mode,
         'attemptid' => $attemptid]);

$viewurl = new \moodle_url('/mod/simplelesson/view.php', ['simplelessonid' => $simplelessonid]);

// Check if data submitted.
if (data_submitted() && confirm_sesskey()) {

    $transaction = $DB->start_delegated_transaction();
    $quba = \question_engine::load_questions_usage_by_activity($attempt->qubaid);
    $timenow = time();
    $quba->process_all_actions($timenow);
    question_engine::save_questions_usage_by_activity($quba);
    $transaction->allow_commit();

    // Force finish the deferred question on save. But not if
    // it's an essay where we want multiple saves allowed.
    $slot = attempts::get_slot($simplelessonid, $page->id);
    $preferred = $quba->get_preferred_behaviour();
    if ( (($preferred == 'deferredfeedback') || ($preferred == 'deferredcbm')) && ($qtype != 'essay') ) {
            $quba->finish_question($slot);
    }

    /* Record results here for each answer.
    qatid is entry in question_attempts table
    attemptid is from start_attempt (includes user id), that's our own question_attempts table.
    pageid gives us also the question info, such as slot and question number.

    We will keep this data because we will remove the attempt data from the question_attempts table during cleanup.
    */
    $qatid = attempts::get_question_attempt_id($attempt->qubaid, $slot);
    $answerdata = new stdClass();
    $answerdata->id = 0;
    $answerdata->simplelessonid = $simplelessonid;
    $answerdata->qatid = $qatid;
    $answerdata->attemptid = $attemptid;
    $answerdata->pageid = $page->id;
    $answerdata->maxmark = $quba->get_question_max_mark($slot);

    // Get the score associated with this question (if any).
    $qscore = attempts::fetch_question_score($simplelessonid, $page->id);

    // Check if the user has allocated a specific mark from the question management page.
    // How many decimals to use in calculations.
    $markdp = display_options::get_options()->markdp;

    if ($qscore == 0) {
        $qscore = $answerdata->maxmark;
    } else {
        $answerdata->maxmark = round($qscore, $markdp);
    }
    // Calculate a score for the question.
    $answerdata->mark = round((float) $quba->get_question_fraction($slot) * $qscore, $markdp);
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

/* ---------------- Prepare the page for display ------------------  */

if (!$page) {
    // Page record was not found.
    redirect($returnview, get_string('lackpages', 'mod_simplelesson'), 2);
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

$baseurl = new \moodle_url('/mod/simplelesson/showpage.php', ['courseid' => $cm->course,
        'simplelessonid' => $simplelessonid, 'mode' => $mode, 'starttime' => $starttime,
         'attemptid' => $attemptid]);

// Prepare data for output class.
$options = new \stdClass();
$options->pagehasquestion = $pagehasquestion;
$options->qid = ($pagehasquestion) ? $pagehasquestion->qid : 0;
$options->homeurl = $returnview;
$options->nexturl = $baseurl->out(false, ['sequence' => ($sequence + 1)]);
$options->prevurl = $baseurl->out(false, ['sequence' => ($sequence - 1)]);
$options->ispreview = ($mode == 'preview');

// Check for manage capability and enable management buttons.
$options->canmanage = (has_capability('mod/simplelesson:manage', $modulecontext));

// Navigation allowed by default.
$options->next = $options->prev = $options->home = true;

// Can a question be added here?
$options->canaddquestion = ( !($options->pagehasquestion) && (has_capability('mod/simplelesson:managequestions', $modulecontext)) );

// Prepare question page.
$answered = false;
$renderer = $PAGE->get_renderer('mod_simplelesson');

// If there is a question and this is an attempt, show the question.
if ( ($options->pagehasquestion) && !($options->ispreview) ) {

    $slot = $DB->get_field('simplelesson_questions', 'slot', ['simplelessonid' => $simplelessonid,
            'pageid' => $page->id]);

    $options->qform = $renderer->render_question_form($actionurl, display_options::get_options(),
            $slot, $quba, time(), $qtype);

    // Check if the question was answered.
    $answered = attempts::is_answered($simplelessonid, $attemptid, $page->id);
}

/* Navigation controls appear after question answered or if incomplete attempts
   are allowed or if this is not a question page or is a preview. */
if ( ($answered) || ($simplelesson->allowincomplete) || !($pagehasquestion) ||
        ($options->ispreview) ) {
    $options->next = ($sequence < count($pages));
    $options->prev = ($sequence > 1);
}

// Always show the home page.
$options->home = true;

// Special case of last page.
$options->summary = ( (count($pages) == $sequence) && !($options->ispreview) );

// Show the page index if required (but not during an attempt).
if ( ($simplelesson->showindex) && ($options->ispreview) ) {

    $options->pagelinks = array();
    foreach ($pages as $indexpage) {
        // Make link, but not to current page.
        if ($indexpage->sequence == $sequence) {
            $options->pagelinks[] = $indexpage->pagetitle;
        } else {
            $link = $baseurl->out(false, ['sequence' => $indexpage->sequence]);
            $options->pagelinks[] = \html_writer::link($link, $indexpage->pagetitle);
        }
    }
    $options->pageindex = true;
}

echo $OUTPUT->header();
echo $OUTPUT->render(new showpage($simplelesson, $page, $attemptid, $actionurl, $options));
echo $OUTPUT->footer();
