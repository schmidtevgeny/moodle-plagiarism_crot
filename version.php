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

$plugin->version =  2024082402;
$plugin->requires = 2012062600;
$plugin->maturity = MATURITY_BETA;
$plugin->release ='2.3.0.3';
$plugin->component = 'plagiarism_crot';
$plugin->cron     = 0;
$plugin->dependencies = array(
    'mod_assign' => ANY_VERSION,
    'mod_forum' => ANY_VERSION,
    'mod_quiz' => ANY_VERSION,
);