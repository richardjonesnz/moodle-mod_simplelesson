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
 * Form for selecting table sort order.
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\forms;
defined('MOODLE_INTERNAL') || die();
require_once('../../lib/formslib.php');
/**
 * Define the add question form elements
 */
class manage_attempts_select extends \moodleform {
    /**
     * Defines a from for selecting a category
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Select a category for the questions that can be added.
        $options = array();
        $options['id'] = get_string('id', 'mod_simplelesson');
        $options['firstname'] = get_string('firstname', 'mod_simplelesson');
        $options['lastname'] = get_string('lastname', 'mod_simplelesson');
        $options['timecreated'] = get_string('timecreated', 'mod_simplelesson');
        $options['status'] = get_string('status', 'mod_simplelesson');
        $options['timetaken'] = get_string('timetaken', 'mod_simplelesson');

        $mform->addElement('select', 'sortby', get_string('select_sort', 'mod_simplelesson'),
                $options);
        $mform->setType('sortby', PARAM_TEXT);
        $mform->setDefault('sortby', 'id');

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->addElement('hidden', 'action', $this->_customdata['action']);
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('action', PARAM_TEXT);

        $this->add_action_buttons(false, get_string('sort'));
    }
}
