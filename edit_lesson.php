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
 * Edit a lesson structure.
 *
 * @package   mod_simplelesson
 * @copyright 2021 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 use mod_simplelesson\local\lesson;
 use mod_simplelesson\output\lesson_editing;
 use mod_simplelesson\forms\edit_questions_form;

require_once('../../config.php');

global $DB;

// Fetch URL parameters.
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$action = optional_param('action', 'none', PARAM_ALPHA);
$sequence = optional_param('sequence', 0, PARAM_INT);

// Set course related variables.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $cm->instance], '*', MUST_EXIST);
$moduleinstance  = $DB->get_record('simplelesson', ['id' => $simplelessonid], '*', MUST_EXIST);

$PAGE->set_url('/mod/simplelesson/edit_lesson.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'sesskey' => sesskey()]);

require_login($course, true, $cm);

$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/simplelesson:manage', $modulecontext);

$PAGE->set_context($modulecontext);
$PAGE->activityheader->set_description('');

// Return here after moving pages or adding a new page.
$returnedit = new moodle_url('/mod/simplelesson/edit_lesson.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid]);

$lesson = new lesson($simplelessonid);
$pages = $lesson->get_pages();


/*
 * Check the action:
 * The up and down arrows are only shown for the relevant
 * sequence positions so we don't have to check that
 */
if ( ($sequence != 0) && ($action != 'none') ) {
    /*
     * Given a sequence number
     * Move the page by exchanging sequence numbers
     *
     */
    if ($action == 'moveup') {
        $pageup = $lesson->get_page_record($sequence);
        $pagedown = $lesson->get_page_record($sequence - 1);
        $up = $pageup->sequence - 1;

        echo 'Pages up: ' . $pageup->id . ' seq ' . $pageup->sequence;
        echo 'Pages down: ' . $pagedown->id . ' seq ' . $pagedown->sequence;

        $DB->set_field('simplelesson_pages', 'sequence', $up,
                ['simplelessonid' => $simplelessonid, 'id' => $pageup->id]);
        $down = $pagedown->sequence + 1;
        $DB->set_field('simplelesson_pages', 'sequence', $down,
                ['simplelessonid' => $simplelessonid, 'id' => $pagedown->id]);

                echo 'Pages up: ' . $pageup->id . ' seq ' . $pageup->sequence;
        echo 'Pages down: ' . $pagedown->id . ' seq ' . $pagedown->sequence;

    } else if ($action == 'movedown') {
        $pageup = $lesson->get_page_record($sequence + 1);
        $pagedown = $lesson->get_page_record($sequence);
        $up = $pageup->sequence - 1;
        $DB->set_field('simplelesson_pages', 'sequence', $up,
                ['simplelessonid' => $simplelessonid, 'id' => $pageup->id]);
        $down = $pagedown->sequence + 1;
        $DB->set_field('simplelesson_pages', 'sequence', $down,
                ['simplelessonid' => $simplelessonid, 'id' => $pagedown->id]);
    }
    redirect($returnedit);
}
$allversions = $simplelesson->allversions;
$mform = new edit_questions_form(null, ['id' => $cm->id, 'simplelessonid' => $simplelesson->id,
                                        'courseid' => $courseid, 'allversions' => $allversions]);

if ($data = $mform->get_data()) {
        $simplelesson->categoryid = $data->categoryid;
        $simplelesson->behaviour = $data->behaviour;
        $DB->update_record('simplelesson', $simplelesson);
}

echo $OUTPUT->header();
echo $OUTPUT->render(new lesson_editing($courseid, $simplelessonid, $pages, $cm, $PAGE->url, $mform));
echo $OUTPUT->footer();
