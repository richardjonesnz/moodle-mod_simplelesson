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
namespace mod_simplelesson\forms;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Manual grading for essay questions
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define a form for grading essay questions
 */
class essay_grading_form extends \moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;

        // Marks available.
        $marks = array();
        for ($m = 0; $m <= $this->_customdata['maxmark']; $m++) {
            $marks[$m] = '' . $m;
        }
        $mform->addElement('select', 'mark',
                get_string('allocate_mark', 'mod_simplelesson'),
                $marks);

        $mform->addElement('hidden', 'courseid',
                $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid',
                $this->_customdata['simplelessonid']);
        $mform->addElement('hidden', 'answerid',
                $this->_customdata['answerid']);

        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);
        $mform->setType('answerid', PARAM_INT);

        $this->add_action_buttons();
    }
}
