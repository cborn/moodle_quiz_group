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
 * This file defines the quiz group.
 *
 * @package   quiz_group
 * @copyright 2017 Camille Tardy, University of Geneva
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/report/group/groupsettings_form.php');
require_once($CFG->dirroot . '/mod/quiz/report/group/locallib.php');


/**
 * Quiz group to enable group evaluation for Quiz.
 *
 *
 *
 * @copyright 2017 Camille Tardy, University of Geneva
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_group_report extends quiz_default_report{

    protected $viewoptions = array();
    protected $questions;
    protected $cm;
    protected $quiz;
    protected $context;

    /**
     * @param the $quiz
     * @param the $cm
     * @param this $course
     */
    public function display($quiz, $cm, $course)
    {
        global $DB, $PAGE;
     //   global $OUTPUT;

        $this->quiz = $quiz;
        $this->cm = $cm;
        $this->course = $course;

        $pageoptions = array();
        $pageoptions['id'] = $cm->id;
        $pageoptions['quizid'] = $quiz->id;

       // $actionurl = new moodle_url('/mod/quiz/view.php', $pageoptions);


        // retrieve current grouping value for the given quizid return false if not found
        $grouping_record = $DB->get_record('quiz_group', array('quizid'=>$quiz->id), 'id, groupingid', 'IGNORE_MISSING');

        $groupingID = 0;
        // if grouping id exist use the ID else set to 0 (-> no grouping selected)
        if ($grouping_record!==false){
            $groupingID = $grouping_record->groupingid;
        }


        $bool_hasattempts = quiz_has_attempts($quiz->id);


        // create quiz group setting form
        $form_params = array('quizid' => $quiz->id, 'idnumber' => $cm->id,  'hasattempts'=>$bool_hasattempts);

        $mform = new quiz_group_settings_form($form_params, 'get');
        $toform = array("sel_groupingid"=>$groupingID/*, 'hasattempts'=>$bool_hasattempts*/);

        // if cancel do nothing
        if ($mform->is_cancelled()) {
            //return to view quiz page
            redirect(new moodle_url('/mod/quiz/view.php', array('id' => $cm->id)), get_string('canceledit', 'quiz_group'));

            // if edited get edited info.
        } else if ($fromform = $mform->get_data()){
            // should retrieve sel_groupingid value here
            $grouping_id_updated = $fromform->sel_groupingid;

            if ($grouping_record == false){
                // no existing record, create one
                $record = new stdClass();
                $record->groupingid = $grouping_id_updated;
                $record->quizid = $quiz->id;

                $DB->insert_record('quiz_group', $record, false);

            }else{
                // existing record, update it
                $grouping_obj = array('id' => $grouping_record->id , 'groupingid' =>  $grouping_id_updated);

                $DB->update_record('quiz_group', $grouping_obj, $bulk=false);
            }

            $final_grouping_name = get_string('no_group_string', 'quiz_group');
            if($grouping_id_updated > 0){
                $DB_grouping = $DB->get_record('groupings', array('id'=>$grouping_id_updated), 'name', 'IGNORE_MISSING');
                $final_grouping_name = $DB_grouping->name;
            }

            //return to view quiz page with validation message
            redirect(new moodle_url('/mod/quiz/view.php', array('id' => $cm->id)), get_string('settings_edited', 'quiz_group', $final_grouping_name));
        }

        $mform->set_data($toform);
        $this->print_header_and_tabs($cm, $course, $quiz, 'editquizsettings');
        $mform->display();



        // Dispatch grades to other group members button
        $pageoptions['mode'] = "group";
        $actionurl = $PAGE->url;
        $info_dispatchgrades_string = get_string('info_dispatchgrades', 'quiz_group');
        $apply_string = get_string('apply', 'quiz_group');
        $titleapply_string = get_string('titleapply', 'quiz_group');

        $out ='<br/>';
        $out .= html_writer::start_tag('form', array('method' => 'post',
            'action' => $actionurl,
            'class' => 'mform', 'id' => 'form_groupdispatchgrade'));
        $out .=  html_writer::tag('h4', $titleapply_string);
        $out .=  html_writer::tag('p', $info_dispatchgrades_string);
        $out .=  html_writer::input_hidden_params(new moodle_url('', array(
            'groupingid' => $groupingID,  'sesskey' => sesskey())));

        $submit_btn = html_writer::empty_tag('input', array('type'=>'submit', 'value' => $apply_string, 'name'=> 'dispatchgrades'));
        $submit_div =  html_writer::tag('div',$submit_btn, array('class'=>'felement fgroup'));

        $out .= html_writer::tag('div',$submit_div, array('id'=>'fgroup_id_buttonar', 'class'=>'fitem fitem_actionbuttons fitem_fgroup'));

        $out .= html_writer::end_tag('form');


        echo $out;
        //retreive submitted data, false if none
        $data = data_submitted();

        // is there submitted data?
        if ($data !== false){
            // Process dispatch grades.
            if ($data->dispatchgrades == $apply_string && confirm_sesskey()){
                dispatch_grade($quiz, $groupingID);
            }
        }


    }




}
