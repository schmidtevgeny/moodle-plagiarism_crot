<?php
/**
 *
 * @author Sergey Butakov, Svetlana Kim
 * this module compares two submissions side by side
 *
 */
require_once(dirname(__FILE__, 2) . '/../config.php');
global $CFG, $DB;
require_once($CFG->dirroot . "/plagiarism/crot/locallib.php");
require_once($CFG->dirroot . "/course/lib.php");
$DB2=local_crot_db();

// globals
$plagiarismsettings = (array)get_config('plagiarism_crot');
$minclustersize = $plagiarismsettings['crot_clustersize'];
$distfragments = $plagiarismsettings['crot_clusterdist'];
$allColors = explode(",", $plagiarismsettings['crot_colours']);

$ida = required_param('ida', PARAM_INT);   // submission A
$idb = required_param('idb', PARAM_INT);   // submission B
// получение ссылки на документ - удаленно
if (!$submA = $DB->get_record("plagiarism_crot_documents", ["id" => $ida])) {
    print_error(get_string('incorrect_docAid', 'plagiarism_crot'));
}
if ($submA->crot_submission_id == 0) {
    $isWebA = true;
} else {
    $isWebA = false;
}
// получение ссылки на документ - удаленно
if (!$submB = $DB->get_record("plagiarism_crot_documents", ["id" => $idb])) {
    print_error(get_string('incorrect_docBid', 'plagiarism_crot'));
}
if ($submB->crot_submission_id == 0) {
    $isWebB = true;
} else {
    $isWebB = false;
}

// TODO get global assignment id
if (!$isWebA) {
    // получение ссылки на документ - удаленно
    if (!$subA = $DB->get_record("plagiarism_crot_files", ["id" => $submA->crot_submission_id])) {
        print_error(get_string('incorrect_fileAid', 'plagiarism_crot'));
    }
    // получение файла
    if (!$filea = $DB->get_record("files", ["id" => $subA->file_id])) {
        print_error(get_string('incorrect_fileAid', 'plagiarism_crot'));
    }
    // sw define type of the assignment
    $asnAtype = $filea->component;
    switch ($asnAtype) {
        case "assignsubmission_file":
            require_once($CFG->dirroot . "/mod/assign/lib.php");
            $asnAtable = "assign";
            $asnAsubm = "assign_submission";
            break;
//sw 08/27
        case "assignsubmission_onlinetext":
            require_once($CFG->dirroot . "/mod/assign/lib.php");
            $asnAtable = "assign";
            $asnAsubm = "assign_submission";
            break;

//sw 0827 end
        case "mod_assignment":
            require_once($CFG->dirroot . "/mod/assignment/lib.php");
            $asnAtable = "assignment";
            $asnAsubm = "assignment_submissions";
            break;
    }
//	if (!$submissionA = $DB->get_record("assignment_submissions", array("id" => $filea->itemid))) {
    if (!$submissionA = $DB->get_record($asnAsubm, ["id" => $filea->itemid])) {
        print_error(get_string('incorrect_submAid', 'plagiarism_crot'));
    }
//        if (! $assignA = $DB->get_record("assignment", array("id" => $submissionA->assignment))) {
    if (!$assignA = $DB->get_record($asnAtable, ["id" => $submissionA->assignment])) {
        print_error(get_string('incorrect_assignmentAid', 'plagiarism_crot'));
    }
    if (!$courseA = $DB->get_record("course", ["id" => $subA->courseid])) {
        print_error(get_string('incorrect_courseAid', 'plagiarism_crot'));
    }

    require_course_login($courseA);
    if (!has_capability('mod/assignment:grade', context_module::instance($subA->cm))) {
        print_error(get_string('have_to_be_a_teacher', 'plagiarism_crot'));
    }
}
if (!$isWebB) {
    if (!$subB = $DB->get_record("plagiarism_crot_files", ["id" => $submB->crot_submission_id])) {
        print_error(get_string('incorrect_fileBid', 'plagiarism_crot'));
    }
    if (!$fileb = $DB->get_record("files", ["id" => $subB->file_id])) {
        print_error(get_string('incorrect_fileBid', 'plagiarism_crot'));
    }
    $asnBtype = $fileb->component;
//		print_r($asnBtype);

    switch ($asnBtype) {
        case "assignsubmission_file":
            require_once($CFG->dirroot . "/mod/assign/lib.php");
            $asnBtable = "assign";
            $asnBsubm = "assign_submission";
            break;
        case "mod_assignment":
            require_once($CFG->dirroot . "/mod/assignment/lib.php");
            $asnBtable = "assignment";
            $asnBsubm = "assignment_submissions";
            break;
//sw 08/27
        case "assignsubmission_onlinetext":
            require_once($CFG->dirroot . "/mod/assign/lib.php");
            $asnBtable = "assign";
            $asnBsubm = "assign_submission";
            break;
// sw 08/27 end
    }
//    		if (!$submissionB = $DB->get_record("assignment_submissions", array("id" => $fileb->itemid))) {
    if (!$submissionB = $DB->get_record($asnBsubm, ["id" => $fileb->itemid])) {
        print_error(get_string('incorrect_submBid', 'plagiarism_crot') . 'qwerty' . $fileb->itemid);
    }
//		if (! $assignB = $DB->get_record("assignment", array("id" => $submissionB->assignment))) {
    if (!$assignB = $DB->get_record($asnBtable, ["id" => $submissionB->assignment])) {
        print_error(get_string('incorrect_assignmentBid' . 'ytrewq', 'plagiarism_crot'));
    }
    if (!$courseB = $DB->get_record("course", ["id" => $subB->courseid])) {
        print_error(get_string('incorrect_courseBid', 'plagiarism_crot'));
    }

    require_course_login($courseB);
    if (!has_capability('mod/assignment:grade', context_module::instance($subB->cm))) {
        print_error(get_string('have_to_be_a_teacher', 'plagiarism_crot'));
    }
}
// end of checking permissions

