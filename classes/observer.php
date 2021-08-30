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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->dirroot . '/plagiarism/crot/lib.php');

class plagiarism_crot_observer {
    // todo: привязать ?
    function crot_event_files_done($eventdata) {
        global $DB;
        $result = true;
        //a file has been uploaded - submit this to the plagiarism prevention service.

        return $result;
    }

    // used
    function crot_event_file_uploaded(\mod_assign\event\assessable_submitted $event) {
        global $DB, $CFG;
        $result = true;
        //mainly used by assignment finalize - used if you want to handle "submit for marking" events
        //a file has been uploaded/finalised - submit this to the plagiarism prevention service.
        $eventdata = $event->get_data();

        $cmid = $eventdata['contextinstanceid'];
        $plagiarismvalues = $DB->get_records_menu('plagiarism_crot_config', ['cm' => $cmid], '', 'name,value');
        if (empty($plagiarismvalues['enabled'])) {
            return $result;
        } else {
            //sw 21/02

            $cm = $DB->get_record('course_modules', ['id' => $cmid]);
            if (empty($cm)) {
                return $result;
            }
            $modulename = $DB->get_field('modules', 'name', ['id' => $cm->module]);
            //sw uncommented
            require_once("$CFG->dirroot/mod/$modulename/lib.php");
            //sw
            $status_value = ['queue', 'in_processing', 'end_processing'];
            // echo '<pre>';var_dump($event);die;
            if ($modulename == 'assign') {
                $moodlesubmission = $DB->get_record('assign_submission', ['id' => $eventdata['objectid']], 'id');

                $eventdata->pathnamehashes = [];
                $filesconditions = ['component' => 'assignsubmission_file',
                    'itemid' => $moodlesubmission->id,
                    'userid' => $eventdata['userid']];
                if ($moodlefiles = $DB->get_records('files', $filesconditions)) {
                    // var_dump($moodlefiles);die;
                    foreach ($moodlefiles as $moodlefile) {
                        // $eventdata->pathnamehashes[] = $moodlefile->pathnamehash;
                        $newelement = new stdClass();
                        $newelement->file_id = $moodlefile->id;
                        $newelement->path = $moodlefile->contenthash;
                        $newelement->status = $status_value[0];
                        $newelement->time = time();
                        $newelement->cm = $cmid;
                        $newelement->courseid = $eventdata['courseid'];
                        $result = $DB->insert_record('plagiarism_crot_files', $newelement);
                        //echo "\nfile " . $file->get_filename() . " was queued up for plagiarism detection service\n";
                    }
                }

            }

            return $result;
        }
    }

    // todo: привязать ?
    function crot_event_content_uploaded($eventdata) {
        global $DB;

        if ($eventdata->modulename == "assign") {
            $context = context_course::instance($eventdata->courseid);
            $filename = "crot_" . $eventdata->courseid . "_" . $eventdata->cmid . "_" . $eventdata->userid . "_" . time() . ".txt";
            $filepath = "/"; // has to start and end with /, rule of the Moodle file storage api
            print_r($eventdata);
            $filerecord = new stdclass();
            $filerecord->contextid = $context->id;
            $filerecord->userid = $eventdata->userid;
            $filerecord->component = "assignsubmission_onlinetext";
            $filerecord->filearea = "onlinetext";
            //sw
            $filerecord->itemid = $eventdata->itemid;
            $uid321 = $filerecord->userid;

            $urecm = $DB->get_record('user', ['id' => $uid321]);

            $filerecord->author = $urecm->firstname . " " . $urecm->lastname;
            // sw end
            $filerecord->filepath = $filepath;
            $filerecord->filename = $filename;

            $fs = get_file_storage(); // using file storage system ensures that the file can be located by other api calls
            // this creates a hash-version of the file in the Moodle data folder and also creates entries in the 'files' table
            $content = strip_tags($eventdata->content);
            $file = $fs->create_file_from_string($filerecord, $content); // convert online text to a text file

            $content = strip_tags($eventdata->content);
            $plagiarism_file = new stdClass(); // create an entry in the crot table
            $plagiarism_file->file_id = $file->get_id();
            $plagiarism_file->path = $file->get_contenthash();
            $plagiarism_file->status = 'queue';
            $plagiarism_file->time = time();
            $plagiarism_file->cm = $eventdata->cmid;
            $plagiarism_file->courseid = $eventdata->courseid;
            //$plagiarism_file->userid = $eventdata->userid;

            $result = $DB->insert_record('plagiarism_crot_files', $plagiarism_file);
            echo "\nType-in assignment was queued up for plagiarism detection service\n";
        }

        return $result;
    }

    // todo: привязать ?
    function crot_event_mod_created($eventdata) {
        $result = true;
        //a new module has been created - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

        return $result;
    }

    // todo: привязать ?
    function crot_event_mod_updated($eventdata) {
        $result = true;
        //a module has been updated - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

        return $result;
    }

    // used
    function crot_event_mod_deleted($eventdata) {
        $result = true;
        //a module has been deleted - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

        return $result;
    }

}