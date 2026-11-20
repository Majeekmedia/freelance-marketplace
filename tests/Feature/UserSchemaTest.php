<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Schema shape (T-003)
|--------------------------------------------------------------------------
| These assert the columns exist WITH the right defaults, because the defaults
| are the security control: a user created without an explicit type/status must
| land on freelancer/pending, never admin/approved.
*/

it('has the platform identity columns', function (): void {
    expect(Schema::hasColumn('users', 'type'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'status'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'deleted_at'))->toBeTrue();
});

it('defaults a bare insert to freelancer and pending', function (): void {
    // Insert directly, bypassing the model, to prove the DATABASE default is
    // safe rather than relying on application code.
    \DB::table('users')->insert([
        'name' => 'Bare Insert',
        'email' => 'bare@example.test',
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $row = \DB::table('users')->where('email', 'bare@example.test')->first();

    expect($row->type)->toBe('freelancer')
        ->and($row->status)->toBe('pending');
});

it('stores type and status as short strings, portable across engines', function (): void {
    // A native ENUM would need a table rebuild to add a status on MySQL and is
    // awkward on SQLite. Short strings keep the schema portable between the
    // local SQLite database and the MySQL/MariaDB host.
    $columns = Schema::getColumns('users');

    $byName = collect($columns)->keyBy('name');

    expect($byName['type']['type_name'])->toContain('varchar')
        ->and($byName['status']['type_name'])->toContain('varchar');
});

it('indexes status for the admin approval queue', function (): void {
    $indexes = collect(Schema::getIndexes('users'))->pluck('columns')->flatten()->all();

    expect($indexes)->toContain('status');
});
