<?php

use App\Models\Business;

test('googleReviewUrl returns write review URL when google_place_id is set', function () {
    $business = new Business(['google_place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4', 'name' => 'Test Business']);

    expect($business->googleReviewUrl())
        ->toBe('https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4');
});

test('googleReviewUrl returns Google search fallback when no google_place_id is set', function () {
    $business = new Business(['name' => 'Acme Plumbing']);

    $url = $business->googleReviewUrl();

    expect($url)
        ->not->toBe('#')
        ->toContain('https://www.google.com/search?q=')
        ->toContain(urlencode('Acme Plumbing reviews'));
});
