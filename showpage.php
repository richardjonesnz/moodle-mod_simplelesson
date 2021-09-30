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
use mod_simplelesson\output\showpage;

require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid  = required_param('simplelessonid', PARAM_INT);
$sid = required_param('sid', PARAM_INT);
$mode = optional_param('mode', 'preview', PARAM_TEXT);

global $USER;

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $cm->instance], '*', MUST_EXIST);
$moduleinstance  = $DB->get_record('simplelesson', array('id' => $simplelessonid), '*', MUST_EXIST);

$PAGE->set_url('/mod/simplelesson/showpage.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sid' => $sid]);

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/simplelesson:view', $modulecontext);

$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_heading(format_string($course->fullname));

// For use with the re-direct.
$returnview = new moodle_url('/mod/simplelesson/view.php',
        array('simplelessonid' => $simplelessonid));

// Now get this record.
$lesson = new lesson($simplelessonid);
$page = $lesson->get_page_record($sid);

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
        'objectid' => $sid,
        'context' => $modulecontext,
    ]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('simplelesson_pages', $page);
$event->trigger();

// Prepare data for renderer.
$options = new \stdClass();

// Check first or last pages reached.
$pages = $lesson->count_pages();
$options->next = ($sid < $pages);
$options->prev = ($sid > 1);
$baseurl = new \moodle_url('/mod/simplelesson/showpage.php', ['courseid' => $cm->course,
        'simplelessonid' => $simplelessonid, 'mode' => $mode]);
// Set next and previous page url's.
if ($options->next) {
    $options->nexturl = $baseurl->out(false, ['sid' => ($sid + 1)]);
}
if ($options->prev) {
    $options->prevurl = $baseurl->out(false, ['sid' => ($sid - 1)]);
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
}
// Output.
echo $OUTPUT->header();
echo $OUTPUT->render(new showpage($page, $options));
echo $OUTPUT->footer();