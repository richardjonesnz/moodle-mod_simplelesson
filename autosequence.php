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
 * Puts page sequence numbers in logical order according to placement on page management screen.
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \mod_simplelesson\local\lesson;
use \core\output\notification;

require_once('../../config.php');
defined('MOODLE_INTERNAL') || die();
global $DB;

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);

$PAGE->set_url('/mod/simplelesson/autosequence.php', ['courseid' => $courseid, 'simplelessonid' => $simplelessonid]);

require_course_login($courseid);

$lesson = new lesson($simplelessonid);
$pagecount = $lesson->count_pages();

if ($pagecount > 0) {

    for ($p = 1; $p <= $pagecount; $p++) {

        $thispage = $lesson->get_page_record($p);
        $prevpageid = $lesson->get_page_id_from_sequence($p - 1);
        $nextpageid = ($p < $pagecount) ? $lesson->get_page_id_from_sequence($p + 1) : 0;
        $DB->set_field('simplelesson_pages', 'prevpageid', $prevpageid, ['id' => $thispage->id]);
        $DB->set_field('simplelesson_pages', 'nextpageid', $nextpageid, ['id' => $thispage->id]);
    }
}

// Go back to page where request came from.
redirect(new moodle_url('/mod/simplelesson/edit_lesson.php', ['courseid' => $courseid, 'simplelessonid' => $simplelessonid]),
        get_string('sequence_updated', 'mod_simplelesson'), 1, notification::NOTIFY_SUCCESS);
