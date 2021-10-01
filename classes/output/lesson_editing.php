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

class lesson_editing implements renderable, templatable {

    private $pages;
    private $cm;

    public function __construct($pages, $cm) {

        $this->pages = $pages;
        $this->cm = $cm;
    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $lastpage = count($this->pages);

        $table = new \stdClass();
        $table->caption = get_string('page_editing', 'mod_simplelesson');

        // Set up table headers.
        $headerdata = array();
        $headerdata[] = get_string('sequence', 'mod_simplelesson');
        $headerdata[] = get_string('pagetitle', 'mod_simplelesson');
        $headerdata[] = get_string('prevpage', 'mod_simplelesson');
        $headerdata[] = get_string('nextpage', 'mod_simplelesson');
        $headerdata[] = get_string('hasquestion', 'mod_simplelesson');
        $headerdata[] = get_string('actions', 'mod_simplelesson');

        $table->tableheaders = $headerdata;

        // Set up table rows.
        $baseparams = ['courseid' => $this->cm->course,
                       'simplelessonid' => $this->cm->instance];

        foreach($this->pages as $page) {
            $data = array();
            $data['sequence'] = $page->sequence;
            $data['pagetitle'] = $page->pagetitle;
            $data['previous'] = $page->prevpageid;
            $data['next'] = $page->nextpageid;
            $data['question'] = ['icon' => 'i/invalid', 'component' => 'core', 'alt'=> 'x'];

            $actions = array();

            // Add edit and delete links.
            $link = new \moodle_url('edit_page.php', $baseparams);
            $icon = ['icon' => 't/edit', 'component' => 'core', 'alt'=>
                    get_string('gotoeditpage', 'mod_simplelesson')];
            $actions['edit'] = ['link' => $link->out(false,
                               ['sequence' => $page->sequence,
                                'sesskey' => sesskey()]),
                                'icon' => $icon];

            // Preview = showpage.
            $link = new \moodle_url('showpage.php', $baseparams);
            $icon = ['icon' => 't/preview', 'component' => 'core', 'alt'=>
                get_string('showpage', 'mod_simplelesson')];
            $actions['preview'] = ['link' => $link->out(false,
                                  ['pageid' => $page->id]),
                                   'icon' => $icon];

            // Delete page.
            $link = new \moodle_url('delete_page.php', $baseparams);
            $icon = ['icon' => 't/delete', 'component' => 'core', 'alt' =>
                    get_string('gotodeletepage', 'mod_simplelesson')];
            $actions['delete'] = ['link' => $link->out(false,
                                 ['sequence' => $page->sequence,
                                  'returnto' => 'edit',
                                  'sesskey' => sesskey()]),
                                  'icon' => $icon];

            // Move page up.
            if ($page->sequence != 1) {
                $link = new \moodle_url('edit_lesson.php', $baseparams);
                $icon = ['icon' => 't/up', 'component' => 'core',
                        'alt' => get_string('move_up', 'mod_simplelesson')];
                $actions['moveup'] = ['link' => $link->out(false,
                                     ['sequence' => $page->sequence,
                                      'action' => 'move_up',
                                      'sesskey' => sesskey()]),
                                      'icon' => $icon];
                }

            // Move down.
            if (($page->sequence != $lastpage)) {
                $link = new \moodle_url('edit_lesson.php', $baseparams);
                $icon = ['icon' => 't/down', 'component' => 'core',
                        'alt' => get_string('move_down', 'mod_simplelesson')];
                $actions['movedown'] = ['link' => $link->out(false,
                                       ['sequence' => $page->sequence,
                                        'action' => 'move_down',
                                        'sesskey' => sesskey()]),
                                        'icon' => $icon];
            }

            $data['actions'] = $actions;
            $table->tabledata[] = $data;
        }
        return $table;
    }
}