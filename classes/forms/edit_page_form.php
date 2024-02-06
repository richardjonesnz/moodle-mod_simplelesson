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
 * Page editing form.
 *
 * @package   mod_simplelesson
 * @copyright 2018 Richard Jones https://richardnz.net
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_simplelesson\forms;
use mod_simplelesson\utility\utility;
defined('MOODLE_INTERNAL') || die();
require_once('../../lib/formslib.php');
/**
 * Define the edit page form elements.
 */
class edit_page_form extends \moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {

        $mform = $this->_form;

        // The page title.
        $mform->addElement('text', 'pagetitle',
                get_string('pagetitle', 'mod_simplelesson'), array('size' => '64'));
        $mform->addRule('pagetitle', null, 'required', null, 'client');
        $mform->setType('pagetitle', PARAM_TEXT);

        // Page text - editor field.
        $context = $this->_customdata['context'];
        $pagecontentsoptions = utility::get_editor_options($this->_customdata['context']);

        $mform->addElement('editor', 'pagecontents_editor',
                get_string('pagecontents', 'mod_simplelesson'),
                null, $pagecontentsoptions);

        // Remember stick with this naming style.
        $mform->setType('pagecontents_editor', PARAM_RAW);
        $mform->addRule('pagecontents_editor', get_string('required'),
                'required', null, 'client');

        // Drop-down lists for page linking - array has id numbers as the key.
        // We have an array of page objects and we want to display the titles but select the id.
        $mform->addElement('select', 'prevpageid', get_string('prev', 'mod_simplelesson'), $this->_customdata['pagetitles']);
        $mform->addElement('select', 'nextpageid', get_string('next', 'mod_simplelesson'), $this->_customdata['pagetitles']);

        $mform->setType('nextpage', PARAM_INT);
        $mform->setType('prevpage', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid', $this->_customdata['simplelessonid']);
        $mform->addElement('hidden', 'returnto', $this->_customdata['returnto']);
        $mform->addElement('hidden', 'sequence', $this->_customdata['sequence']);
        $mform->addElement('hidden', 'sesskey', sesskey());

        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);
        $mform->setType('returnto', PARAM_ALPHA);
        $mform->setType('sequence', PARAM_INT);

        // Add the action buttons.
        $this->add_action_buttons($cancel = true);
    }
    // Massage the editor data for displaying on the form.
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $context = $this->_customdata['context'];
            $pagecontentsoptions = simplelesson_get_editor_options($context);
            $defaultvalues = (object) $defaultvalues;
            $defaultvalues =
                    file_prepare_standard_editor(
                    $defaultvalues,
                    'pagecontents',
                    $pagecontentsoptions,
                    $context,
                    'mod_simplelesson',
                    'pagecontents',
                    $defaultvalues->id);
            $defaultvalues = (array) $defaultvalues;
        }
    }
}
