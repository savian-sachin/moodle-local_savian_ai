<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => \local_savian_ai\hook_callbacks\before_standard_head_html::class . '::callback',
        'priority' => 500,
    ],
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => \local_savian_ai\hook_callbacks\before_footer_html::class . '::callback',
        'priority' => 500,
    ],
];
