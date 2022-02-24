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
 * The renderer for the question form.
 *
 * @package    mod_simplelesson
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_simplelesson_renderer extends plugin_renderer_base {
    /**
     *
     * Render the question form on a page
     *
     * @param moodle_url $actionurl - form action url
     * @param array mixed $options - question display options
     * @param int $slot - slot number for question usage
     * @param object $quba - question usage object
     * @param int $starttime, time question was first presented to user
     * @param string $qtype, the question type - to identify an essay
     * @return string, html representation of the question
     */
    public function render_question_form( $actionurl, $options, $slot, $quba, $starttime, $qtype) {

        $data = new \stdClass();

        $data->headtags = $quba->render_question_head_html($slot);
        $data->actionurl = $actionurl;
        $data->slot = $slot;
        $data->sesskey = sesskey();
        $data->starttime = $starttime;
        $data->question = $quba->render_question($slot, $options);
        $data->hasbutton = false;

        if ( ($qtype == 'essay') || ($quba->get_preferred_behaviour()
                == 'deferredfeedback') || ($quba->get_preferred_behaviour()
                == 'deferredcbm') ) {
            $data->hasbutton = true;
            $data->label = ($qtype == 'essay') ? get_string('saveessay', 'mod_simplelesson') :
                    get_string('saveanswer', 'mod_simplelesson');
            $data->button = $this->output->single_button($actionurl, $data->label);
            $data->save_message = get_string('save_message', 'simplelesson');
        }

        return $this->output->render_from_template('mod_simplelesson/show_question', $data);

    }
}
