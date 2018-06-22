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
 * This file defines the setting form for the quiz group.
 *
 * @package   quiz_group
 * @copyright 2017 Camille Tardy, University of Geneva
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


/**
 * Quiz group report settings form.
 *
 * @copyright 2017 Camille Tardy, University of Geneva
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_group_settings_form extends moodleform {


    protected function definition() {
        global $COURSE;

        $mform = $this->_form;

        $mform->addElement('header', 'quizgroupsubmission', get_string('quizgroup', 'quiz_group'));

        // todo : fix hasattempt --> kills action button return url (bad quiz id)
        // if attempt block edit.
        /* $mform->addElement('hidden', 'hasattempts');
         $mform->setType('hasattempts',PARAM_BOOL);
         $mform->setDefault('hasattempts', false);

         if($this->_customdata['hasattempts']===true){
             $mform->addElement('html', "<p style='color: red; background-color: #FFFECE; padding: 1px 10px;'>".get_string('quiz_has_attempts', 'quiz_group')."</p> <br/>");
            // $mform->setDefault('hasattempts', true);
         }*/

        $mform->addElement('html', "<p>".get_string('info_bygroup', 'quiz_group')."</p>");
        $mform->addElement('html', "<p><em>".get_string('warning_group', 'quiz_group')."</em></p></br></br>");

        $mform->addElement('html', "<h4>".get_string('title_groupingselect', 'quiz_group')."</h4>");

        // get grouping list from course
        $groupings = groups_get_all_groupings($COURSE->id);
        $options = array();
        $options[0] = get_string('no_grouping', 'quiz_group');
        foreach ($groupings as $grouping) {
            $options[$grouping->id] = $grouping->name;
        }

        // create select element and pre-select current value
        $mform->addElement('select', 'sel_groupingid', get_string('teamsubmissiongroupingid', 'assign'), $options);

        //submit button
        $mform->addElement('submit', 'savechanges', get_string('savechanges', 'quiz_group'));
        // $mform->disabledIf('submitbutton', 'hasattempts', 'eq',true);
        // $mform->disabledIf('sel_groupingid', 'hasattempts', 'eq',true);

    }

    function validation($data, $files) {
        // No form validation needed yet.
    }
}
