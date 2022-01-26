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
 * The main simplelesson configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_simplelesson
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_simplelesson\utility\constants;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_simplelesson
 * @copyright  2019 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_simplelesson_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('simplelessonname', 'simplelesson'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'simplelessonname', 'simplelesson');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Additional settings for the module.
        $mform->addElement('header', 'label', get_string('simplelesson_settings', 'mod_simplelesson'));

        $mform->addElement('text', 'title', get_string('simplelesson_title', 'mod_simplelesson'));
        $mform->setType('title', PARAM_TEXT);

        // Allow the page index.
        $mform->addElement('advcheckbox', 'showindex', get_string('showindex', 'mod_simplelesson'));
        $mform->setDefault('showindex', 1);
        $mform->addHelpButton('showindex', 'showindex', 'simplelesson');

        // Allow student review.
        $mform->addElement('advcheckbox', 'allowreview', get_string('allowreview', 'mod_simplelesson'));
        $mform->setDefault('allowreview', 1);
        $mform->addHelpButton('allowreview', 'allowreview', 'simplelesson');

        // Allow incomplete attempts.
        $mform->addElement('advcheckbox', 'allowincomplete', get_string('allowincomplete', 'mod_simplelesson'));
        $mform->setDefault('allowincomplete', 1);
        $mform->addHelpButton('allowincomplete', 'allowincomplete', 'simplelesson');

        // Attempts.
        $attemptoptions = array(0 => get_string('unlimited', 'mod_simplelesson'),
            1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5');
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', 'mod_simplelesson'), $attemptoptions);
        $mform->setType('maxattempts', PARAM_INT);

        // Grade Method.
        $gradeoptions = [
                constants::MOD_SIMPLELESSON_GRADE_HIGHEST =>
                get_string('gradehighest', 'mod_simplelesson'),
                constants::MOD_SIMPLELESSON_GRADE_AVERAGE =>
                get_string('gradeaverage', 'mod_simplelesson'),
                constants::MOD_SIMPLELESSON_GRADE_LAST =>
                get_string('gradelast', 'mod_simplelesson')];
        $mform->addElement('select', 'grademethod',
                get_string('grademethod', 'mod_simplelesson'),
                $gradeoptions);
        $mform->addHelpButton('grademethod', 'grademethod', 'scorm');
        $mform->setType('grademethod', PARAM_INT);
        $mform->setDefault('grademethod', 'highest');

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
