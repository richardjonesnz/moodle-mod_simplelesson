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
 * Prints different kinds of Simplelesson report.
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

/**
 * Prepare the data for the reports page.
 *
 * @param object simplesson - current instance.
 * @param int cmid - course module id.
 * @copyright  2021 Richard Jones <richardnz@outlook.com>
 */

class reports implements renderable, templatable {

    private $options;

    public function __construct($options) {

        $this->options = $options;
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
        $data->headers = $this->options->headers;
        return $data;
    }
}
