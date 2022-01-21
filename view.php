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
 * Prints a particular instance of simplelesson
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_simplelesson\event\course_module_viewed;
use mod_simplelesson\output\view;
use mod_simplelesson\local\lesson;
use mod_simplelesson\forms\edit_questions_form;

require_once('../../config.php');
require_once(dirname(__FILE__).'/lib.php');

global $DB, $USER;

// Get a course module or instance id.
$id = optional_param('id', 0, PARAM_INT);
$simplelessonid  = optional_param('simplelessonid', 0, PARAM_INT);

if ($id) {
    // Course module id.
    $cm = get_coursemodule_from_id('simplelesson', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $simplelesson = $DB->get_record('simplelesson', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($simplelessonid) {
    // Simplelesson instance id.
    $simplelesson = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $simplelesson->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('simplelesson', $simplelesson->id, $course->id, false,
            MUST_EXIST);
}

// Set page and check permissions.
$modulecontext = context_module::instance($cm->id);
$PAGE->set_url('/mod/simplelesson/view.php', ['id' => $cm->id]);

require_login($course, true, $cm);
require_capability('mod/simplelesson:view', $modulecontext);

// This object holds the options for the view page template.
$options = new \stdClass();
$options->canmanage = has_capability('mod/simplelesson:manage', $modulecontext);

$PAGE->set_title(format_string($simplelesson->name));
$PAGE->set_heading(format_string($course->fullname));

// Log the module viewed event.
$event = course_module_viewed::create(['objectid' => $cm->id, 'context' => $modulecontext]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $simplelesson);
$event->trigger();

// Set completion.
// if we got this far, we can consider the activity "viewed".
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Show add page button if permitted.
if ($options->canmanage) {

    $editlessonurl = new \moodle_url('/mod/simplelesson/edit_lesson.php',
            ['courseid' => $course->id,
             'simplelessonid' => $simplelesson->id,
             'sesskey' => sesskey()]);
    $options->editlesson = $editlessonurl->out(false);

    // If can add a question.
    if (has_capability('mod/simplelesson:managequestions', $modulecontext)) {
        $editquestionsurl = new \moodle_url('/mod/simplelesson/edit_questions.php',
                ['courseid' => $course->id,
                 'simplelessonid' => $simplelesson->id,
                 'sesskey' => sesskey()]);
        $options->editquestions = $editquestionsurl->out(false);
        $options->managequestions = true;
    }

    // Add button on home page.
    $addpageurl = new \moodle_url('/mod/simplelesson/add_page.php',
    ['courseid' => $course->id,
     'simplelessonid' => $simplelesson->id,
     'sequence' => 0,
     'sesskey' => sesskey()]);
    $options->addpage = $addpageurl->out(false);
    $options->addpagehome = true;
}

// Are there any pages yet?
$lesson = new lesson($simplelesson->id);
$options->pages = count($lesson->get_pages());

$options->addq = false; // Can't add a question from here.

if ($options->pages === 0) {
    // No  pages, no next or manage pages, just an add button.
    // Can use the Manage questions interface to set up category though.
    $options->next = false;
    $options->addpagelesson = true;
    $options->addurl = new \moodle_url('/mod/simplelesson/add_page.php',
            ['courseid' => $course->id,
             'simplelessonid' => $simplelesson->id,
             'sesskey' => sesskey()]);
} else {
    // Setup the first page.
    $options->preview = true;
    $nextlink = new \moodle_url('/mod/simplelesson/showpage.php',
            ['courseid' => $course->id,
             'simplelessonid' => $simplelesson->id,
             'sequence' => 1,
             'mode' => 'preview']);
    $options->previewurl = $nextlink->out(false);

    // Start attempt button.
    $options->attempt = true;
    $nextlink = new \moodle_url('/mod/simplelesson/start_attempt.php',
            ['courseid' => $course->id,
             'simplelessonid' => $simplelesson->id,
             'sequence' => 1]);
    $options->attempturl = $nextlink->out(false);
}
$options->prev = false; // This the first page.

// This form allows selection of category and behaviour within a modal.
$mform = new edit_questions_form(null, ['id' => $id, 'simplelessonid' => $simplelesson->id]);
if ($data = $mform->get_data()) {
     $simplelesson->categoryid = $data->categoryid;
     $simplelesson->behaviour = $data->behaviour;
     $DB->update_record('simplelesson', $simplelesson);
}

// Reports tab, if permitted in admin settings.
$config = get_config('mod_simplelesson');
if ($config->enablereports) {
    if (has_capability('mod/simplelesson:viewreportstab', $modulecontext)) {
        $reportslink = new \moodle_url('/mod/simplelesson/reports.php',
                ['courseid' => $course->id, 'simplelessonid' => $simplelesson->id]);
        $options->reportsurl = $reportslink->out(false);

        $viewlink = new \moodle_url('/mod/simplelesson/view.php',
                ['simplelessonid' => $simplelesson->id]);
        $options->viewsurl = $viewlink->out(false);
        $options->reports = true;
    } else {
        $options->reports = false;
    }
}

// Start output to browser.
echo $OUTPUT->header();
echo $OUTPUT->render(new view($simplelesson, $cm->id, $options, $mform));
echo $OUTPUT->footer();
