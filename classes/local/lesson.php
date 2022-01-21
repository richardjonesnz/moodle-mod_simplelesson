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
 * Class show: The lesson object.
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simplelesson\local;
use mod_simplelesson\utility\utility;

defined('MOODLE_INTERNAL') || die();
/**
 * This class describes a simplelesson object.
 *
 * @package    mod_simplelesson
 * @copyright  2021 Richard Jones richardnz@outlook.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class lesson {

    protected $id;      // The simple lesson id.
    protected $pages;   // An array of page objects in the lesson.

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
     * @param int $sid the sequence number of the wanted page.
     * @return object representing a page in the simplelesson or null if not found.
     */
    public function get_page_record($sid) {

        foreach ($this->pages as $page) {
            if ($page->sequence == $sid) {
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
            $pagetitles[] = $page->pagetitle;
        }
        return $pagetitles;
    }
}
