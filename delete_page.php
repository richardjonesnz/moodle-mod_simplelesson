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
 * Delete current page, adjusting sequence numbers as necessary
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_simplelesson\local\lesson;
use \mod_simplelesson\forms\confirm_delete_form;
use \mod_simplelesson\output\delete_page;
use \mod_simplelesson\event\page_deleted;

require_once('../../config.php');
defined('MOODLE_INTERNAL') || die();

global $DB;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$sequence = required_param('sequence', PARAM_INT);
$title = optional_param('title', '', PARAM_ALPHA);

// Set course related variables.
$moduleinstance  = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/delete_page.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sequence' => $sequence]);
require_login($course, true, $cm);
require_sesskey();

$PAGE->set_heading(format_string($course->fullname));

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/simplelesson:manage', $modulecontext);

$PAGE->set_context($modulecontext);
$PAGE->activityheader->set_description('');

$mform = new confirm_delete_form(null, ['courseid' => $courseid,
                                        'simplelessonid' => $simplelessonid,
                                        'sequence' => $sequence,
                                        'title' => $title]);

// If the cancel button was pressed go back to the page.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/simplelesson/showpage.php', ['courseid' => $courseid,
            'simplelessonid' => $simplelessonid, 'sequence' => $sequence]),
            get_string('cancelled', 'mod_simplelesson'), 2);
}

if ($data = $mform->get_data()) {
    // Get this page and the lastpage sequence number.
    $lesson = new lesson($simplelessonid);
    $page = $lesson->get_page_record($sequence);
    $lastpage = $lesson->count_pages();

    // Check if any other pages after this point to this page and fix their links.
    for ($p = $sequence + 1; $p <= $lastpage; $p++) {
        $nextpage = $lesson->get_page_record($p);
        $DB->set_field('simplelesson_pages', 'sequence', ($p - 1), ['id' => $nextpage->id]);
    }

    // Log the page deleted event. Note: $page still holds the requested page tbd.
    $page = $DB->get_record('simplelesson_pages', ['simplelessonid' => $simplelessonid, 'id' => $page->id],
            '*', MUST_EXIST);
    $event = page_deleted::create(['objectid' => $page->id, 'context' => $modulecontext]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('simplelesson_pages', $page);
    $event->trigger();

    // Delete the page.
    $DB->delete_records('simplelesson_pages', ['simplelessonid' => $simplelessonid, 'id' => $page->id]);

    // Delete any question entry relating to the page.
    $result = $DB->count_records('simplelesson_questions', ['simplelessonid' => $simplelessonid,
            'pageid' => $page->id]);
    if ($result >= 1) {
        $DB->delete_records('simplelesson_questions', ['simplelessonid' => $simplelessonid,
                'pageid' => $page->id]);
    }
    // Go back to Page Management.
    redirect(new moodle_url('/mod/simplelesson/edit_lesson.php', ['courseid' => $courseid,
            'simplelessonid' => $simplelessonid, 'sesskey' => sesskey()]),
            get_string('page_deleted', 'mod_simplelesson'), 2);
}

echo $OUTPUT->header();
echo $OUTPUT->render(new delete_page($mform, $title));
echo $OUTPUT->footer();
