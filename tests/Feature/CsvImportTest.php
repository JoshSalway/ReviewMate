<?php

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->business = Business::factory()->onboarded()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);
});

function makeCsvFile(string $content): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'csv') . '.csv';
    file_put_contents($path, $content);
    return new UploadedFile($path, 'customers.csv', 'text/csv', null, true);
}

test('users can import customers from csv', function () {
    $csv = "name,email,phone\nJohn Smith,john@example.com,0400111222\nJane Doe,jane@example.com,0400333444";
    $file = makeCsvFile($csv);

    $this->post('/customers/import', ['file' => $file])
        ->assertRedirect();

    expect($this->business->customers()->count())->toBe(2);
    $this->assertDatabaseHas('customers', [
        'business_id' => $this->business->id,
        'name' => 'John Smith',
        'email' => 'john@example.com',
    ]);
});

test('csv import skips rows without a name', function () {
    $csv = "name,email\n,john@example.com\nJane Doe,jane@example.com";
    $file = makeCsvFile($csv);

    $this->post('/customers/import', ['file' => $file])
        ->assertRedirect();

    expect($this->business->customers()->count())->toBe(1);
});

test('csv import does not create duplicates for existing email', function () {
    Customer::factory()->create([
        'business_id' => $this->business->id,
        'name' => 'Existing',
        'email' => 'john@example.com',
    ]);

    $csv = "name,email\nJohn Smith,john@example.com";
    $file = makeCsvFile($csv);

    $this->post('/customers/import', ['file' => $file])
        ->assertRedirect();

    expect($this->business->customers()->count())->toBe(1);
});

test('csv import requires a file', function () {
    $this->post('/customers/import', [])
        ->assertSessionHasErrors('file');
});

test('csv import only accepts csv files', function () {
    $file = UploadedFile::fake()->create('data.pdf', 100, 'application/pdf');

    $this->post('/customers/import', ['file' => $file])
        ->assertSessionHasErrors('file');
});
