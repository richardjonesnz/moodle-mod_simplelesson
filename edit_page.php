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
 * Edit a page
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_simplelesson\local\lesson;
use \mod_simplelesson\forms\edit_page_form;
use \mod_simplelesson\utility\utility;
use \core\output\notification;
require_once('../../config.php');
global $DB;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$sequence = required_param('sequence', PARAM_INT);

// Set course related variables.
$moduleinstance = $DB->get_record('simplelesson',
        array('id' => $simplelessonid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid,
        $courseid, false, MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/edit_page.php',
        array('courseid' => $courseid,
              'simplelessonid' => $simplelessonid,
              'sequence' => $sequence));

require_login($course, true, $cm);
require_sesskey();
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

$returnpage = new moodle_url('/mod/simplelesson/showpage.php',
    ['courseid' => $courseid,
     'simplelessonid' => $simplelessonid,
     'sequence' => $sequence,
     'sesskey' => sesskey()]);

// Page link data for this page.
$lesson = new lesson($simplelessonid);
$pagetitles = $lesson->get_page_titles();
$page = $lesson->get_page_record($sequence);

$mform = new edit_page_form(null,
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sequence' => $sequence,
         'context' => $modulecontext,
         'pagetitles' => $pagetitles]);

// If the cancel button was pressed.
if ($mform->is_cancelled()) {
    redirect($returnpage, get_string('cancelled'), 2);
}

$options = utility::get_editor_options($modulecontext);

// If we have data, save it and return.
if ($data = $mform->get_data()) {

    $data->sequence = $sequence;
    $data->simplelessonid = $simplelessonid;
    $data->nextpageid = (int) $data->nextpageid;
    $data->prevpageid = (int) $data->prevpageid;
    $data->id = $page->id;
    $data->timemodified = time();

    $data = file_postupdate_standard_editor(
            $data,
            'pagecontents',
            $options,
            $modulecontext,
            'mod_simplelesson',
            'pagecontents',
            $data->id);

    $DB->update_record('simplelesson_pages', $data);

    // Back to showpage.
    redirect($returnpage, get_string('page_updated', 'mod_simplelesson'), 2, notification::NOTIFY_SUCCESS);
}

// Assign page data to the form.
$page = file_prepare_standard_editor(
        $page,
        'pagecontents',
        $options,
        $modulecontext,
        'mod_simplelesson',
        'pagecontents',
        $page->id);

$mform->set_data($page);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edit_page', 'mod_simplelesson'), 2);
echo get_string('edit_page_form', 'mod_simplelesson');
$mform->display();
echo $OUTPUT->footer();