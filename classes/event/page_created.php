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
 * Defines the page created event.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\event;

/**
 * A page created event for Simple lesson.
 *
 * @package    mod_simplelesson
 * @since      Moodle 3.4
 * @copyright  2018 Richard Jones
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class page_created extends \core\event\base {

    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'simplelesson_pages';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns a localised string
     *
     * @return string
     */
    public static function get_name() {
        return get_string('page_created', 'mod_simplelesson');
    }
    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid'"
                . " has created a page with the id '$this->objectid'"
                . " in the simplelesson activity with course module id
                '$this->contextinstanceid'.";
    }
}
