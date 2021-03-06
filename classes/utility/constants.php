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
 * Constants.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones http://richardnz/net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\utility;

/**
 * Control question display options
 */
class constants {
    // Attempt status constants.
    const MOD_SIMPLELESSON_ATTEMPT_STARTED = 1;
    const MOD_SIMPLELESSON_ATTEMPT_COMPLETE = 2;
    // Grading methods.
    const MOD_SIMPLELESSON_GRADE_HIGHEST = 1;
    const MOD_SIMPLELESSON_GRADE_AVERAGE = 2;
    const MOD_SIMPLELESSON_GRADE_LAST = 3;
}
