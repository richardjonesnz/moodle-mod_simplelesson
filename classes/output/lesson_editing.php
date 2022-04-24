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
 * Sets up the table to edit the lesson.
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
use context_module;

class lesson_editing implements renderable, templatable {

    private $courseid;
    private $simplelessonid;
    private $pages;
    private $cm;
    private $pageurl;
    private $mform;

    /**
     * Data required to construct the lesson editing table.
     *
     * @param int courseid the course id
     * @param int simplelessonid the Simple lesson id
     * @param array pages objects representing pages in the Simplelesson
     * @param object cm the course module object
     * @param string pageurl the url of the current page
     * @param string mform string of html representing a Moodle form
     */
    public function __construct($courseid, $simplelessonid, $pages, $cm, $pageurl, $mform) {

        $this->courseid = $courseid;
        $this->simplelessonid = $simplelessonid;
        $this->pages = $pages;
        $this->cm = $cm;
        $this->pageurl = $pageurl;
        $this->mform = $mform;
    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;

        $lastpage = count($this->pages);

        $table = new stdClass();
        $table->caption = get_string('page_editing', 'mod_simplelesson');
        $table->home = true; // Show the home button.
        $table->homeurl = new moodle_url('/mod/simplelesson/view.php',
                ['simplelessonid' => $this->simplelessonid]);
        $table->auto = ($lastpage >= 2); // Show the auto-sequence button (if enough pages).
        $table->autourl = new moodle_url('/mod/simplelesson/autosequence.php',
                ['courseid' => $this->courseid,
                 'simplelessonid' => $this->simplelessonid]);
        $table->addpagelesson = true; // Show the addpage button.
        $table->addurl = new moodle_url('/mod/simplelesson/add_page.php',
                ['courseid' => $this->courseid,
                 'simplelessonid' => $this->simplelessonid,
                 'returnto' => 'manage']);

        // Show the Manage questions button if permitted.
        $modulecontext = context_module::instance($this->cm->id);
        $table->managequestions = has_capability('mod/simplelesson:managequestions', $modulecontext);
        $table->questionurl = new moodle_url('/mod/simplelesson/edit_questions.php',
                ['courseid' => $this->courseid,
                 'simplelessonid' => $this->simplelessonid]);

        // This form shows in a Bootstrap modal.
        $table->mform = $this->mform->render();
        $table->qlinkurl = new moodle_url('/question/edit.php', ['courseid' => $this->courseid]);

        // Set up table headers.
        $headerdata = array();
        $headerdata[] = get_string('id', 'mod_simplelesson');
        $headerdata[] = get_string('pagetitle', 'mod_simplelesson');
        $headerdata[] = get_string('previd', 'mod_simplelesson');
        $headerdata[] = get_string('nextid', 'mod_simplelesson');
        $headerdata[] = get_string('hasquestion', 'mod_simplelesson');
        $headerdata[] = get_string('remove', 'mod_simplelesson');
        $headerdata[] = get_string('actions', 'mod_simplelesson');

        $table->tableheaders = $headerdata;

        // Set up table rows.
        $baseparams = ['courseid' => $this->cm->course,
                       'simplelessonid' => $this->cm->instance];

        foreach ($this->pages as $page) {
            $data = array();
            $data['id'] = $page->id;
            $data['pagetitle'] = $page->pagetitle;
            $data['previous'] = $page->prevpageid;
            $data['next'] = $page->nextpageid;
            $data['question'] = false;

            // Is there a question on the page?
            $result = $DB->get_record('simplelesson_questions',
                    ['simplelessonid' => $this->cm->instance,
                    'pageid' => $page->id],
                    'qid',
                    IGNORE_MISSING);

            // Add a link to the preview question page.
            if ($result) {
                $data['questionurl'] = new \moodle_url('/question/bank/previewquestion/preview.php',
                        ['id' => $result->qid,
                         'returnurl' => $this->pageurl]);
                $data['questionlink'] = $result->qid;
                $data['question'] = true;
            } else {
                $link = new \moodle_url('/mod/simplelesson/add_question.php',
                        ['courseid' => $this->courseid,
                         'simplelessonid' => $this->simplelessonid,
                         'sequence' => $page->sequence,
                         'returnto' => 'manage',
                         'sesskey' => sesskey()]);
                $data['questionlink'] = get_string('add', 'mod_simplelesson');
                $data['questionurl'] = $link->out(false);
            }

            $actions = array();

            // Add edit and delete links.
            $link = new \moodle_url('delete_question.php', $baseparams);
            $icon = ['icon' => 't/block', 'component' => 'core', 'alt' =>
                    get_string('delete_question', 'mod_simplelesson')];
            $actions['deleteq'] = ['link' => $link->out(false,
                               ['sequence' => $page->sequence,
                                'returnto' => 'manage',
                                'sesskey' => sesskey()]),
                                'icon' => $icon];

            // Add edit and delete links.
            $link = new \moodle_url('edit_page.php', $baseparams);
            $icon = ['icon' => 't/edit', 'component' => 'core', 'alt' =>
                    get_string('gotoeditpage', 'mod_simplelesson')];
            $actions['edit'] = ['link' => $link->out(false,
                               ['sequence' => $page->sequence,
                                'returnto' => 'manage',
                                'sesskey' => sesskey()]),
                                'icon' => $icon];

            // Preview = showpage.
            $link = new \moodle_url('showpage.php', $baseparams);
            $icon = ['icon' => 't/preview', 'component' => 'core', 'alt' =>
                get_string('showpage', 'mod_simplelesson')];
            $actions['preview'] = ['link' => $link->out(false,
                                  ['sequence' => $page->sequence]),
                                   'icon' => $icon];

            // Delete page.
            $link = new \moodle_url('delete_page.php', $baseparams);
            $icon = ['icon' => 't/delete', 'component' => 'core', 'alt' =>
                    get_string('gotodeletepage', 'mod_simplelesson')];
            $actions['delete'] = ['link' => $link->out(false,
                                 ['sequence' => $page->sequence,
                                  'title' => $page->pagetitle,
                                  'returnto' => 'manage',
                                  'sesskey' => sesskey()]),
                                  'icon' => $icon];

            // Move page up.
            if ($page->sequence != 1) {
                $link = new \moodle_url('edit_lesson.php', $baseparams);
                $icon = ['icon' => 't/up', 'component' => 'core',
                        'alt' => get_string('move_up', 'mod_simplelesson')];
                $actions['moveup'] = ['link' => $link->out(false,
                                     ['sequence' => $page->sequence,
                                      'action' => 'moveup',
                                      'sesskey' => sesskey()]),
                                      'icon' => $icon];
            }

            // Move down.
            if (($page->sequence != $lastpage)) {
                $link = new \moodle_url('edit_lesson.php', $baseparams);;
                $icon = ['icon' => 't/down', 'component' => 'core',
                        'alt' => get_string('move_down', 'mod_simplelesson')];
                $actions['movedown'] = ['link' => $link->out(false,
                                       ['sequence' => $page->sequence,
                                        'action' => 'movedown',
                                        'sesskey' => sesskey()]),
                                        'icon' => $icon];
            }

            $data['actions'] = $actions;
            $table->tabledata[] = $data;
        }

        return $table;
    }
}
