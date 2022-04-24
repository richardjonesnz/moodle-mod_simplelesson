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

namespace mod_simplelesson\local;
use \mod_simplelesson\utility\constants;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/questionlib.php');

/**
 * Utility class for question usage actions
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones https://richardnz.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempts {
    /**
     * Handles data relating to attempts, including question usages
     *
     * @param object $context - module context
     * @param string $behaviour - question behaviour
     * @param object array $entries - questions selected by user (edit.php)
     * @param int $simplelessonid - module instance id
     * @return int the id of the question engine usage.
     */
    public static function create_usage($context, $behaviour, $entries, $simplelessonid) {

        $quba = \question_engine::make_questions_usage_by_activity('mod_simplelesson', $context);
        $quba->set_preferred_behaviour($behaviour);

        // Set the question slot.
        foreach ($entries as $entry) {
            $questiondef = \question_bank::load_question($entry->qid);
            $slot = $quba->add_question($questiondef, $entry->defaultmark);
            self::set_slot($simplelessonid, $entry->pageid, $slot);
        }

        $quba->start_all_questions();
        \question_engine::save_questions_usage_by_activity($quba);
        return $quba->get_id();
    }
    /**
     * Set the slot number in the questions table
     *
     * @param int $simplelessonid - module instance id
     * @param int $pageid - id of page to set slot
     * @param int $slot - question slot number
     */
    public static function set_slot($simplelessonid, $pageid, $slot) {
        global $DB;
        $DB->set_field('simplelesson_questions', 'slot', $slot,
                ['simplelessonid' => $simplelessonid, 'pageid' => $pageid]);
    }
    /**
     * Get the usage id for a simplelesson attempt
     *
     * @param int $attemptid - module instance id
     * @return int $qubaid - the question usage id associated with this lesson
     */
    public static function get_usageid($attemptid) {
        global $DB;
        return $DB->get_field('simplelesson_attempts', 'qubaid', ['id' => $attemptid]);
    }
    /**
     * Remove all usage data for all simplelesson instances
     * This is a scheduled task under admin control.
     */
    public static function remove_all_usage_data() {
        global $DB;
        /*
          Data is removed from Moodle tables, not from
          our plugin's tables.  The data can be left
          when the user aborts an attempt improperly.
        */
        $usages = $DB->get_records('question_usages', ['component' => 'mod_simplelesson']);
        foreach ($usages as $usage) {
            self::remove_usage_data($usage->id);
        }
        return true;
    }
    /**
     * Remove the usage id for a simplelesson instance
     * Also clean up Moodle's attempt data as this doesn't
     * always seem to get done by question engine.
     *
     * Will also make it easier when dealing with GDPR.
     *
     * @param $qubaid - question usage id
     */
    public static function remove_usage_data($qubaid) {
        global $DB;

        // Delete these records explicitly, we have the
        // attempt data we need in our attempts table.
        $ataids = $DB->get_records('question_attempts', ['questionusageid' => $qubaid]);
        foreach ($ataids as $ataid) {
            // Get the attempt step id's.
            $atsteps = $DB->get_records('question_attempt_steps', ['questionattemptid' => $ataid->id]);
            foreach ($atsteps as $atstep) {
                // Get the step data out.
                $DB->delete_records('question_attempt_step_data', ['attemptstepid' => $atstep->id]);
            }
            // Get the attempt steps cleaned out.
            $DB->delete_records('question_attempt_steps', ['questionattemptid' => $ataid->id]);
        }
        // Delete the attempt data.
        $DB->delete_records('question_attempts', ['questionusageid' => $qubaid]);
        $DB->delete_records('question_usages', ['id' => $qubaid]);
    }
    /**
     * Return the wanted row from question attempts
     *
     * @param int $qubaid usage id
     * @param int $slot question attempt slot
     * @return object corresponding row in question attempts
     */
    public static function get_question_attempt_id($qubaid, $slot) {
        global $DB;
        $data = $DB->get_record('question_attempts', ['questionusageid' => $qubaid, 'slot' => $slot],
                'id', MUST_EXIST);
        return $data->id;
    }
    /**
     * Return an array of updated lesson answers and associated data
     *
     * @param int $attemptid int id of simplelesson_attempts
     * @return object array with one or more rows of answer data
     */
    public static function get_lesson_answer_data($attemptid) {
        global $DB;

        $answerdata = $DB->get_records('simplelesson_answers', ['attemptid' => $attemptid]);

        // Add the data for the summary table.
        foreach ($answerdata as $data) {

            // Add the page title.
            $data->pagename = $DB->get_field('simplelesson_pages', 'pagetitle',  ['id' => $data->pageid]);

            // Get the required question data.
            $data->qid = $DB->get_field('simplelesson_questions', 'qid',
                    ['simplelessonid' => $data->simplelessonid, 'pageid' => $data->pageid]);
            $questiondata = $DB->get_record('question', ['id' => $data->qid], 'name, qtype');
            $data->question = $questiondata->name;
            $data->qtype = $questiondata->qtype;

            // Record this data in the table.
            $DB->update_record('simplelesson_answers', $data);
        }
        return $answerdata;
    }

    /**
     * Update answer data - or insert new answer record
     * We need to do this in case an essay question is
     * saved more than once or in case, in the future, other
     * behaviours are implemented.
     *
     * @param object $answerdata data to update with
     * @return int id of inserted or updated record
     */
    public static function update_answer($answerdata) {
        global $DB;
        // Check if answerdata already recorded.
        $answerrecord = $DB->get_record('simplelesson_answers',
                ['attemptid' => $answerdata->attemptid,
                 'simplelessonid' => $answerdata->simplelessonid,
                 'pageid' => $answerdata->pageid], '*', IGNORE_MISSING);
        if ($answerrecord) {
            // Update the record.
            $answerdata->id = $answerrecord->id;
            $DB->update_record('simplelesson_answers', $answerdata);
        } else {
            // Create a new record.
            $answerdata->id = $DB->insert_record('simplelesson_answers', $answerdata);
        }
        return $answerdata->id;
    }

    /**
     * Make an entry in the attempts table
     *
     * @param object $data data to insert (from start_attempt.php)
     * @return int record id
     */
    public static function set_attempt_start($data) {
        global $DB;
        return $DB->insert_record('simplelesson_attempts', $data);
    }
    /**
     * Get the user record for an attempt
     *
     * @param int $attemptid the attempt record id
     * @return object - user data from the users table
     */
    public static function get_attempt_user($attemptid) {
        global $DB;
        $data = $DB->get_record('simplelesson_attempts', ['id' => $attemptid], 'userid', MUST_EXIST);
        return $DB->get_record('user', ['id' => $data->userid], '*', MUST_EXIST);
    }
    /**
     * Set status the attempts table
     *
     * Need some constants here: 0, 1 (started), 2 (complete).
     * @param int $attemptid - record id to update
     * @param object $sessiondata - Score & time for this attempt
     */
    public static function set_attempt_completed($attemptid, $sessiondata) {
        global $DB;
        $DB->set_field('simplelesson_attempts', 'status',
                constants::MOD_SIMPLELESSON_ATTEMPT_COMPLETE,
                ['id' => $attemptid]);
        $DB->set_field('simplelesson_attempts', 'sessionscore', $sessiondata->score,
                ['id' => $attemptid]);
        $DB->set_field('simplelesson_attempts', 'timetaken', $sessiondata->stime,
                ['id' => $attemptid]);
    }
    /**
     * Add up the marks and times in the answer data
     *
     * @param object array $answerdata - array of question answers
     * @return object overall mark and time for the attempt
     */
    public static function get_sessiondata($answerdata) {
        $returndata = new \stdClass();
        $returndata->score = 0.0;
        $returndata->maxscore = 0.0;
        $returndata->stime = 0;
        foreach ($answerdata as $data) {
            // An unmarked essay question has a score of -1, don't count it.
            $data->mark = ($data->mark < 0) ? 0 : $data->mark;
            $returndata->score += $data->mark;
            $returndata->maxscore += $data->maxmark;
            $returndata->stime += $data->timetaken;
        }
        return $returndata;
    }
    /**
     * Get the user attempts at this lesson instance
     *
     * @param int $userid - relevant user
     * @param int $simplelessonid - relevant lesson
     * @return int number of attempts by user
     *         on this lesson and course
     */
    public static function get_number_of_attempts($userid, $simplelessonid) {
        global $DB;
        return $DB->count_records('simplelesson_attempts',
                ['userid' => $userid,
                 'simplelessonid' => $simplelessonid]);
    }
    /**
     * save answer data
     *
     * @param object array $answerdata answers to save
     * @return none
     */
    public static function save_lesson_answerdata($answerdata) {
        global $DB;

        foreach ($answerdata as $answer) {
            $data = new \stdClass();
            $data->id = $answer->id;
            $data->simplelessonid = $answer->simplelessonid;
            $data->qatid = 0; // Data will be removed by cleanup.
            $data->attemptid = $answer->attemptid;
            $data->pageid = $answer->pageid;
            $data->maxmark = $answer->maxmark;
            $data->mark = $answer->mark;
            $data->questionsummary = $answer->questionsummary;
            $data->rightanswer = $answer->rightanswer;
            $data->youranswer = $answer->youranswer;
            $data->timestarted = $answer->timestarted;
            $data->timecompleted = $answer->timecompleted;

            $DB->update_record('simplelesson_answers', $data);
        }
    }
    /**
     * Checks the question usage engine stateclass entry in the answers table.
     *
     * @param  int $simplelessonid the simplelesson id
     * @param  int $attemptid the attempt id
     * @param  int $pageid the page id
     * @return bool true if question has been completed
     */
    public static function is_answered($simplelessonid, $attemptid, $pageid) {
        global $DB;
        $result = false;
        $stateclass = $DB->get_field('simplelesson_answers',
                'stateclass', array('simplelessonid' => $simplelessonid,
                'attemptid' => $attemptid, 'pageid' => $pageid));
        if ( ($stateclass == '') || ($stateclass == 'notanswered') ||
             ($stateclass == 'invalidanswer') || ($stateclass == 'notyetanswered')) {
            $result = false;
        } else {
            $result = true;
        }

        return $result;
    }
    /**
     * Delete the record for an attempt and the associated answers
     *
     * @param int $attemptid the attempt record id
     * @return bool true if succeeds.
     */
    public static function delete_attempt($attemptid) {
        global $DB;

        $DB->delete_records('simplelesson_answers', ['attemptid' => $attemptid]);

        return $DB->delete_records('simplelesson_attempts', ['id' => $attemptid]);
    }
    /**
     * Upate attempt session score and the associated answer after grading an essay attempt.
     *
     * @param int $answerid the answer record id
     * @param int $mark the mark awarded in manual grading
     */
    public static function update_attempt_score($answerid, $mark) {
        global $DB;

        // Get the relevant answer record and current session score.
        $answer = $DB->get_record('simplelesson_answers', ['id' => $answerid], '*', MUST_EXIST);
        $sessionscore = $DB->get_field('simplelesson_attempts', 'sessionscore',
                ['id' => $answer->attemptid]);

        // Update with mark for essay question. Might be a re-grade.
        if ($answer->mark == -1) {
            // Not graded yet.
            $update = $sessionscore + $mark;
        } else {
            // Adjust the session score.
            $update = $sessionscore - $answer->mark + $mark;
        }
        // Update the attempt, answer tables for this question.
        $DB->set_field('simplelesson_attempts', 'sessionscore', ($update), ['id' => $answer->attemptid]);
        $DB->set_field('simplelesson_answers', 'mark', $mark, ['id' => $answerid]);
    }
    /**
     * Given a simplelessonid, find all its questions that are on a page.
     *
     * @param object $simplelesonid
     * @return array question display data
     */
    public static function fetch_attempt_questions($simplelessonid) {
        global $DB;
        $sql = "SELECT s.id, s.qid, s.pageid, q.name, q.questiontext, q.defaultmark
                  FROM {simplelesson_questions} s
                  JOIN {question} q ON s.qid = q.id
                 WHERE s.simplelessonid = :slid
                   AND s.pageid <> 0";
        $entries = $DB->get_records_sql($sql, ['slid' => $simplelessonid]);
        return $entries;
    }
    /**
     * Add up the questions scores for the lesson
     *
     * @param int $simplelessonid - id of the lesson
     * @return int the maximum possible score for questions in this lesson
     */
    public static function get_maxscore($simplelessonid) {
        global $DB;

        $sql = "SELECT s.id, s.simplelessonid, s.score, s.slot
                  FROM {simplelesson_questions} s
                 WHERE s.simplelessonid = :slid
                   AND s.slot <> 0";
        $entries = $DB->get_records_sql($sql, ['slid' => $simplelessonid]);

        $maxscore = 0;
        foreach ($entries as $entry) {
            $maxscore += $entry->score;
        }
        return $maxscore;
    }
    /**
     * Given a question id find the score assigned
     *
     * @param int $qid - the question id
     * @return int $score the score allocated by the teacher
     */
    public static function fetch_question_score($simplelessonid, $pageid) {
        global $DB;
        $data = $DB->get_record('simplelesson_questions',
                ['simplelessonid' => $simplelessonid,
                 'pageid' => $pageid],
                 'score', MUST_EXIST);
        return $data->score;
    }
    /**
     * Given a simplelessonid and pageid
     * return the slot number
     *
     * @param int $simplelesson the module instance
     * @param int $pageid the page
     * @return int a slot number from the table
     */
    public static function get_slot($simplelessonid, $pageid) {
        global $DB;
        return $DB->get_field('simplelesson_questions', 'slot',
                ['simplelessonid' => $simplelessonid,
                 'pageid' => $pageid]);
    }
}
