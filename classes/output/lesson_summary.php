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
 * Sets up the table to display attempt data.
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simplelesson\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 *
 * Output the details of the attempt
 */

class lesson_summary implements renderable, templatable {

    private $options;
    private $user;
    private $answerdata;
    private $markdp;
    private $sessiondata;

    /**
     * The data required to build the template
     *
     * @param object data detailing the options for the summary display
     * @param string user the user name
     * @param object array $answerdata an array of data relating to user responses to questions.
     * @param int $markdp - numer of decimal places in mark
     * @param object $sessiondata - score, maxscore and time
     */

    public function __construct($options, $user, $answerdata, $markdp, $sessiondata) {

        $this->options = $options;
        $this->user = $user;
        $this->answerdata = $answerdata;
        $this->markdp = $markdp;
        $this->sessiondata = $sessiondata;

    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;

        // Generate the data for the attempt.
        $table = $this->options;

        $table->head = [get_string('question', 'mod_simplelesson'),
                        get_string('pagetitle', 'mod_simplelesson'),
                        get_string('rightanswer', 'mod_simplelesson'),
                        get_string('youranswer', 'mod_simplelesson'),
                        get_string('mark', 'mod_simplelesson'),
                        get_string('outof', 'mod_simplelesson'),
                        get_string('timetaken', 'mod_simplelesson')];

        $table->data = array();
        foreach ($this->answerdata as $answer) {
            $data = array();
            $data['question'] = $answer->question;
            $data['pagetitle'] = $answer->pagename;
            $mark = round($answer->mark, $this->markdp);
            if ($answer->qtype == 'essay') {
                $data['mark'] = get_string('ungraded', 'mod_simplelesson');
                $data['your_answer'] = format_text($answer->youranswer); // User input if essay question.
                $data['right_answer'] = get_string('essay', 'mod_simplelesson');
            } else {
                $data['mark'] = $mark;
                $data['your_answer'] = $answer->youranswer;
                $data['right_answer'] = $answer->rightanswer;
            }
            $data['maxmark'] = round($answer->maxmark, 2);
            $data['timetaken'] = $answer->timetaken;
            $table->data[] = $data;
        }
        // Session summary data.
        $table->name = $this->user->firstname . ' ' . $this->user->lastname;
        $table->score = $this->sessiondata->score;
        $table->maxscore = $this->sessiondata->maxscore;
        $table->totaltime = $this->sessiondata->stime;

        return $table;
    }
}
