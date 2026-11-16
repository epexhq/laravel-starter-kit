<?php

use Livewire\Component;
use Symfony\Component\Finder\Finder;

arch('livewire components are classes in app/Livewire', function (): void {
    expect(Component::class)->toOnlyBeUsedIn('App\Livewire');
});

test('livewire components are not view-based', function (): void {
    $viewBasedComponents = [];

    foreach (Finder::create()->files()->in(__DIR__.'/../resources/views')->name('*.php') as $file) {
        if (preg_match('/new\s+class\b[^{]*\bextends\b/', $file->getContents()) === 1) {
            $viewBasedComponents[] = 'resources/views/'.$file->getRelativePathname();
        }
    }

    expect($viewBasedComponents)->toBe([], 'Create Livewire components with `php artisan make:livewire`, as classes in app/Livewire.');
});
