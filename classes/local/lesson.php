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

/**
 * This class describes a simplelesson object.
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lesson {

    /**
     * id int of this simplelesson
     */
    protected $id;
    /**
     * pages array of objects representing content pages
     */
    protected $pages;   // An array of page objects in the lesson.

    /**
     * Construct a Simple lesson object
     * @param int the simplelesson id.
     */
    public function __construct($id) {

        $this->id = $id;
        $this->pages = self::get_pages();
    }
    /**
     * Retrieve all the pages in a given simplelesson sorted by sequence number.
     *
     * @return array of page objects in the simplelesson.
     */
    public function get_pages() {
        global $DB;
        $result = $DB->get_records('simplelesson_pages', ['simplelessonid' => $this->id], 'sequence', '*');
        return $result;
    }
    /**
     * Count the number of pages in this lesson..
     * @return int Page count.
     */
    public function count_pages() {
        return count($this->pages);
    }
    /**
     * Retrieve page record given it's sequence number.
     * @param int $sequence the sequence number of the wanted page.
     * @return object representing a page in the simplelesson or null if not found.
     */
    public function get_page_record($sequence) {

        foreach ($this->pages as $page) {
            if ($page->sequence == $sequence) {
                return $page;
            }
        }
        return null;
    }
    /**
     * Retrieve page titles.
     * @return array of page titles.
     */
    public function get_page_titles() {
        $pagetitles = array();
        $pagetitles[0] = get_string('none', 'mod_simplelesson');
        foreach ($this->pages as $page) {
            $pagetitles[$page->id] = $page->pagetitle;
        }
        return $pagetitles;
    }
    /**
     * Given a sequence number, find that page record id.
     *
     * @param int $sequence, where the page is in the lesson sequence
     * @return int the id of the page in the pages table
     */
    public function get_page_id_from_sequence($sequence) {
        global $DB;
        $data = $DB->get_record('simplelesson_pages', ['simplelessonid' => $this->id, 'sequence' => $sequence]);
        return ($data) ? $data->id : 0;
    }
    /**
     * Given a category id find the questions in that category.
     *
     * @param int $catid the question category.
     * @param int $allversions whether or not to return all versions of a given question.
     * @return array a hashed array of question objects.
     */
    public static function get_questions($catid, $allversions) {
        global $DB;

        $sql = "SELECT q.id AS questionid, q.name, q.qtype,
                       c.id AS categoryid,
                       v.id AS versionid,
                       v.version,
                       v.status,
                       v.questionbankentryid AS entryid
                  FROM {question} q
                  JOIN {question_versions} v ON q.id = v.questionid
                  JOIN {question_bank_entries} e on e.id = v.questionbankentryid
                  JOIN {question_categories} c ON c.id = e.questioncategoryid
                 WHERE c.id = :catid
                   AND v.status = :vstatus
                 order by entryid, v.version desc";

        $records = $DB->get_records_sql($sql, ['catid' => $catid,
                'vstatus' => \core_question\local\bank\question_version_status::QUESTION_STATUS_READY]);

        // Return one or more versions (checkbox option in settings).
        if ($allversions) {
            return $records;
        } else {
            // This depends on the above SQL returning appropriately ordered data.
            $questions = [];
            $entryid = 0;
            foreach ($records as $record) {
                // Check if this entry id has been added already.
                if ($record->entryid != $entryid) {
                    $questions[] = $record;
                    $entryid = $record->entryid;
                }
            }
            return $questions;
        }
    }
}
