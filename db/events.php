<?php


defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => 'plagiarism_crot_observer::core_event_course_module_deleted'
    ),
    array(
        'eventname' => '\assignsubmission_file\event\assessable_uploaded',
        'callback'  => 'plagiarism_crot_observer::assignsubmission_file_event_assessable_uploaded'
    ),
    array(
        'eventname' => '\assignsubmission_onlinetext\event\assessable_uploaded',
        'callback'  => 'plagiarism_crot_observer::assignsubmission_onlinetext_event_assessable_uploaded'
    ),
    array(
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback'  => 'plagiarism_crot_observer::mod_assign_event_assessable_submitted'
    ),
    array(
        'eventname' => '\mod_workshop\event\assessable_uploaded',
        'callback'  => 'plagiarism_crot_observer::mod_workshop_event_assessable_uploaded'
    ),
    array(
        'eventname' => '\mod_forum\event\assessable_uploaded',
        'callback'  => 'plagiarism_crot_observer::mod_forum_event_assessable_uploaded'
    ),
    array(
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback'  => 'plagiarism_crot_observer::mod_quiz_event_attempt_submitted'
    ),
    array(
        'eventname' => '\core\event\user_deleted',
        'callback'  => 'plagiarism_crot_observer::core_event_user_deletion'
    )
);

//
//$observers = array (
//    array(
//        'eventname' => '\mod_assign\event\assessable_submitted',
//        'callback'  => 'plagiarism_crot_observer::crot_event_file_uploaded'
//    ),
//    array(
//        'eventname' => '\mod_assign\event\submission_updated',
//        'callback'  => 'plagiarism_crot_observer::crot_event_file_uploaded'
//    ),
//    array(
//        'eventname' => '\core\event\course_module_deleted',
//        'callback'  => 'plagiarism_crot_observer::crot_event_mod_deleted'
//    ),
//    array(
//        'eventname' => '\core\event\course_reset_ended',
//        'callback'  => 'plagiarism_crot_observer::crot_event_mod_deleted',
//    )
//);
/*$handlers = array (
    'assessable_file_uploaded' => array (
        'handlerfile'      => '/plagiarism/crot/lib.php',
        'handlerfunction'  => 'crot_event_file_uploaded',
        'schedule'         => 'cron'
    ),
    'assessable_files_done' => array (
        'handlerfile'      => '/plagiarism/crot/lib.php',
        'handlerfunction'  => 'crot_event_files_done',
        'schedule'         => 'cron'
    ),
    'assessable_content_uploaded' => array (
        'handlerfile'      => '/plagiarism/crot/lib.php',
        'handlerfunction'  => 'crot_event_content_uploaded',
        'schedule'         => 'cron'
    ),
    'mod_created' => array (
        'handlerfile'      => '/plagiarism/crot/lib.php',
        'handlerfunction'  => 'crot_event_mod_created',
        'schedule'         => 'cron'
    ),
    'mod_updated' => array (
        'handlerfile'      => '/plagiarism/crot/lib.php',
        'handlerfunction'  => 'crot_event_mod_updated',
        'schedule'         => 'cron'
    ),
    'mod_deleted' => array (
        'handlerfile'      => '/plagiarism/crot/lib.php',
        'handlerfunction'  => 'crot_event_mod_deleted',
        'schedule'         => 'cron'
    ),
);*/
