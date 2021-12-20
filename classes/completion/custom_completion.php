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

declare(strict_types=1);
namespace mod_simplelesson\completion;
use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the simplelesson activity.
 *
 * Contains the class for defining mod_simplelesson's custom completion rules
 * and fetching a simplelesson instance's completion statuses for a user.
 *
 * @package mod_simplelesson
 * @copyright 2021 Richard F Jones <richardnz@outlook.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        var_dump($rule);exit;

        switch ($rule) {
            case 'timetaken':
                $timetaken = $DB->get_field_sql(
                    "SELECT SUM(timetaken)
                       FROM {simplelesson_attempts}
                      WHERE simplelessonid = :simplelessonid
                        AND userid = :userid",
                        ['userid' => $this->userid, 
                         'simplelessonid' => $this->cm->instance]);

                $status = ($timetaken && $timetaken >= $this->cm->customdata['customcompletionrules']['timetaken']);
                break;
            case 'attemptcompleted':
                $status = $DB->record_exists('simplelesson_attempts',
                    ['simplelessonid' => $this->cm->instance, 
                     'userid' => $this->userid, 
                     'status' => MOD_SIMPLELESSON_ATTEMPT_COMPLETE]);
                break;
            default:
                $status = false;
                break;
        }

        return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'timetaken',
            'attemptcompleted',
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        $timetaken = format_time($this->cm->customdata['customcompletionrules']['timetaken'] ?? 0);

        return [
            'timetaken' => get_string('completiondetail:timetaken', 'simplelesson', $timetaken),
            'attemptcompleted' => get_string('completiondetail:attemptcompleted', 'simplelesson'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionusegrade',
            'completionpassgrade',
            'timetaken',
            'attemptcompleted',
        ];
    }
}
