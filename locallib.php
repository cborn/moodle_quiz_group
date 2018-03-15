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
 * Library of functions used by the quiz module.
 *
 * This contains functions that are called from within the quiz group sub_module only
 *
 * @package   quiz_group
 * @copyright 2017 Camille Tardy, University of Geneva
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Return grouping used in Group quiz or false if not found
 * @param $quizid
 * @return $groupingid
 */
function get_groupquiz_groupingid($quizid){
    global $DB;

    //todo use get_fieldset_select instead of get_record ??
    $quiz_group_groupingid = $DB->get_record('quiz_group', array('quizid'=>$quizid), 'groupingid', 'IGNORE_MISSING');

    if($quiz_group_groupingid == false){
        $groupingid = null;
    }else{
        $groupingid = $quiz_group_groupingid->groupingid;
    }


    return $groupingid;
}

/**
 * Retrieve group id for the quiz according to the user's groups and the quiz grouping
 * -> returns grpid = 0 if user not in grouping
 *
 * @param int $userid
 * @param int $quizid
 * @param int $courseid
 *
 * @return $grpid
 */
function get_user_group_for_groupquiz($userid, $quizid, $courseid, $groupingID = null){
    // retreive all groups for user
    $user_grpids = groups_get_user_groups($courseid, $userid);
    //keep only grp ids
    $user_grps = array();
    foreach ($user_grpids as $key=>$gpid){
        foreach ($gpid as $k=>$gid) {
            // if not alreday in array add id
            if (!in_array($gid, $user_grps, false)){
                $user_grps[] = $gid;
            }
        }
    }

    // retrieve grouping ID used in Quiz_group
    if($groupingID == null ){
        $groupingID = get_groupquiz_groupingid($quizid);
    }

    // filter group from grouping.
    $grpsingrouping = groups_get_all_groups(intval($courseid), null ,intval($groupingID));
    $grps_in_ging = array();
    //keep only grp ids
    foreach ($grpsingrouping as $gp){
        $grps_in_ging[] = $gp->id;
    }

    //compare the 2 arrays and retrieve group id
    $grpid = 0;
    $grps_intersect = array_intersect($user_grps, $grps_in_ging);
    //if not empty grp_intersect pick the first group.

    if (!empty($grps_intersect)){
        $grpid = $grps_intersect[0];
    }

    return $grpid;
}


/**
 * Transform an attempt obj (event) in a group attempt object to save in DB
 *
 * @param quiz_attempt $attempt
 * @return group_attempt
 */
function quiz_group_attempt_to_groupattempt_dbobject($attempt, $quizid, $grpid, $groupingid){

    //fetch the informations
    $userid = $attempt['userid'];
    //$courseid = $attempt['courseid'];

    //get user grp for given quiz
    //$grpid = get_user_group_for_groupquiz($userid, $quiz_id, $courseid);

    // fill in the group_attempt object
    $grp_attempt = new \stdClass();
    //attemptid cannot be found here as attempt not yet saved in DB, set default to null;
    $grp_attempt->attemptid = null;
    $grp_attempt->quizid = $quizid;
    $grp_attempt->userid = $userid;
    $grp_attempt->groupid = $grpid;
    $grp_attempt->groupingid = $groupingid;
    $grp_attempt->timemodified = time();


    return $grp_attempt;
}


/**
 * Create group attempt in DB
 * from quiz attempt in DB
 *
 * @param $attempt
 * @param $courseid
 */
function create_grpattempt_from_attempt($attempt,$courseid)
{
    global $DB;

    $userid = $attempt->userid;
    $quizid = $attempt->quiz;
    $groupingid = get_groupquiz_groupingid($quizid);

    $grp_att = new stdClass();
    $grp_att->attemptid = $attempt->id;
    $grp_att->quizid = $quizid;
    $grp_att->groupingid =
    $grp_att->timemodified = time(); //now

    $grp_att->groupid = get_user_group_for_groupquiz($userid, $quizid, $courseid, $groupingid);

    /*if($groupingid == null || $groupingid ==0){
        //DO nothing grp is not a grp quiz
    }else */
    if($groupingid>0 && $grp_att->groupid > 0){
        //create grp attempt in DB
        $DB->insert_record('quiz_group_attempts', $grp_att);
    }else if($groupingid>0 && $grp_att->groupid == 0){
        //do not save group attempt if its value is 0, and display error message
        //dispaly error message user not in grouing selected for group quiz
        \core\notification::error(get_string('user_notin_grouping', 'quiz_group'));
    }

}


/**
 * Dispatch grade function.
 * @param quizid $quizid
 * @param groupingid $groupingID
 *
 */
