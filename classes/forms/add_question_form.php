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
 * Form for adding a question to a page
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
class add_question_form extends \moodleform {
    /**
     * Defines a from for selecting questions
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Get questions.
        $questions = $this->_customdata['questions'];

        // Where to come back to from question preview page.
        $returnpageurl = $this->_customdata['returnpageurl'];

        $radios = array();
        foreach ($questions as $question) {
            $previewurl = new \moodle_url('/question/bank/previewquestion/preview.php',
            ['id' => $question->id,
             'returnurl' => $returnpageurl]);

             // Messy but it does work.
            $checkname = $question->id . ':  ' . $question->name . '&nbsp;' .
                    '('. $question->qtype . ')&nbsp;' .
                    '<a href="' . $previewurl . '">' . ' [' .
                    get_string('preview_question', 'mod_simplelesson') . ']</a>';

            // Questions already used in the lesson were marked.
            $attribute = ($question->marked) ? 'disabled' : '';                   ;
            $radios[] = $mform->createElement('radio', 'optradio', '', $checkname, $question->id, $attribute);
        }

        // Set up the radio group, the br is needed to prevent wrapping.
        $mform->addGroup($radios, 'options', '' , '<br />', false);

        $mform->addElement('text', 'score', get_string('questionscore', 'mod_simplelesson'));
        $mform->setDefault('score', 1);
        $mform->setType('score', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid', $this->_customdata['simplelessonid']);
        $mform->addElement('hidden', 'sequence', $this->_customdata['sequence']);

        // Returnto is the Simplelesson page the user came here from.
        $mform->addElement('hidden', 'returnto', $this->_customdata['returnto']);
        $mform->addElement('hidden', 'returnpageurl', $this->_customdata['returnpageurl']);
        $mform->addElement('hidden', 'sesskey', $this->_customdata['sesskey']);

        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);
        $mform->setType('sequence', PARAM_INT);
        $mform->setType('returnto', PARAM_ALPHA);
        $mform->setType('returnpageurl', PARAM_LOCALURL);
        $this->add_action_buttons($cancel = true);
    }
}
