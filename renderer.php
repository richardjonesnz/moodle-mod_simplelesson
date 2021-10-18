<?php
class mod_simplelesson_renderer extends plugin_renderer_base {
/**
     *
     * Render the question form on a page
     *
     * @param moodle_url $actionurl - form action url
     * @param array mixed $options - question display options
     * @param int $slot - slot number for question usage
     * @param object $quba - question usage object
     * @param int $starttime, time question was first presented to user
     * @param string $qtype, the question type - to identify an essay
     * @return string, html representation of the question
     */
    public function render_question_form(
            $actionurl, $options, $slot, $quba,
            $starttime, $qtype) {

        $html = html_writer::start_div('mod_simplelesson_question');
        $headtags = '';
        $headtags .= $quba->render_question_head_html($slot);

        // Start the question form.
        $html .= html_writer::start_tag('form',
                array('method' => 'post', 'action' => $actionurl,
                'enctype' => 'multipart/form-data',
                'accept-charset' => 'utf-8',
                'id' => 'responseform'));
        $html .= html_writer::start_tag('div');
        $html .= html_writer::empty_tag('input',
                array('type' => 'hidden',
                'name' => 'sesskey', 'value' => sesskey()));
        $html .= html_writer::empty_tag('input',
                array('type' => 'hidden',
                'name' => 'slots', 'value' => $slot));
        $html .= html_writer::empty_tag('input',
                array('type' => 'hidden',
                'name' => 'starttime', 'value' => $starttime));
        $html .= html_writer::end_tag('div');

        // Output the question. slot = display number.
        $html .= $quba->render_question($slot, $options);

        // If it's an essay question, output a save button.
        // If it's deferred feedback add a save button.

        if ( ($qtype == 'essay') || ($quba->get_preferred_behaviour()
                == 'deferredfeedback') ){
            $html .= html_writer::start_div(
                    'mod_simplelesson_save_button');
            $label = ($qtype == 'essay') ?
                    get_string('saveanswer', 'mod_simplelesson') :
                    get_string('save', 'mod_simplelesson');
            $html .= $this->output->single_button($actionurl,
                    $label);
            $html .= html_writer::end_div();
        }

        // Finish the question form.
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_div('div');
        return $html;
    }
}