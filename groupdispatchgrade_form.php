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
class quiz_group_dispatchgrade_form extends moodleform {


    protected function definition() {

        //todo: disable if no attempt

        $mform_dispatch = $this->_form;
        $mform_dispatch->addElement('header', 'quizgroupdispatchgrades', get_string('titleapply', 'quiz_group'));
        $mform_dispatch->addElement('html', "<p>".get_string('info_dispatchgrades', 'quiz_group')."</p>");
        $mform_dispatch->addElement('hidden', 'groupingid');

        //submit button
        $mform_dispatch->addElement('submit', 'dispatch', get_string('apply', 'quiz_group'));
    }

    function validation($data, $files) {
        //No form validation needed.
    }
}