function dispatch_grade($quiz, $groupingID) {
    global $DB, $PAGE;
    $quizid = $quiz->id;
    $courseid = $PAGE->course->id;


    $grp_attempts_array = $DB->get_records('quiz_group_attempts', array('quizid'=>$quizid, 'groupingid'=>$groupingID));
    //change order of fields to get userid as index for grade array.
    $quizgrades_array = $DB->get_records('quiz_grades', array('quiz'=>$quizid), '', 'userid, id, quiz, grade, timemodified');
  //  $quizattempts_array = $DB->get_records('quiz_attempts', array('quiz'=>$quizid, 'state' =>'finished'));


    //if no grp attempt : create from DB if they exist.
    if(empty($grp_attempts_array)){
        //check if attempts exist in attempt table that didnt get saved in grp attempt dB; if yes copy them in grp attempt table
        $quizattempts_array = $DB->get_records('quiz_attempts', array('quiz'=>$quizid, 'state' =>'finished'));

        foreach ($quizattempts_array as $att){
            //if user not in correct grouping do not create
            $grpid = get_user_group_for_groupquiz($att->userid, $quizid, $courseid);
            if($grpid > 0){
                create_grpattempt_from_attempt($att,$courseid);
            }// if user not in grouping do not create grp_attempt
        }
    }


    foreach ($grp_attempts_array as $grp_attempt){
        //get group id
        $groupid = $grp_attempt->groupid;
        $attemptid = $grp_attempt->attemptid;
        //get attempt for grp_attempt
        $attempt = $DB->get_record('quiz_attempts', array('id'=>$attemptid));

        //get all user for group id
        $users = groups_get_members($groupid, 'u.id');

        //retrieve grade from this user
        $insert_grade = new \stdClass();
        foreach($quizgrades_array as $qg){
            if ($qg->userid == $attempt->userid){
                // copy grade value to insert item
                $insert_grade->quiz = $qg->quiz;
                $insert_grade->userid = $qg->userid;
                $insert_grade->grade = $qg->grade;
                $insert_grade->timemodified = $qg->timemodified;
            }
        }

        //duplicate grade for each user iin list
        foreach ($users as $u){
            //delete current user of users list
            if ($u->id == $attempt->userid){
                // user of original grade, do nothing
            }else{

                // Deal with quiz grade table
                $insert_grade->userid = $u->id;
                // if not already in DB
                $user_quiz_grade_db = $DB->get_record('quiz_grades', array('quiz'=>$quizid, 'userid'=>$u->id));
                if ($user_quiz_grade_db == false){
                    // if not exist insert in DB
                    $DB->insert_record('quiz_grades', $insert_grade, false);
                   // echo 'ok insert grade user :'.$u->id.'<br/>';
                }else if ($user_quiz_grade_db->grade !== $qg->grade){
                    // if exist but grade different, update grade

                    $update= new stdClass();
                    $update->id = $quizgrades_array[$u->id]->id;
                    $update->grade = $insert_grade->grade;
                    $DB->update_record('quiz_grades', $update, false);
                }


                // Deal with gradeBook
                //get user grade for quiz
                $grade_forquiz = quiz_get_user_grades($quiz, $u->id);
                if($grade_forquiz && ($grade_forquiz[$u->id]->rawgrade !== $insert_grade->grade)){
                    //if exist, update if grade is different
                    quiz_grade_item_update($quiz, $grade_forquiz);
                }else if(empty($grade_forquiz)){
                    //if dont exist create grade
                    $grade = new stdClass();
                    $grade->userid = $u->id;
                    $grade->rawgrade = $insert_grade->grade;

                    quiz_grade_item_update($quiz, $grade);

                }
            }

        }
    }

    //display validation message
    \core\notification::success(get_string('dispatchgrade_done', 'quiz_group'));

}


/**
 * Logic to happen when a/some group(s) has/have been deleted in a course.
 * Check which grps are valid in a given course, which quiz exist in course (quiz_id)
 * for each quiz verify if grp_attempt exist and
 * delete those that belong to a group that is no longer in the active group list
 *
 * @param int $courseid The course ID.
 * @return void
 */
function quiz_process_grp_deleted_in_course($courseid) {
    global $DB;


    // get course group (return :  array of group objects (id, courseid, name, enrolmentkey)
    // translate in text list the ids
    $groups = $DB->get_records('groups', array('courseid'=>$courseid),'', 'id');
    $groups_list = "";
    foreach($groups as $g){
        $groups_list.= '"'.$g->id.'",';
    }

    //get all course quizs id
    $quizs_id = $DB->get_records('quiz', array('course'=>$courseid), '', 'id, name');

    //get all grp attempts foreach quizs id ang groups not in list (--> deleted)
    foreach($quizs_id as $key=>$q){
        $sql = "SELECT id FROM {quiz_group_attempts} WHERE quizid = ? AND groupid NOT IN (?)";
        $grp_attempts_id = $DB->get_records_sql($sql, array($q->id, $groups_list));
        //delete each grp attempt from deleted grp
        foreach($grp_attempts_id as $ga){
            //delete record in DB
            $att_id = $ga->id;
            $DB->delete_records('quiz_group_attempts', array('id'=>$att_id));
        }
    }



}

