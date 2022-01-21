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
 * Shows simplelesson reports of various kinds.
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_simplelesson\output\reports;
use mod_simplelesson\local\reporting;

require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$simplelessonid  = required_param('simplelessonid', PARAM_INT);
$report = optional_param('report', 'menu', PARAM_ALPHA);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('simplelesson', $simplelessonid, $courseid, false, MUST_EXIST);
$simplelesson = $DB->get_record('simplelesson', ['id' => $cm->instance], '*', MUST_EXIST);

// Set up the page.
$PAGE->set_url('/mod/simplelesson/reports.php',
        array('courseid' => $courseid, 'simplelessonid' => $simplelessonid));

require_login($course, true, $cm);
$coursecontext = context_course::instance($courseid);
$modulecontext = context_module::instance($cm->id);

require_capability('mod/simplelesson:viewreportstab', $modulecontext);

$PAGE->set_context($modulecontext);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->set_description('');

// Navigation options.
$options = new stdClass();
$options->answersurl = $PAGE->url->out(false, ['report' => 'answers']);
$options->attemptsurl = $PAGE->url->out(false, ['report' => 'attempts']);
$options->gradingurl = $PAGE->url->out(false, ['report' => 'manualgrade']);
$viewlink = new \moodle_url('/mod/simplelesson/view.php',
        ['simplelessonid' => $simplelesson->id]);
$options->viewurl = $viewlink->out(false);
$options->reportsurl = $PAGE->url->out(false, ['report' => 'menu']);
$options->home = true;
$options->menu = ($report == 'menu');
$options->headers = [];

$options->exporturl = new moodle_url('/mod/simplelesson/export.php',
        ['courseid' => $courseid,
         'simplelessonid' => $simplelessonid,
         'type' => $report]);

switch ($report) {

    case 'answers':
        $options->answers = true;
        $options->records = reporting::fetch_answer_data($simplelessonid);
        $options->headers = reporting::fetch_answer_report_headers();
        $options->export = true;
        $options->exportlink = get_string('userreportdownload', 'mod_simplelesson');
        break;

    case 'attempts' :
        $options->attempts = true;
        $options->records = reporting::fetch_attempt_data($simplelessonid);
        $options->headers = reporting::fetch_attempt_report_headers();
        $options->export = true;
        $options->exportlink = get_string('userreportdownload', 'mod_simplelesson');
        break;

    case 'manualgrade':
        // Show records requiring manual grading.
        $data = reporting::fetch_essay_answer_data($simplelessonid);
        $records = reporting::get_essay_report_data($simplelessonid, $data);
        if (empty($records)) {
            // Nothing to grade.
            redirect(new moodle_url('/mod/simplelesson/reports.php',
                    array('courseid' => $courseid,
                    'simplelessonid' => $simplelessonid,
                    'report' => 'menu')),
                    get_string('no_manual_grades',
                    'mod_simplelesson'), 2);
        }
        $options->manualgrade = true;
        $options->records = $records;
        $options->headers = reporting::fetch_essay_answer_report_headers();
        break;
    case 'menu':
        break;
    default:
        // Developer debugging called.
        debugging('Internal error: missing or invalid report type',
                DEBUG_DEVELOPER);
}

echo $OUTPUT->header();
echo $OUTPUT->render(new reports($options));
echo $OUTPUT->footer();
