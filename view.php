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
use stdClass;

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

// Set up the page (supress the activity header).
$PAGE->set_title(format_string($simplelesson->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->set_description('');

// Log the module viewed event.
$event = course_module_viewed::create(['objectid' => $cm->id, 'context' => $modulecontext]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $simplelesson);
$event->trigger();

// Set completion.
// if we got this far, we can consider the activity "viewed".
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Holds options for the first page and its template.
$options = new stdClass();

// Edit lesson shown to users with permission.
$options->canmanage = has_capability('mod/simplelesson:manage', $modulecontext);

// Are there any pages yet?
$lesson = new lesson($simplelesson->id);
$options->pages = $lesson->count_pages();
if ( $options->pages === 0) {
    // No  pages, no next or manage pages, just an edit button.
    $options->next = false;
    $options->attempt = false;
} else {
    $options->next = true;
    // User can preview or attempt this lesson.
    $options->preview = true;
    $options->attempt = true;
}

// Reports tab, if permitted in admin settings and has permission.
$options->reports = ( (get_config('mod_simplelesson')->enablereports) &&
                    (has_capability('mod/simplelesson:viewreportstab', $modulecontext)) );

// Start output to browser.
echo $OUTPUT->header();
echo $OUTPUT->render(new view($simplelesson, $cm->id, $options));
echo $OUTPUT->footer();
