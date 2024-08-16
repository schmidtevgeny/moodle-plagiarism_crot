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
 * Update report Scores from Turnitin.
 *
 * @package    plagiarism_turnitin
 * @author     John McGettrick http://www.turnitin.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_crot\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Update report Scores from Turnitin.
 */
class update_reports extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('updatereportscores', 'plagiarism_crot');
    }

    public function execute() {
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $starttime = $mtime;

        global $CFG, $DB;

        require_once($CFG->dirroot . '/plagiarism/crot/lib.php');
        require_once($CFG->dirroot . '/plagiarism/crot/locallib.php');
        require_once($CFG->dirroot . "/course/lib.php");
//        require_once($CFG->dirroot . "/mod/assignment/lib.php");

        $DB2=local_crot_db();

        $plagiarismsettings = (array)get_config('plagiarism_crot');
        $gram_size = $plagiarismsettings['crot_grammarsize'];
        $window_size = $plagiarismsettings['crot_windowsize'];
        $query_size = $plagiarismsettings['crot_global_search_query_size'];
        $msnkey = $plagiarismsettings['crot_live_key'];
        $culture_info = $plagiarismsettings['crot_culture_info'];
        $globs = $plagiarismsettings['crot_percentage_of_search_queries'];
        $todown = $plagiarismsettings['crot_number_of_web_documents'];

        if (empty($gram_size) || empty($window_size)) {
            die('The plugin is not properly set. Please set the plugin in admin/plugins/plagiarism prevention menu');    /// the initial settigns were not properly set up
        }

        $sql_query = "SELECT cf.* FROM {plagiarism_crot_files} cf WHERE cf.status = 'queue'";
        $files = $DB->get_records_sql($sql_query, [], 0, 10);
        $sql_query = "SELECT count(1) as cnt FROM {plagiarism_crot_files} cf WHERE cf.status = 'queue'";
        $wait = $DB->get_record_sql($sql_query);
        echo "\n{$wait->cnt} file in queue";

        if (!empty($files)) {
            foreach ($files as $afile) {
                try
                {
                    $afile->status = 'in_processing';
                    $DB->update_record('plagiarism_crot_files', $afile);

                    echo "\nfile $afile->id was not processed yet. start processing now ... \n";
                    $atime = microtime();
                    $atime = explode(" ", $atime);
                    $atime = $atime[1] + $atime[0];
                    $astarttime = $atime;

                    $fs = get_file_storage();
                    $file = $fs->get_file_by_id($afile->file_id);

                    $filename = $file->get_filename(); // get file name

                    $arrfilename = explode(".", $filename);
                    $ext = $arrfilename[count($arrfilename) - 1];// get file extension
                    $l1 = $afile->path[0] . $afile->path[1];
                    $l2 = $afile->path[2] . $afile->path[3];
                    $apath = $CFG->dataroot . "/filedir/$l1/$l2/$afile->path";  // get file path

                    // call tokenizer to get plain text and store it in plagiarism_crot_documents
                    $atext = tokenizer($apath, $ext);
                    $atext = mb_substr($atext, 0, 999000);
                    // insert into plagiarism_crot_documents
                    $docrecord = new \stdClass();
                    $docrecord->crot_submission_id = $afile->id;
                    $docrecord->content = addslashes($atext);
                    $docid = $DB->insert_record('plagiarism_crot_documents', $docrecord);

                    // fingerprinting - calculate and store the fingerprints into the table
                    $atext = mb_strtolower($atext, "utf-8");

                    // get f/print
                    $fingerp = [];
                    $fingerp = GetFingerprint($atext);

                    // store fingerprint
                    foreach ($fingerp as $fp) {
                        $hashrecord = new \stdClass();
                        $hashrecord->position = $fp->position;
                        $hashrecord->crot_doc_id = $docid;
                        $hashrecord->value = $fp->value;
                        $DB2->insert_record("plagiarism_crot_fingerprint", $hashrecord);
                    }

                    // local search
                    echo "starting local search \n";
                    $plagiarismvalues = $DB->get_records_menu('plagiarism_crot_config', ['cm' => $afile->cm], '', 'name,value');
                    if ($plagiarismvalues['crot_local'] == 1) {
                        // comparing fingerprints and updating plagiarism_crot_spair table
                        // select all submissions that has at least on common f/print with the current document
                        $sql_query = "SELECT id
                                        FROM {plagiarism_crot_documents} asg
                                       WHERE EXISTS (
                                             SELECT * 
                                               FROM {plagiarism_crot_fingerprint} fp1
                                               JOIN {plagiarism_crot_fingerprint} fp2 
                                                 ON fp1.value = fp2.value
                                              WHERE fp2.crot_doc_id = asg.id 
                                                    AND fp1.crot_doc_id = :crot_doc_id
                                             )";
                        $sql_query = "SELECT distinct fp2.crot_doc_id as id
       FROM
           {plagiarism_crot_fingerprint} fp1
       JOIN {plagiarism_crot_fingerprint} fp2
         ON fp1.value = fp2.value
      WHERE  fp1.crot_doc_id = :crot_doc_id";
                        $pair_submissions = $DB2->get_records_sql($sql_query, ['crot_doc_id' => $docid]);

                        foreach ($pair_submissions as $pair_submission) {
                            // check if id exists in web_doc table then don't compare because
                            // we consider only local documents here
                            if ($webdoc = $DB->get_record("plagiarism_crot_webdoc", ['document_id' => $pair_submission->id]))
                                continue;
                            //compare two fingerprints to get the number of same hashes
                            if ($docid != $pair_submission->id) {
                                $sql_query = "SELECT sum(CASE WHEN cnt1 < cnt2 THEN cnt1 ELSE cnt2 END) cnt
                                                FROM (
                                                      SELECT count(*) AS cnt1, 
                                                             (SELECT count(*)
                                                                FROM {plagiarism_crot_fingerprint} fp2
                                                               WHERE fp2.crot_doc_id = :docid  
                                                                     AND fp2.value = fp1.value
                                                             ) AS cnt2
                                                      FROM {plagiarism_crot_fingerprint} fp1
                                                      WHERE fp1.crot_doc_id = :crot_doc_id
                                                      GROUP BY fp1.value
                                                ) t";
                                $similarnumber = $DB2->get_record_sql($sql_query, ['crot_doc_id' => $pair_submission->id, 'docid'=>$docid]);
                                // takes id1 id2 and create/update record with the number of similar hashes
                                $sql_query = "SELECT * 
                                                FROM {plagiarism_crot_spair} 
                                               WHERE (submission_a_id = $afile->id 
                                                         AND submission_b_id = $pair_submission->id) 
                                                     OR (submission_a_id = $pair_submission->id 
                                                         AND submission_b_id = $afile->id)";
                                $pair = $DB2->get_record_sql($sql_query);
                                if (!$pair) {
                                    // insert
                                    $pair_record = new \stdClass();
                                    $pair_record->submission_a_id = $docid;
                                    $pair_record->submission_b_id = $pair_submission->id;
                                    $pair_record->number_of_same_hashes = $similarnumber->cnt;
                                    $DB2->insert_record("plagiarism_crot_spair", $pair_record);
                                } else {
                                    // TODO update
                                }
                            }    // end of comparing with local documents
                        }
                    } // end for local search

                    if ($plagiarismvalues['crot_global'] == 1) {
                        // global search
                        echo "\nfile $afile->id is selected for global search. Starting global search\n";
                        // strip text
                        $atext = StripText($atext, " ");
                        // create search queries
                        $words = [];
                        $words = preg_split("/[\s]+/", trim(StripText($atext, " ")));
                        $max = sizeof($words) - $query_size + 1;
                        $queries = [];
                        for ($i = 0; $i < $max; $i++) {
                            $query = "";
                            for ($j = $i; ($j - $i) < $query_size; $j++) {
                                $query = $query . " " . $words[$j];
                            }
                            $queries[] = $query;
                        }    // queries are ready!

                        // create list of URLs
                        srand((float)microtime() * 10000000);

                        // randomly select x% of queries
                        $rand_keys = array_rand($queries, (sizeof($queries) / 100) * $globs);
                        $narr = [];
                        foreach ($rand_keys as $mkey) {
                            $narr[] = $queries[$mkey];
                        }
                        $queries = $narr;

                        $tarr = getTopResults($queries, $todown, $msnkey, $culture_info);
                        $k = 0;
                        // get top results
                        foreach ($tarr as $manUrl) {
                            //get content of downloaded web document
                            // in php ini allow_url_fopen = On
                            $path = $manUrl->mainUrl;
                            // get content from the remote file
                            $mega = [];

                            // get content  and get encoding
                            if (trim($path) != "") {
                                try {
                                    $result = getremotecontent($path);
                                    if (trim($result) == "") {
                                        continue;
                                    }
                                } catch (\Exception $e) {
                                    print_error("exception in downloading!\n");
                                    $result = "Was not able to download the respective resource";
                                }
                            } else {
                                continue;
                            }

                            $result = mb_ereg_replace('#\s{2,}#', ' ', $result);

                            // split into strings and remove empty ones
                            $strs = explode("\n", $result);
                            $result = "";
                            foreach ($strs as $st) {
                                $st = trim($st);
                                if ($st != "") {
                                    $result = $result . mb_ereg_replace('/\s\s+/', ' ', $st) . " \n";
                                }
                            }
                            // insert doc into crot_doc table
                            $wdocrecord = new \stdClass();
                            $wdocrecord->crot_submission_id = 0;
                            $wdocrecord->content = addslashes($result);
                            $wdocid = $DB->insert_record("plagiarism_crot_documents", $wdocrecord);

                            // insert doc into web_doc table
                            $webdocrecord = new \stdClass();
                            $webdocrecord->document_id = $wdocid;
                            $webdocrecord->link = urlencode($manUrl->mainUrl);
                            $webdocrecord->link_live = urlencode($manUrl->msUrl);
                            $webdocrecord->is_from_cache = false;
                            $webdocrecord->related_doc_id = $docid;
                            $webdocid = $DB->insert_record("plagiarism_crot_webdoc", $webdocrecord);
                            $result = mb_convert_case($result, MB_CASE_LOWER, "UTF-8");

                            $fingerp = [];
                            try {
                                $fingerp = GetFingerprint($result);
                            } catch (\Exception $e) {
                                print_error("exception in FP calc\n");
                                continue;
                            }

                            // store fingerprint
                            foreach ($fingerp as $fp) {
                                $hashrecord->position = $fp->position;
                                $hashrecord->crot_doc_id = $wdocid;
                                $hashrecord->value = $fp->value;
                                $DB2->insert_record("plagiarism_crot_fingerprint", $hashrecord);
                            }

                            //compare two fingerprints to get the number of same hashes

                            $sql_query = "SELECT sum(CASE WHEN cnt1 < cnt2 THEN cnt1 ELSE cnt2 END) cnt
                                                FROM (
                                                      SELECT count(*) AS cnt1, 
                                                             (SELECT count(*)
                                                                FROM {plagiarism_crot_fingerprint} fp2
                                                               WHERE fp2.crot_doc_id = :docid  
                                                                     AND fp2.value = fp1.value
                                                             ) AS cnt2
                                                      FROM {plagiarism_crot_fingerprint} fp1
                                                      WHERE fp1.crot_doc_id = :crot_doc_id
                                                      GROUP BY fp1.value
                                                ) t";

                            try {
                                $similarnumber = $DB2->get_record_sql($sql_query, ['crot_doc_id' => $wdocid, 'docid'=>$docid]);
                            } catch (\Exception $e) {
                                print_error("exception in query\n");
                                continue;
                            }
                            // check that the number of same hashes is not null
                            if (!is_null($similarnumber->cnt) && $similarnumber->cnt != 0) {
                                // add record to pair table
                                $pair_record->submission_a_id = $docid;
                                $pair_record->submission_b_id = $wdocid;
                                $pair_record->number_of_same_hashes = $similarnumber->cnt;
                                $ppair = $DB2->insert_record("plagiarism_crot_spair", $pair_record);
                            } else {
                                //if null then remove the web document and its fingerprint
                                // remove from doc
                                $DB->delete_records("plagiarism_crot_documents", ["id" => $wdocid]);
                                // remove from fingerprints
                                $DB2->delete_records("plagiarism_crot_fingerprint", ["crot_doc_id" => $wdocid]);
                            }
                        }

                    } // end global search

                    $afile->status = 'end_processing';
                    $DB->update_record('plagiarism_crot_files', $afile);
                    echo "\nfile $afile->id was sucessfully processed\n";
                }
                catch (\Exception $e) {
                    echo "\nError in processing file $afile->id!\n";
                    continue;
                }
            }// end of the main loop
        } else {
            echo "No uploaded assignments to process!";
        }
        // calc and display exec time
        $mtime = microtime();
        $mtime = explode(" ", $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $endtime = $mtime;
        $totaltime = ($endtime - $starttime);
        echo "\nThe uploaded assignments were processed by crot in " . $totaltime . " seconds\n";

    }
}