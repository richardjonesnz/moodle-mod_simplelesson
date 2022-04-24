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
 * Prepare the data for the select question mustache template.
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\forms;

use renderable;
use renderer_base;
use templatable;
use stdClass;
use moodle_url;

/**
 * Prepare data for add_question form.
 */
class select_question_form implements renderable, templatable {

    private $simplelesson;
    private $sequence;
    private $questions;
    private $actionurl;

    /**
     * Data required to construct the template.
     */
    public function __construct($simplelesson, $sequence, $questions, $actionurl) {

        $this->simplelesson = $simplelesson;
        $this->sequence = $sequence;
        $this->questions = $questions;
        $this->actionurl = $actionurl;

    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = new stdClass();

        // Table headers.
        $headers = [];
        $headers[] = get_string('select_question', 'mod_simplelesson');
        $headers[] = get_string('id', 'mod_simplelesson');
        $headers[] = get_string('question_name', 'mod_simplelesson');
        $headers[] = get_string('question_version', 'mod_simplelesson');
        $headers[] = get_string('preview_question', 'mod_simplelesson');
        $data->headers = $headers;

        // Hidden fields.
        $data->courseid = $this->simplelesson->course;
        $data->simplelessonid = $this->simplelesson->id;
        $data->sequence = $this->sequence;
        $data->actionurl = $this->actionurl;
        $data->sesskey = sesskey();

        foreach ($this->questions as $question) {
            $row = [];
            $row['id'] = $question->questionid;
            $row['name'] = $question->name;
            $row['version'] = $question->version;
            $row['qtype'] = $question->qtype;
            $row['disabled'] = $question->disabled;
            $row['previewurl'] = new moodle_url('/question/bank/previewquestion/preview.php',
                    ['id' => $question->questionid, 'returnurl' => $this->actionurl]);
            $data->tabledata[] = $row;
        }

        return $data;
    }
}