// built navigation
$strmodulename = get_string("block_name", "plagiarism_crot");
$strassignment = get_string("assignments", "plagiarism_crot");

$view_url = new moodle_url('/mod/' . $asnAtable . '/view.php', ['id' => $subA->cm]);
$PAGE->navbar->add($assignA->name, $view_url);
$PAGE->navbar->add($strmodulename . " - " . $strassignment);
$PAGE->set_title($courseA->shortname . ": " . $assignA->name . ": " . $strmodulename . " - " . $strassignment);
$PAGE->set_heading($courseA->fullname);
$PAGE->set_url('/plagiarism/crot/compare.php', ['ida' => $ida, 'idb' => $idb]);
echo $OUTPUT->header();
// TODO add to log
//add_to_log($course->id, "antiplagiarism", "view all", "index.php?id=$course->id", "");

// get content of the 1st document
$textA = stripslashes($submA->content);
//$textA = ($submA->content);


// get all hashes for docA
$sql_query = "SELECT * FROM {$CFG->prefix}plagiarism_crot_fingerprint f WHERE crot_doc_id = $ida ORDER BY position asc";
$hashesA = $DB2->get_records_sql($sql_query);
// get all hashes for document B
$sql_query = "SELECT * FROM {$CFG->prefix}plagiarism_crot_fingerprint f WHERE crot_doc_id = $idb ORDER BY position asc";
$hashesB = $DB2->get_records_sql($sql_query);

// TODO create separate function for coloring ?
$sameHashA = [];

// coloring: step 1 - get same hashes
foreach ($hashesA as $hashA) {
    // look for same hash in the array  B
    foreach ($hashesB as $hashB) {
        if ($hashA->value == $hashB->value) {
            // same hash found!
            $sameHashA [] = $hashA;
            break;
        }
    }
}

// coloring: step 2 - put hashes into clusters
$clustersA = [];
$newcluster = [];
$sizeA = sizeof($sameHashA);
for ($i = 0; $i < $sizeA; $i++) {
    if ($i > 0) {
        if (($sameHashA[$i]->position - $sameHashA[$i - 1]->position) <= $distfragments) {
            // the hashes are close to each other - put hash into the cluster
            $newcluster[] = $sameHashA[$i];
        } else {    // hashes are far from each other - wrap up the  old cluster
            if (sizeof($newcluster) >= $minclustersize) {
                $clustersA[] = $newcluster;
            }
            // create a new cluster
            $newcluster = [];
            // put the orphan into the new cluster
            $newcluster[] = $sameHashA[$i];

        }
        if (($i == ($sizeA - 1)) and (sizeof($newcluster) >= $minclustersize)) {
            // last hash
            $clustersA[] = $newcluster;
        }
    } else {
        // put the first hash into the cluster
        $newcluster[] = $sameHashA[0];
    }
}

// coloring: step 3 - add colors to each cluster
$colorsA = [];
// initilize colors
$i = 0;
foreach ($clustersA as $clusterA) {
    // todo: add  more sophisticated coloring
    $colorsA[] = $allColors[0];
    $i++;
}
// loop backward to add colors
$lenA = mb_strlen($textA, "utf-8") - 1;
for ($i = sizeof($clustersA) - 1; $i >= 0; $i--) {
    $clusterA = $clustersA[$i];
    // get borders
    $startPos = $clusterA[0]->position;
    $ch = mb_substr($textA, $startPos, 1, "utf-8");
    if (!ctype_space($ch) && $startPos > 0) {
        while (!ctype_space($ch)) {
            $startPos = $startPos - 1;
            if ($startPos == 0) {
                break;
            }
            $ch = mb_substr($textA, $startPos, 1, "utf-8");
        }
    }
    $endPos = $clusterA[sizeof($clusterA) - 1]->position;
    $chr = mb_substr($textA, $endPos, 1, "utf-8");
    if (!ctype_space($chr) && $endPos < $lenA) {
        while (!ctype_space($chr)) {
            $endPos = $endPos + 1;
            if ($endPos == $lenA) {
                break;
            }
            $chr = mb_substr($textA, $endPos, 1, "utf-8");
        }
    }
    // add colors to the cluster
    $textA = colorer($textA, $startPos, $endPos, $colorsA[$i]);
}

// get the content of the second document

$textB = stripslashes($submB->content);
//$textB = ($submB->content);

