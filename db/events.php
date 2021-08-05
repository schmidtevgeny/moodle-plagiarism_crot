<?php


$observers = array (
    array(
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback'  => 'plagiarism_crot_observer::crot_event_file_uploaded'
    ),
    array(
        'eventname' => '\core\event\course_module_deleted',
        'callback'  => 'plagiarism_crot_observer::crot_event_mod_deleted'
    ),
    array(
        'eventname' => '\core\event\course_reset_ended',
        'callback'  => 'plagiarism_crot_observer::crot_event_mod_deleted',
    )
);
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
