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
 * Library of interface functions and constants for module simplelesson
 *
 * Core Moodle functions and callbacks.
 *
 * The simplelesson specific functions, needed to implement all the module
 * logic will be found in the classes folder.
 *
 * @package    mod_simplelesson
 * @copyright  2018 Richard Jones <richardnz@outlook.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://github.com/moodlehq/moodle-mod_newmodule
 * @see https://github.com/justinhunt/moodle-mod_pairwork
 */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function simplelesson_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_USES_QUESTIONS:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the simplelesson into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $simplelesson Submitted data from the form in mod_form.php
 * @param mod_simplelesson_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted simplelesson record
 */
function simplelesson_add_instance(stdClass $simplelesson, mod_simplelesson_mod_form $mform = null) {
    global $DB;

    $simplelesson->timecreated = time();
    $simplelesson->id = $DB->insert_record('simplelesson', $simplelesson);

    simplelesson_grade_item_update($simplelesson);

    return $simplelesson->id;
}

/**
 * Updates an instance of the simplelesson in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $simplelesson An object from the form in mod_form.php
 * @param mod_simplelesson_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function simplelesson_update_instance(stdClass $simplelesson, mod_simplelesson_mod_form $mform = null) {
    global $DB;

    $simplelesson->timemodified = time();
    $simplelesson->id = $simplelesson->instance;

    $result = $DB->update_record('simplelesson', $simplelesson);

    simplelesson_grade_item_update($simplelesson);
    simplelesson_update_grades($simplelesson, 0);

    return $result;
}

/**
 * Removes an instance of the simplelesson from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function simplelesson_delete_instance($id) {
    global $DB;

    if (!$simplelesson = $DB->get_record('simplelesson', ['id' => $id])) {
        return false;
    }
    if (!$cm = get_coursemodule_from_instance('simplelesson', $simplelesson->id)) {
        return false;
    }

    // Delete any dependent records.
    $DB->delete_records('simplelesson_questions', ['simplelessonid' => $simplelesson->id]);
    $DB->delete_records('simplelesson_answers', ['simplelessonid' => $simplelesson->id]);
    $DB->delete_records('simplelesson_attempts', ['simplelessonid' => $simplelesson->id]);
    $DB->delete_records('simplelesson_pages', ['simplelessonid' => $simplelesson->id]);

    // Delete the module record.
    $DB->delete_records('simplelesson', ['id' => $simplelesson->id]);

    // Delete files.
    $context = context_module::instance($cm->id);
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'pagecontents');

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $simplelesson The simplelesson instance record
 * @return stdClass|null
 */
function simplelesson_user_outline($course, $user, $mod, $simplelesson) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $simplelesson the module instance record
 */
function simplelesson_user_complete($course, $user, $mod, $simplelesson) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in simplelesson activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function simplelesson_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function simplelesson_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of simplelesson?
 *
 * This function returns if a scale is being used by one simplelesson
 * if it has support for grading and scales.
 *
 * @param int $simplelessonid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given simplelesson instance
 */