// add colors to doc B
$sameHashB = [];

// coloring for doc B: step 1 - get same hashes
// this has to be done in a separate loop to make sure those hashes are ordered by position
foreach ($hashesB as $hashB) {        // look for same hash in the array  B
    foreach ($sameHashA as $hashA) {
        if ($hashA->value == $hashB->value) {
            // same hash found!
            $sameHashB [] = $hashB;
            break;
        }
    }
}

$clustersB = [];
$newcluster = [];
$sizeB = sizeof($sameHashB);
for ($i = 0; $i < $sizeB; $i++) {
    if ($i > 0) {
        if (($sameHashB[$i]->position - $sameHashB[$i - 1]->position) <= $distfragments) {
            // the hashes are close to each other - put hash into the cluster
            $newcluster[] = $sameHashB[$i];
        } else {    // hashes are far from each other - wrap up the  old cluster
            if (sizeof($newcluster) >= $minclustersize) {
                $clustersB[] = $newcluster;
            }
            // create a new cluster
            $newcluster = [];
            // put the orphan into the new cluster
            $newcluster[] = $sameHashB[$i];

        }
        if (($i == ($sizeB - 1)) and (sizeof($newcluster) >= $minclustersize)) {
            // last hash
            $clustersB[] = $newcluster;
        }
    } else {
        // put the first hash into the cluster
        $newcluster[] = $sameHashB[0];
    }
}

// coloring: step 3 - add colors to each cluster
$colorsB = [];
// initilize colors
$i = 0;
foreach ($clustersB as $clusterB) {
    $colorsB[] = $allColors[0];
    $i++;
}
// loop backward to add colors
$lenB = mb_strlen($textB, "utf-8") - 1;
for ($i = sizeof($clustersB) - 1; $i >= 0; $i--) {
    $clusterB = $clustersB[$i];
    // get borders
    $startPos = $clusterB[0]->position;
    $ch = mb_substr($textB, $startPos, 1, "utf-8");
    if (!ctype_space($ch) && $startPos > 0) {
        while (!ctype_space($ch)) {
            $startPos = $startPos - 1;
            if ($startPos == 0) {
                break;
            }
            $ch = mb_substr($textB, $startPos, 1, "utf-8");
        }
    }
    $endPos = $clusterB[sizeof($clusterB) - 1]->position;
    $chr = mb_substr($textB, $endPos, 1, "utf-8");
    if (!ctype_space($chr) && $endPos < $lenB) {
        while (!ctype_space($chr)) {
            $endPos = $endPos + 1;
            if ($endPos == $lenB) {
                break;
            }
            $chr = mb_substr($textB, $endPos, 1, "utf-8");
        }
    }
    // add colors to the cluster
    $textB = colorer($textB, $startPos, $endPos, $colorsB[$i]);
}

// create and display  2-column table to compare two documents
// get name A
if (!$isWebA) {
    if (!$studentA = $DB->get_record("files", ["id" => $subA->file_id])) {
        $strstudentA = get_string('name_unknown', 'plagiarism_crot');
    } else {
        //sw
        //sw end
        $strstudentA = $studentA->author . ":<br> " . $courseA->shortname . ", " . $assignA->name;
    }
} else {
    $wdoc = $DB->get_record("plagiarism_crot_webdoc", ["document_id" => $ida]);
    if (strlen($wdoc->link) > 40) {
        $linkname = substr($wdoc->link, 0, 40);
    } else {
        $linkname = $wdoc->link;
    }
    $strstudentA = "Web document:<br>" . "<a href=\"$wdoc->link\">$linkname</a>";
}

// get name B
if (!$isWebB) {
    if (!$studentB = $DB->get_record("files", ["id" => $subB->file_id])) {
        $strstudentB = get_string('name_unknown', 'plagiarism_crot');
    } else {
        $strstudentB = $studentB->author . ":<br> " . $courseB->shortname . ", " . $assignB->name;
    }
} else {
    $wdoc = $DB->get_record("plagiarism_crot_webdoc", ["document_id" => $idb]);
    if (strlen($wdoc->link) > 40) {
        $linkname = substr($wdoc->link, 0, 40);
    } else {
        $linkname = $wdoc->link;
    }
    $strstudentB = get_string('webdoc', 'plagiarism_crot') .
        html_writer::link($wdoc->link, $linkname, ['target'=>'_blank']);
}
?>
<STYLE>
    #example {
        text-align: left;
        position: relative;
        padding: 2px;
        height: 300px;
        overflow: scroll;
        border-width: 2px;
        border-style: outset;
        background-color: lightgrey;
    }
</STYLE>
<?php
$textA = "<div id=\"example\">" . preg_replace('/\n/', "<br>", $textA) . "</div>";
$textB = "<div id=\"example\">" . preg_replace('/\n/', "<br>", $textB) . "</div>";
$table = new html_table();
$table->head = [$strstudentA, $strstudentB];
$table->align = ["center", "center"];
$table->size = ['50%', '50%'];
$table->data[] = [$textA, $textB];
echo html_writer::table($table);
// footer
echo $OUTPUT->footer($courseA);
?>
