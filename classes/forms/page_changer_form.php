/**
 * Define a form that acts on the page field
 */
class simplelesson_pagechanger_form extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        $mform = $this->_form;
        $pagetitles = $this->_customdata['page_titles'];

        // Select a page title.
        $mform->addElement('select', 'pagetitle',
                get_string('pagetitle', 'mod_simplelesson'),
                $pagetitles);

        $mform->addElement('text', 'score',
                get_string('questionscore', 'mod_simplelesson'));
        $mform->setDefault('score', 1);
        $mform->setType('score', PARAM_INT);

        $mform->addElement('hidden', 'courseid',
                $this->_customdata['courseid']);
        $mform->addElement('hidden', 'simplelessonid',
                $this->_customdata['simplelessonid']);
        $mform->addElement('hidden', 'actionitem',
                $this->_customdata['actionitem']);

        $mform->setType('courseid', PARAM_INT);
        $mform->setType('simplelessonid', PARAM_INT);
        $mform->setType('actionitem', PARAM_INT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'qid');
        $mform->setType('qid', PARAM_INT);
        $mform->addElement('hidden', 'name');
        $mform->setType('name', PARAM_TEXT);

        $this->add_action_buttons();
    }
}