<?php

use App\Services\DefaultTemplateService;

test('DefaultTemplateService forBusinessType tradie returns follow_up type not followup', function () {
    $templates = DefaultTemplateService::forBusinessType('tradie');
    $types = array_column($templates, 'type');

    expect($types)->toContain('follow_up')
        ->not->toContain('followup');
});

test('DefaultTemplateService forBusinessType tradie returns all expected types', function () {
    $templates = DefaultTemplateService::forBusinessType('tradie');
    $types = array_column($templates, 'type');

    expect($types)->toContain('request')
        ->toContain('follow_up')
        ->toContain('sms');
});

test('DefaultTemplateService forBusinessType cafe returns follow_up type not followup', function () {
    $templates = DefaultTemplateService::forBusinessType('cafe');
    $types = array_column($templates, 'type');

    expect($types)->toContain('follow_up')
        ->not->toContain('followup');
});

test('DefaultTemplateService forBusinessType salon returns follow_up type not followup', function () {
    $templates = DefaultTemplateService::forBusinessType('salon');
    $types = array_column($templates, 'type');

    expect($types)->toContain('follow_up')
        ->not->toContain('followup');
});

test('DefaultTemplateService forBusinessType unknown type falls back to default with follow_up', function () {
    $templates = DefaultTemplateService::forBusinessType('unknown_type');
    $types = array_column($templates, 'type');

    expect($types)->toContain('request')
        ->toContain('follow_up')
        ->toContain('sms')
        ->not->toContain('followup');
});
