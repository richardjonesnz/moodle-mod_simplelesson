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
 * Prepares the data for view page of the mod instance.
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simplelesson\output;

use renderable;
use renderer_base;
use templatable;
use stdClass;
use moodle_url;

/**
 * Create a new view page renderable object
 *
 * @param object simplesson - current instance.
 * @param int cmid - course module id.
 * @param object options - options relating the the template display.
 * @param object mform - the question category selection form.
 * @copyright  2021 Richard Jones <richardnz@outlook.com>
 */

class view implements renderable, templatable {

    private $simplelesson;
    private $cmid;
    private $options;
    private $mform;

    public function __construct($simplelesson, $cmid, $options, $mform) {

        $this->simplelesson = $simplelesson;
        $this->cmid = $cmid;
        $this->options = $options;
        $this->mform = $mform;
    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = new stdClass();
        $data = $this->options;

        $data->title = $this->simplelesson->title;

        // Moodle handles processing of std intro field.
        // $data->body = format_module_intro('simplelesson', $this->simplelesson, $this->cmid);
        // $data->message = get_string('welcome', 'mod_simplelesson');

        // This form shows in a Bootstrap modal.
        $data->mform = $this->mform->render();

        $data->qlinkurl = new moodle_url('/question/edit.php', ['courseid' => $this->simplelesson->course]);

        return $data;
    }
}
