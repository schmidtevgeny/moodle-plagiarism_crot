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
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @since 2.0
 * @package    plagiarism_crot
 * @subpackage plagiarism
 * @author     Dan Marsden, Sergey Butakov, Svetlana Kim
 * @copyright  2010 Dan Marsden, Sergey Butakov, Svetlana Kim
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//get global class
global $CFG, $DB;
require_once($CFG->dirroot . '/plagiarism/lib.php');


///// Crot Class ////////////////////////////////////////////////////
class plagiarism_plugin_crot extends plagiarism_plugin {
    public function get_all_system_config() {
        global $DB;

        static $map = [];
        if (empty($map)) {
            $records = $DB->get_records('config_plugins', [
                'plugin' => 'plagiarism_crot',
            ]);

            foreach ($records as $record) {
                $map[$record->name] = $record->value;
            }
        }

        return $map;
    }

    /**
     * hook to allow plagiarism specific information to be displayed beside a submission
     * хук, позволяющий отображать информацию о плагиате рядом с отправленным материалом
     * @param array $linkarray contains all relevant information for the plugin to generate a link
     * @return string
     *
     */
    public function get_links($linkarray) {
        global $DB, $CFG, $PAGE;
        // echo '<pre>';
        if (array_key_exists('forum', $linkarray)) return '';
        if (!array_key_exists('file', $linkarray)) return '';
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $file = $linkarray['file'];
        $course = $linkarray['course'];
        $output = "";
        $cid = $course->id;
        if (empty($cid)) {
            $cid = $course;
        }
        //add link/information about this file to $output
        if (!empty($file)) { //sw
            if (!$plagiarism_crot_files_rec = $DB->get_record("plagiarism_crot_files", ["file_id" => $file->get_id()])) {
                $output .= '';// if there is no record in plagiarism_crot_files about this file then nothing to show
            } else {
                if (!$crot_doc_rec = $DB->get_record("plagiarism_crot_documents", ["crot_submission_id" => $plagiarism_crot_files_rec->id])) {
                    $output .= '';// if there is no record in plagiarism_crot_documents about this file then nothing to show
                } else {
                    $sql_query = "SELECT max(number_of_same_hashes) AS max 
                                    FROM {plagiarism_crot_spair} 
                                   WHERE submission_a_id = :submission_a_id
                                         OR  submission_b_id = :submission_b_id";
                    if (!$similarity = $DB->get_record_sql($sql_query, ['submission_a_id' => $crot_doc_rec->id, 'submission_b_id' => $crot_doc_rec->id])) {
                        // get maximum number of same hashes for the current document
                        $output .= html_writer::empty_tag('br') .
                            html_writer::tag('b', get_string('no_similarities', 'plagiarism_crot'));
                    } else {
                        $sql_query = "SELECT count(*) AS cnt 
                                        FROM {plagiarism_crot_fingerprint} 
                                       WHERE crot_doc_id = :crot_doc_id";
                        $numbertotal = $DB->get_record_sql($sql_query, ['crot_doc_id' => $crot_doc_rec->id]);// get total number of hashes for the current document
                        if ($numbertotal->cnt == 0) {
                            $perc = 0;
                        } else {
                            $perc = round(($similarity->max / $numbertotal->cnt) * 100, 2);
                        }

                        if (has_capability('mod/assignment:grade', $PAGE->context)) {
                            $output .= html_writer::empty_tag('br') .
                                html_writer::tag('b',
                                    html_writer::link(
                                        new moodle_url('/plagiarism/crot/index.php',
                                            ['id_a' => $crot_doc_rec->id, 'user_id' => $userid, 'cid' => $cid]),
                                        get_string('val_similarity_score', 'plagiarism_crot', $perc)
                                    )
                                );
                        } else {
                            $output .= html_writer::empty_tag('br') .
                                html_writer::tag('b',
                                    get_string('val_similarity_score', 'plagiarism_crot', $perc)
                                );
                        }
                    }
                }
            }
        } else {
            $path = $linkarray['content'];
            $path = strip_tags($path);
            $path = sha1($path);

            $sql_query = "SELECT * 
                            FROM {plagiarism_crot_files} 
                           WHERE path = :path 
                                 AND courseid = :courseid 
                                 AND cm = :cm 
                        ORDER BY file_id DESC 
                           LIMIT 1";

            $file = $DB->get_record_sql($sql_query, ['path' => $path, 'courseid' => $course, 'cm' => $cmid]);
            $fileid = $file->file_id;

            if (!$plagiarism_crot_files_rec = $DB->get_record("plagiarism_crot_files", ["file_id" => $fileid])) {
                $output .= html_writer::tag('small', html_writer::tag('i', 'Pending')) . ' ';
                // if there is no record in plagiarism_crot_files about this file then nothing to show
            } else {
                if (!$crot_doc_rec = $DB->get_record("plagiarism_crot_documents", ["crot_submission_id" => $plagiarism_crot_files_rec->id])) {
                    $output .= '';// if there is no record in plagiarism_crot_documents about this file then nothing to show
                } else {
                    $sql_query = "SELECT max(number_of_same_hashes) AS max 
                                    FROM {plagiarism_crot_spair} 
                                   WHERE submission_a_id = :submission_a_id
                                         OR  submission_b_id = :submission_b_id";
                    if (!$similarity = $DB->get_record_sql($sql_query, ['submission_a_id' => $crot_doc_rec->id, 'submission_b_id' => $crot_doc_rec->id])) {
                        // get maximum number of same hashes for the current document
                        $output .= html_writer::empty_tag('br') .
                            html_writer::tag('b', get_string('no_similarities', 'plagiarism_crot'));
                    } else {
                        $sql_query = "SELECT count(*) AS cnt 
                                        FROM {plagiarism_crot_fingerprint} 
                                       WHERE crot_doc_id = :crot_doc_id";
                        $numbertotal = $DB->get_record_sql($sql_query, ['crot_doc_id' => $crot_doc_rec->id]);// get total number of hashes for the current document
                        if ($numbertotal->cnt == 0) {
                            $perc = 0;
                        } else {
                            $perc = round(($similarity->max / $numbertotal->cnt) * 100, 2);
                        }
                        if (has_capability('mod/assignment:grade', $PAGE->context)) {
                            $output .= html_writer::empty_tag('br') .
                                html_writer::tag('b',
                                    html_writer::link(
                                        new moodle_url('/plagiarism/crot/index.php',
                                            ['id_a' => $crot_doc_rec->id, 'user_id' => $userid, 'cid' => $cid]),
                                        get_string('val_similarity_score', 'plagiarism_crot', $perc)
                                    )
                                );
                        } else {
                            $output .= html_writer::empty_tag('br') .
                                html_writer::tag('b',
                                    get_string('val_similarity_score', 'plagiarism_crot', $perc));
                        }
                    }
                }
            }
        }

        return $output;
    }

