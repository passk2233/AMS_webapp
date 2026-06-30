<?php
require __DIR__ . '/config.php';
require_student();

// Landing page shown after a student clicks a subject: explains the 1–10 scale,
// plays a short how-to video, then sends them on to the evaluation form.
$planId = (int) ($_GET['plan'] ?? 0);
$title  = 'ກ່ອນເລີ່ມການປະເມີນ';
require __DIR__ . '/views/guide.php';
