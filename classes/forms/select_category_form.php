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
class select_category_form extends \moodleform {
    /**
     * Defines a from for selecting a category
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Select a category for the questions that can be added.
        $categories = array();
        $cats = $DB->get_records('question_categories', null, null, 'id, name');
        foreach ($cats as $cat) {
            $questions = $DB->count_records('question', ['category' => $cat->id]);
            if ($questions > 0) {
                $categories[$cat->id] = $cat->name . ' (' . $questions . ')';
            }
            $categories[0] = get_string('nocategory', 'mod_simplelesson');
        }

        $mform->addElement('select', 'categoryid', get_string('category_select', 'mod_simplelesson'),
                $categories);
        $mform->addHelpButton('categoryid', 'categoryid', 'mod_simplelesson');
        $mform->setType('categoryid', PARAM_INT);
        $mform->setDefault('categoryid', 0);

        $this->add_action_buttons(false, get_string('select'));
    }
}