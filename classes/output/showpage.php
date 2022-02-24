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
 * Prepare the data for showing a lesson page.
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
use moodle_url;

class showpage implements renderable, templatable {

    private $simplelesson;
    private $lessonpage;
    private $attemptid;
    private $returnurl;
    private $options;

    public function __construct($simplelesson, $lessonpage, $attemptid, $returnurl, $options) {

        $this->simplelesson = $simplelesson;
        $this->lessonpage = $lessonpage;
        $this->attemptid = $attemptid;
        $this->returnurl = $returnurl;
        $this->options = $options;
    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $baseparams = ['courseid' => $this->simplelesson->course, 'simplelessonid' => $this->simplelesson->id];

        // Page actions.
        if ($this->options->canmanage) {

            $url = new moodle_url('/mod/simplelesson/delete_page.php', $baseparams);
            $this->options->deletepageurl = $url->out(false, ['sequence' => $this->lessonpage->sequence,
                    'title' => $this->lessonpage->pagetitle, 'returnto' => 'view', 'sesskey' => sesskey()]);

            $url = new moodle_url('/mod/simplelesson/edit_page.php', $baseparams);
            $this->options->editpageurl = $url->out(false, ['sequence' => $this->lessonpage->sequence,
                    'sesskey' => sesskey()]);
        }

        // Question actions.
        if ($this->options->canaddquestion) {
            $url = new moodle_url('/mod/simplelesson/add_question.php', $baseparams);
            $this->options->addquestionurl = $url->out(false, ['sequence' => $this->lessonpage->sequence,
                    'returnto' => 'show', 'sesskey' => sesskey()]);
        } else {
            // Question could be deleted or previewed.
            $url = new moodle_url('/mod/simplelesson/delete_question.php', $baseparams);
            $this->options->deletequestionurl = $url->out(false, ['sequence' => $this->lessonpage->sequence,
                    'returnto' => 'show', 'sesskey' => sesskey()]);
            $this->options->deletequestion = true;
            $this->options->previewquestion = true;
            $this->options->questionpreviewurl = new moodle_url('/question/bank/previewquestion/preview.php',
                    ['id' => $this->options->qid, 'returnurl' => $this->returnurl]);

        }

        // Lesson actions.
        if ($this->options->canmanage) {
            $url = new moodle_url('/mod/simplelesson/edit_lesson.php', $baseparams);
            $this->options->editlessonurl = $url->out(false);
        }

        // Last page attempt summary button.
        if ($this->options->summary) {
            $url = new moodle_url('/mod/simplelesson/summary.php', $baseparams);
            $this->options->summaryurl = $url->out(false, ['mode' => 'attempt', 'sequence' => $this->lessonpage->sequence,
                   'attemptid' => $this->attemptid]);
        }

        $this->options->title = $this->lessonpage->pagetitle;
        $this->options->content = $this->lessonpage->pagecontents;

        return $this->options;
    }
}