    /**
     * hook to allow a disclosure to be printed notifying users what will happen with their submission
     * хук, позволяющий печатать сообщение, уведомляющее пользователей о том, что произойдет с их работой
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $DB, $OUTPUT;
        // check if this cmid has plagiarism enabled
        $select = 'cm = ? AND ' . $DB->sql_compare_text('name') . ' = "enabled"';
        if (!$enabled = $DB->get_record_select('plagiarism_crot_config', $select, [$cmid])) {
            return '';
        } else if ($enabled->value == 0) {
            return '';
        }
        $plagiarismsettings = (array)get_config('plagiarism_crot');
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $outputhtml = $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $outputhtml .= format_text($plagiarismsettings['crot_student_disclosure'], FORMAT_MOODLE, $formatoptions);
        $outputhtml .= $OUTPUT->box_end();
        return $outputhtml;

    }

    /**
     * hook to allow status of submitted files to be updated - called on grading/report pages.
     * хук, позволяющий обновлять статус отправленных файлов - вызывается на страницах оценок/отчетов.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
        //called at top of submissions/grading pages - allows printing of admin style links or updating status
    }

    public function config_options() {
        return ['enabled', 'crot_local', 'crot_global'];
    }
}

function plagiarism_crot_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB;
    $plagiarismsettings = (array)get_config('plagiarism_crot');
    if (!empty($plagiarismsettings['enabled'])) {
        $cmid = optional_param('update', 0, PARAM_INT); //there doesn't seem to be a way to obtain the current cm a better way - $this->_cm is not available here.
        if (!empty($cmid)) {
            $plagiarismvalues = $DB->get_records_menu('plagiarism_crot_config', ['cm' => $cmid], '', 'name,value');
        }
        $plugin = new plagiarism_plugin_crot();
        $plagiarismelements = $plugin->config_options();

        $ynoptions = [0 => get_string('no'), 1 => get_string('yes')];
        $mform->addElement('header', 'crotdesc', get_string('crot', 'plagiarism_crot'));
        $mform->addHelpButton('crotdesc', 'crot', 'plagiarism_crot');
        $mform->addElement('select', 'enabled', get_string("usecrot", "plagiarism_crot"), $ynoptions);
        $mform->addElement('select', 'crot_local', get_string("comparestudents", "plagiarism_crot"), $ynoptions);
        $mform->disabledIf('crot_local', 'enabled', 'eq', 0);
        $mform->setDefault('crot_local', '1');
        $mform->addElement('select', 'crot_global', get_string("compareinternet", "plagiarism_crot"), $ynoptions);
        $mform->disabledIf('crot_global', 'enabled', 'eq', 0);

        foreach ($plagiarismelements as $element) {
            if (isset($plagiarismvalues[$element])) {
                $mform->setDefault($element, $plagiarismvalues[$element]);
            }
        }
    }
}

function plagiarism_crot_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    $plugin = new plagiarism_plugin_crot();
    $plagiarismsettings = (array)get_config('plagiarism_crot');
    if (!empty($plagiarismsettings['enabled'])) {
        if (isset($data->enabled)) {
            //array of posible plagiarism config options.
            $plagiarismelements = $plugin->config_options();
            //first get existing values
            $existingelements = $DB->get_records_menu('plagiarism_crot_config', ['cm' => $data->coursemodule], '', 'name,id');
            foreach ($plagiarismelements as $element) {
                $newelement = new stdClass();
                $newelement->cm = $data->coursemodule;
                $newelement->name = $element;
                $newelement->value = (isset($data->$element) ? $data->$element : 0);
                if (isset($existingelements[$element])) { //update
                    $newelement->id = $existingelements[$element];
                    $DB->update_record('plagiarism_crot_config', $newelement);
                } else { //insert
                    $DB->insert_record('plagiarism_crot_config', $newelement);
                }
            }
        }
    }
    return $data;
}

function plagiarism_crot_extend_navigation_course($navigation, $course, $context) {
    global $PAGE;
    if (!$PAGE->course || $PAGE->course->id == SITEID) {
        return null;
    }
    if (has_capability('mod/assignment:grade', context_course::instance($course->id))) {
        $url = new moodle_url('/plagiarism/crot/menu.php', ['id' => $course->id]);
        $settingsnode = navigation_node::create('CROT',
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/settings', ''));

        $navigation->add_node($settingsnode);
    }
}