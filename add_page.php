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
 * Add a page at the end of the sequence
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\lesson;
use \mod_simplelesson\utility\utility;
use \mod_simplelesson\forms\edit_page_form;
use \mod_simplelesson\event\page_created;
use \core\output\notification;

require_once('../../config.php');
global $DB;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$returnto = optional_param('returnto', 'view', PARAM_ALPHA);

// Set course related variables.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);

// Set up the page page.
$PAGE->set_url('/mod/simplelesson/add_page.php', ['courseid' => $courseid, 'simplelessonid' => $simplelessonid]);

require_login($course, true, $cm);

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/simplelesson:manage', $modulecontext);

$PAGE->set_context($modulecontext);
$PAGE->activityheader->set_description('');

// For use with the re-direct.
$returnview = new moodle_url('/mod/simplelesson/view.php',
        ['simplelessonid' => $simplelessonid]);
$returnmanage = new moodle_url('/mod/simplelesson/edit_lesson.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid]);

// Set up the lesson object.
$lesson = new lesson($simplelessonid);

// Page data for link dropdown (array keyed by page id).
$pagetitles = $lesson->get_page_titles();

// Get the page editing form.
$mform = new edit_page_form(null,
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'returnto' => $returnto,
         'sequence' => 0,
         'context' => $modulecontext,
         'pagetitles' => $pagetitles]);

// If the cancel button was pressed.
if ($mform->is_cancelled()) {
    // Return to the calling page.
    if ($returnto == 'manage') {
        redirect($returnmanage, get_string('cancelled'), 2);
    }
    redirect($returnview, get_string('cancelled'), 2);
}
/*
 * If we have data, then our job here is to save it and return.
 * We will always add pages at the end, moving pages is handled
 * elsewhere.
 */
if ($data = $mform->get_data()) {

    $lastpage = $lesson->count_pages();
    $data->sequence = $lastpage + 1;
    $data->simplelessonid = $simplelessonid;
    $data->qid = 0;
    $options = utility::get_editor_options($modulecontext);

    // Insert a dummy record and get the id.
    $data->timecreated = time();
    $data->timemodified = time();
    $data->pagecontents = ' ';
    $data->pagecontentsformat = FORMAT_HTML;
    $dataid = $DB->insert_record('simplelesson_pages', $data);

    $data->id = $dataid;

    // Massage the data into a form for saving.
    $data = file_postupdate_standard_editor(
            $data,
            'pagecontents',
            $options,
            $modulecontext,
            'mod_simplelesson',
            'pagecontents',
            $data->id);

    // Update the record with full editor data.
    $DB->update_record('simplelesson_pages', $data);

    // Trigger the page created event.
    $eventparams = ['context' => $modulecontext, 'objectid' => $data->id];
    $event = page_created::create($eventparams);
    $event->add_record_snapshot('course', $PAGE->course);
    $event->add_record_snapshot($PAGE->cm->modname, $simplelesson);
    $event->trigger();

    if ($returnto == 'manage') {
        redirect($returnmanage, get_string('page_saved', 'mod_simplelesson'), 2,
                notification::NOTIFY_SUCCESS);
    }
    redirect($returnview, get_string('page_saved', 'mod_simplelesson'), 2, notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('page_adding', 'mod_simplelesson'), 2);

// Show the form.
$mform->display();

// Finish the page.
echo $OUTPUT->footer();
