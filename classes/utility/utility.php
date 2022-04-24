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

namespace mod_simplelesson\utility;

/**
 * This class provides methods relating to Moodle editors and formatting.
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utility {
    /**
     * Returns the editor options for html editor areas.
     *
     * @param object $context object the module context.
     * @return array
     */
    public static function get_editor_options($context) {
        global $CFG;
        return ['subdirs' => true,
                'maxbytes' => $CFG->maxbytes,
                'maxfiles' => -1,
                'changeformat' => 1,
                'context' => $context,
                'noclean' => true,
                'trusttext' => false];
    }
    /**
     * Returns the formatting options for html editor area text.
     *
     * @param object $context object the module context.
     * @return object
     */
    public static function get_formatting_options($context) {
        $formatoptions = new \stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        return $formatoptions;
    }
}
