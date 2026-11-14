<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Test Impact Analysis
|--------------------------------------------------------------------------
|
| On local machines Pest only re-runs the tests affected by your changes and replays the
| rest from cache (requires PCOV or Xdebug). On CI, or with "--ci", the full suite runs.
| Use "--no-tia" to force a full local run, or "--tia --fresh" to re-record the graph.
| TIA needs a git repository with at least one commit, so it stays off until then.
|
*/

if (new Process(['git', 'rev-parse', '--verify', '--quiet', 'HEAD'], dirname(__DIR__))->run() === 0) {
    pest()->tia()
        ->locally()
        ->defaultBranch('main');
}