function simplelesson_scale_used($simplelessonid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('simplelesson',
            ['id' => $simplelessonid, 'grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of simplelesson.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any simplelesson instance
 */
function simplelesson_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('simplelesson', ['grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Removes all grades from gradebook - implements reset activity.
 *
 * @param int $courseid The ID of the course to reset
 */
function simplelesson_reset_gradebook($courseid) {
    global $CFG, $DB;

    $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
              FROM {simplelesson} a, {course_modules} cm, {modules} m
             WHERE m.name = :moduletype
               AND m.id = cm.module
               AND cm.instance=a.id
               AND a.course=:courseid";

    $params = ['moduletype' => 'mod_simplelesson', 'courseid' => $courseid];

    if ($simplelessons = $DB->get_records_sql($sql, $params)) {
        foreach ($simplelessons as $simplelesson) {
            simplelesson_grade_item_update($simplelesson, 'reset');
        }
    }
}
/**
 * Implementation of the function for printing the form elements
 * that control whether the course reset functionality affects the
 * simplelesson activity.
 * @param moodleform $mform form passed by reference
 */
function simplelesson_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'simplelessonheader', get_string('modulenameplural', 'mod_simplelesson'));
    $name = get_string('deleteallsubmissions', 'mod_simplelesson');
    $mform->addElement('advcheckbox', 'reset_mod_simplelesson_submissions', $name);
}

/**
 * Course reset form defaults.
 * @param  object $course
 * @return array
 */
function simplelesson_reset_course_form_defaults($course) {
    return ['reset_mod_simplelesson_submissions' => 1,
            'reset_mod_simplelesson_group_overrides' => 1,
            'reset_mod_simplelesson_user_overrides' => 1];
}
/**
 * Actual implementation of the reset course functionality,
 * delete all the Simple lesson attempts for course $data->courseid.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function simplelesson_reset_userdata($data) {
    global $CFG, $DB;

    $componentstr = get_string('modulenameplural', 'simplelesson');
    $status = array();

    // Suddenly we're back to mod_simplelesson here, go figure.
    if ($data->reset_mod_simplelesson_submissions) {
        $sql = "SELECT l.id
                  FROM {simplelesson} l
                 WHERE l.course=:course";
        $params = ['course' => $data->courseid];
        $simplelessons = $DB->get_records_sql($sql, $params);

        // Delete all the data for all simple lesson attempts and answers in this course.
        $DB->delete_records_select('simplelesson_attempts', "simplelessonid IN ($sql)", $params);
        $DB->delete_records_select('simplelesson_answers',  "simplelessonid IN ($sql)", $params);

        $status[] = ['component' => $componentstr, 'item' =>
                get_string('deleteallattempts', 'simplelesson'), 'error' => false];

        // Remove all grades from gradebook.
        if (empty($data->reset_gradebook_grades)) {
            simplelesson_reset_gradebook($data->courseid);
        }
    }
    return $status;
}
/**
 * Delete grade item for given simplelesson instance
 *
 * @param stdClass $simplelesson instance
 * @return grade_item
 */
function simplelesson_grade_item_delete($simplelesson) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    return grade_update('mod/simplelesson', $simplelesson->course,
            'mod', 'simplelesson', $simplelesson->id, 0, $grades,
            ['deleted' => 1]);
}

/**
 * Update simplelesson grades in the gradebook
 * Needed by grade_update_mod_grades().
 *
 * @param stdClass $simplelesson instance object
 * @param int $userid update grade of specific user only, 0 means all participants
 * @param bool $nullifnone - not used
 */
function simplelesson_update_grades(stdClass $simplelesson,
        $userid = 0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = simplelesson_get_user_grades($simplelesson, $userid);
    if ($grades) {
        simplelesson_grade_item_update($simplelesson, $grades);
    } else if ($userid) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        simplelesson_grade_item_update($simplelesson, $grade);
    } else {
        simplelesson_grade_item_update($simplelesson);
    }
}

/**
 * Creates or updates grade item for the given simplelesson instance
 * Needed by grade_update_mod_grades().
 *
 * @param stdClass $mod_simplelesson record with extra cmidnumber
 * @param array $grades optional array/object of grade(s): 'reset' means reset grades in gradebook.
 * @return int 0 if ok, error code otherwise
 */
function simplelesson_grade_item_update(stdClass $simplelesson, $grades=null) {
    global $CFG;
    // Workaround for buggy PHP versions.
    if (!function_exists('grade_update')) {
        require_once($CFG->libdir.'/gradelib.php');
    }

    $item = array();
    $item['itemname'] = clean_param($simplelesson->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($simplelesson->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $simplelesson->grade;
        $item['grademin']  = 0;
    } else if ($simplelesson->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$simplelesson->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $item['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/simplelesson', $simplelesson->course,
            'mod', 'simplelesson', $simplelesson->id, 0,
            $grades, $item);
}
/**
 * Return grade for given user or all users.
 *
 * @param stdClass $simplelesson instance
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function simplelesson_get_user_grades($simplelesson, $userid=0) {
    global $DB;

    $grades = array();
    if (empty($userid)) {
        // All user attempts for this simple lesson.
        $sql = "SELECT a.id, a.simplelessonid,
                       a.userid, a.sessionscore,
                       a.timecreated
                  FROM {simplelesson_attempts} a
                 WHERE a.simplelessonid = :slid
              GROUP BY a.userid";

        $slusers = $DB->get_records_sql($sql, ['slid' => $simplelesson->id]);
        if ($slusers) {
            foreach ($slusers as $sluser) {
                $grades[$sluser->userid] = new stdClass();
                $grades[$sluser->userid]->id = $sluser->id;
                $grades[$sluser->userid]->userid = $sluser->userid;

                // Get this users attempts.
                $sql = "SELECT a.id, a.simplelessonid,
                       a.userid, a.sessionscore, a.maxscore,
                       a.timecreated
                  FROM {simplelesson_attempts} a
            INNER JOIN {user} u
                    ON u.id = a.userid
                 WHERE a.simplelessonid = :slid
                   AND u.id = :uid";
                $attempts = $DB->get_records_sql($sql, ['slid' => $simplelesson->id,
                      'uid' => $sluser->userid]);

                // Apply grading method.
                $grades[$sluser->userid]->rawgrade =
                        \mod_simplelesson\local\grading::grade_user($simplelesson, $attempts);
            }
        } else {
            return false;
        }
    } else {
        // User grade for userid.
        $sql = "SELECT a.id, a.simplelessonid,
                       a.userid, a.sessionscore, a.maxscore,
                       a.timecreated
                  FROM {simplelesson_attempts} a
            INNER JOIN {user} u
                    ON u.id = a.userid
                 WHERE a.simplelessonid = :slid
                   AND u.id = :uid";

        $attempts = $DB->get_records_sql($sql, ['slid' => $simplelesson->id, 'uid' => $userid]);
        if (!$attempts) {
            return false; // No attempt yet.
        }
        // Update grades for user.
        $grades[$userid] = new stdClass();
        $grades[$userid]->id = $simplelesson->id;
        $grades[$userid]->userid = $userid;
        // Using selected grading strategy here.
        $grades[$userid]->rawgrade =
                \mod_simplelesson\local\grading::grade_user($simplelesson, $attempts);
    }
    return $grades;
}

/**
 * Rescale all grades for this activity and push the new grades to the gradebook.
 *
 * @param stdClass $course Course db record
 * @param stdClass $cm Course module db record
 * @param float $oldmin
 * @param float $oldmax
 * @param float $newmin
 * @param float $newmax
 */
function simplelesson_rescale_activity_grades($course, $cm, $oldmin, $oldmax, $newmin, $newmax) {
    global $DB;

    if ($oldmax <= $oldmin) {
        // Grades cannot be scaled.
        return false;
    }
    $scale = ($newmax - $newmin) / ($oldmax - $oldmin);
    if (($newmax - $newmin) <= 1) {
        // We would lose too much precision, lets bail.
        return false;
    }

    $params = array(
        'p1' => $oldmin,
        'p2' => $scale,
        'p3' => $newmin,
        'a' => $cm->instance
    );

    // Only rescale grades that are greater than or equal to 0. Anything else is a special value.
    $sql = 'UPDATE {simplelesson} set grade = (((grade - :p1) * :p2) + :p3)
            where id = :a and grade >= 0';
    $dbupdate = $DB->execute($sql, $params);
    if (!$dbupdate) {
        return false;
    }

    // Now re-push all grades to the gradebook.
    // Get this instance of simplelesson.
    $simplelesson = $DB->get_record('simplelesson', ['id' => $cm->instance], '*', MUST_EXIST);
    simplelesson_update_grades($simplelesson);

    return true;
}

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by get_file_info_context_module().
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function simplelesson_get_file_areas($course, $cm, $context) {
    return ['pagecontents' => 'for page files editor content'];
}

/**
 * File browsing support for simplelesson file areas
 *
 * @package mod_simplelesson
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function simplelesson_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the simplelesson file areas
 *
 * @package mod_simplelesson
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the simplelesson's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function simplelesson_pluginfile($course, $cm, $context, $filearea, array $args,
        $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }
    require_login($course, true, $cm);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_simplelesson/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    // Finally send the file.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
/**
 *
 * Modified for use in mod_simplelesson by Richard Jones http://richardnz/net
 * This is used for images within pages that are in questions.
 *
 * @package    mod_simplelesson
 * @see package mod_qpractice
 * @copyright  2013 Jayesh Anandani
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package  mod_simplelesson
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string $component the name of the component we are serving files for.
 * @param string $filearea the name of the file area.
 * @param int $qubaid the attempt usage id.
 * @param int $slot the id of a question in this quiz attempt.
 * @param array $args the remaining bits of the file path.
 * @param bool $forcedownload whether the user must be forced to download the file.
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function simplelesson_question_pluginfile($course, $context, $component,
         $filearea, $qubaid, $slot, $args,
         $forcedownload, array $options = array()) {

    require_login($course);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Extends the global navigation tree by adding simplelesson nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the simplelesson module instance
 * @param stdClass $course current course record
 * @param stdClass $module current simplelesson instance record
 * @param cm_info $cm course module information
 */
function simplelesson_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the simplelesson settings
 *
 * This function is called when the context for the page is a simplelesson module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $simplelessonnode simplelesson administration node
 */
function simplelesson_extend_settings_navigation(settings_navigation
        $settingsnav, navigation_node $simplelessonnode=null) {
    // Provide a link to the attempts management page.
    global $PAGE;
    $attemptsurl = new moodle_url(
            '/mod/simplelesson/manage_attempts.php',
            array('courseid' => $PAGE->course->id));
    $simplelessonnode->add(get_string('manage_attempts',
            'mod_simplelesson'), $attemptsurl);
}
