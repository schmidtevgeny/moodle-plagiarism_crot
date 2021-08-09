<?php
require_once(dirname(__FILE__, 2) . '/../config.php');
// require_once($CFG->dirroot . '/my/lib.php');
// require_once($CFG->dirroot . '/tag/lib.php');
// require_once($CFG->dirroot . '/user/profile/lib.php');
// require_once($CFG->libdir . '/filelib.php');

$courseid = required_param('id', PARAM_INT);
$PAGE->set_url(new moodle_url('/plagiarism/crot/menu.php', ['id' => $courseid]));
require_login();

$course = get_course($courseid);
$PAGE->set_course($course);
$context = context_course::instance($courseid); //context_system::instance();

require_capability('mod/assignment:grade', $context);


$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');

$PAGE->set_title(get_string('block_name', 'plagiarism_crot', $course));
$PAGE->set_heading(get_string('block_name', 'plagiarism_crot', $course));


echo $OUTPUT->header();

$cm_info = get_fast_modinfo($courseid);
$table = new html_table();
$table->data=[];

foreach ($cm_info->get_cms() as $cm) {
    if ($cm->modname == 'assign')
    {
        $row = [];
        $row[]= $cm->id;
        $row[]=$cm->name;

        $plagiarismvalues = $DB->get_records_menu('plagiarism_crot_config', ['cm' => $cm->id], '', 'name,value');
        if (empty($plagiarismvalues['enabled'])||$plagiarismvalues['enabled']==0) {
            $row[]='OFF';
        }else{
            $row[]='ON';
        }




        $table->data[]=$row;
    }
}

echo html_writer::table($table);

echo $OUTPUT->footer();
