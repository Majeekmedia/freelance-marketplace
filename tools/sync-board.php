<?php

declare(strict_types=1);

/**
 * Bring the planning board back in step with what has actually been built.
 *
 * The drift this fixes: `milestones.md` still said M1 was in-progress and M2-M12
 * were todo, while M1-M3 were finished and most of M4 was done. `tasks.md` had
 * T-001..T-008 marked todo even though they shipped in earlier milestones.
 *
 * Why this matters beyond tidiness: the gates read these files. A board that
 * under-reports progress makes the next "what should I do now" answer wrong, and
 * a board that over-reports is worse. Recording real state is what keeps
 * `aidev next` honest.
 *
 * Run: php tools/sync-board.php
 */

$base = __DIR__ . '/..';

// ---------------------------------------------------------------- milestones

$milestonesPath = $base.'/milestones.md';
$milestones = file_get_contents($milestonesPath);

/*
 * Evidence-backed status. Each milestone below has at least one verification
 * file on disk under .aidev/evidence/, which is the standard the kit requires.
 */
$milestoneStatus = [
    'M1' => 'done',
    'M2' => 'done',
    'M3' => 'done',
    'M4' => 'in-progress',
    // M5 onward is genuinely untouched.
];

foreach ($milestoneStatus as $id => $status) {
    // Replace only the Status line inside that milestone's block.
    $pattern = '/(### '.preg_quote($id, '/').' —[^\n]*\n(?:.*?\n)*?- \*\*Status:\*\* )(\w[\w-]*)/';

    $replaced = preg_replace($pattern, '${1}'.$status, $milestones, 1, $count);

    if ($replaced === null) {
        fwrite(STDERR, "Failed to update {$id}\n");
        exit(1);
    }

    $milestones = $replaced;
    echo str_pad($id, 5).' -> '.$status.($count ? '' : '  (NO MATCH)')."\n";
}

file_put_contents($milestonesPath, $milestones);

// --------------------------------------------------------------------- tasks

$tasksPath = $base.'/tasks.md';
$tasks = file_get_contents($tasksPath);

/*
 * Tasks that shipped. T-001..T-008 were completed during M1 and M2; T-010 and
 * T-011b were completed during M4 but were never ticked.
 */
$doneTasks = [
    'T-001', 'T-002', 'T-003', 'T-004', 'T-005', 'T-006', 'T-007', 'T-008',
    'T-009', 'T-010', 'T-011', 'T-011b', 'T-011c', 'T-011d',
];

foreach ($doneTasks as $task) {
    $pattern = '/(### '.preg_quote($task, '/').' —[^\n]*\n(?:.*?\n)*?- \*\*Status:\*\* )(\w+)/';

    $replaced = preg_replace($pattern, '${1}done', $tasks, 1, $count);

    if ($replaced === null) {
        fwrite(STDERR, "Failed to update {$task}\n");
        exit(1);
    }

    $tasks = $replaced;
}

file_put_contents($tasksPath, $tasks);

echo "\ntasks.md updated.\n";

// ------------------------------------------------------------------- verify

$m = file_get_contents($milestonesPath);
$t = file_get_contents($tasksPath);

echo 'milestones done: '.preg_match_all('/- \*\*Status:\*\* done/', $m)."\n";
echo 'milestones in-progress: '.preg_match_all('/- \*\*Status:\*\* in-progress/', $m)."\n";
echo 'tasks done: '.preg_match_all('/- \*\*Status:\*\* done/', $t)."\n";
echo 'tasks todo: '.preg_match_all('/- \*\*Status:\*\* todo/', $t)."\n";
