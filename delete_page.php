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
use \mod_simplelesson\event\page_deleted;

require_once('../../config.php');
defined('MOODLE_INTERNAL') || die();

global $DB;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$sequence = required_param('sequence', PARAM_INT);
$returnto = optional_param('returnto', 'view', PARAM_TEXT);

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

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_context($modulecontext);

$returnview = new moodle_url('/mod/simplelesson/view.php', ['simplelessonid' => $simplelessonid]);

$returnedit = new moodle_url('/mod/simplelesson/edit_lesson.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sesskey' => sesskey()]);

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

// Go back to page where request came from.
if ($returnto == 'edit') {
    redirect($returnedit, get_string('page_deleted', 'mod_simplelesson'), 2);
}
// Default.
redirect($returnview, get_string('page_deleted', 'mod_simplelesson'), 2);
