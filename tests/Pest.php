<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// `composer test:tia` compares against this branch, so it works in a repository without a remote.
pest()->tia()->defaultBranch('main');
