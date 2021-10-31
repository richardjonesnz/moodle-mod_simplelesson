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
 * Set of display options for simple lesson
 * From previewlib.php and re-written for this plugin
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
defined('MOODLE_INTERNAL') || die();

/**
 * Prepare data fro add_question form.
 */
class add_question {
    private $mform;

    public function __construct($mform) {

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
        $data->mform = $this->mform->render();
        return $data;
    }
}
