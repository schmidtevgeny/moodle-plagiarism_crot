<?php
/**
 * Это самописная фигня для включения/выключения крота из меню управления курсом. *
 * Добавляется через function plagiarism_crot_extend_navigation_course($navigation, $course, $context)
 * Вроде нужна правка
 */
// TODO: function plagiarism_crot_extend_navigation_course($navigation, $course, $context)
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

$cm_info = get_fast_modinfo($courseid);
$table = new html_table();
$table->data = [];
$enable = optional_param('enable', 0, PARAM_INT);
$disable = optional_param('disable', 0, PARAM_INT);
if ($enable or $disable) {
    $redirect = new moodle_url('/plagiarism/crot/menu.php', ['id' => $courseid]);
    $confirm = optional_param('confirm', '', PARAM_ALPHANUM);
    $confirmcode = md5($USER->id . 'crot' . $enable . 'and' . $disable);
    if ($confirm === $confirmcode) {
        require_once($CFG->dirroot . '/plagiarism/crot/lib.php');
        // process
        if ($enable) {
            $data = new stdClass();
            $data->coursemodule = $enable;
            $data->enabled = '1';
            $data->crot_local = '1';
            $data->crot_global = '0';
            plagiarism_crot_coursemodule_edit_post_actions($data, $course);
        } else if ($disable) {
            $data = new stdClass();
            $data->coursemodule = $disable;
            $data->enabled = '0';
            $data->crot_local = '0';
            $data->crot_global = '0';
            plagiarism_crot_coursemodule_edit_post_actions($data, $course);
        }
    } else {
        if ($enable) {
            $message = get_string('enable_for', 'plagiarism_crot',
                $cm_info->get_course()->fullname . ' / ' . $cm_info->get_cms()[$enable]->name.' ('.$enable.')');
        } else if ($disable) {
            $message = get_string('disable_for', 'plagiarism_crot',
                $cm_info->get_course()->fullname . ' / ' . $cm_info->get_cms()[$disable]->name.' ('.$disable.')');
        }

        $continueurl = new moodle_url('/plagiarism/crot/menu.php', [
            'id' => $courseid,
            'enable' => $enable,
            'disable' => $disable,
            'confirm' => $confirmcode,
        ]);

        echo $OUTPUT->header();
        echo $OUTPUT->confirm($message, $continueurl, $redirect);
        echo $OUTPUT->footer();
        die;
    }
}

echo $OUTPUT->header();
foreach ($cm_info->get_cms() as $cm) {
    if ($cm->modname == 'assign') {
        $row = [];
        $row[] = $cm->id;
        $row[] = html_writer::link(
            new moodle_url('/mod/assign/view.php', ['id' => $cm->id]),
            $cm->name
        );

        $plagiarismvalues = $DB->get_records_menu('plagiarism_crot_config', ['cm' => $cm->id], '', 'name,value');
        if (empty($plagiarismvalues['enabled']) || $plagiarismvalues['enabled'] == 0) {
            $row[] = html_writer::link(new moodle_url('/plagiarism/crot/menu.php', ['id' => $courseid, 'enable' => $cm->id]),
                get_string('disabled', 'plagiarism_crot'),
                ['title' => get_string('enable', 'plagiarism_crot')]
            );
        } else {
            $row[] = html_writer::link(new moodle_url('/plagiarism/crot/menu.php', ['id' => $courseid, 'disable' => $cm->id]),
                get_string('enabled', 'plagiarism_crot'),
                ['title' => get_string('disable', 'plagiarism_crot')]
            );
        }

        $table->data[] = $row;
    }
}

echo html_writer::table($table);

echo $OUTPUT->footer();
