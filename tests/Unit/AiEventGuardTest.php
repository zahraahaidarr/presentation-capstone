<?php

use App\Models\EventCategory;
use App\Services\AiEventGuard;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;


beforeEach(function () {
    // ✅ Runs after Laravel is booted for the test file
    config(['services.openai.key' => 'test-key']);
});

it('it returns parsed moderation result when openai is faked', function () {
    EventCategory::create([
        'category_id' => 1,
        'name' => 'Ashura',
        'description' => null,
    ]);

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => '{"related":true,"category_id":1,"reason":"ok"}',
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = app(AiEventGuard::class)->check('This is about Ashura');

    expect($result['related'])->toBeTrue();
    expect($result['category_id'])->toBe(1);
    expect($result['reason'])->toBe('ok');
});
