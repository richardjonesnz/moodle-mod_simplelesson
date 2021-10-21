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
 * Exports simplelesson attempt report to csv format
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 *
 */
use \mod_simplelesson\local\reporting;
use core\dataformat;
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/dataformatlib.php');
defined('MOODLE_INTERNAL') || die();
$courseid = required_param('courseid', PARAM_INT);
$simplelessonid = required_param('simplelessonid', PARAM_INT);
$type = required_param('type', PARAM_TEXT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);

$context = context_module::instance($cm->id);

require_login();
require_capability('mod/simplelesson:exportreportpages', $context);

if ($type == 'answers') {
    $records = reporting::fetch_answer_data($simplelessonid);
    $answers = new ArrayObject($records);
    $iterator = $answers->getIterator();
    $fields = reporting::fetch_answer_report_headers($simplelessonid);
    $filename = clean_filename($moduleinstance->name) . '_answers';
} else { // Attempts.
    $records = reporting::fetch_attempt_data($simplelessonid);
    $attempts = new ArrayObject($records);
    $iterator = $attempts->getIterator();
    $fields = reporting::fetch_attempt_report_headers();
    $filename = clean_filename($moduleinstance->name) . '_attempts';
}

// Consider adding a form here to allow choice of filename and download format.
$dataformat = 'csv';
dataformat::download_data($filename, $dataformat, $fields, $iterator);
exit;
