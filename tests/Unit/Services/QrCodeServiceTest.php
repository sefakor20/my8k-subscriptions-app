<?php

declare(strict_types=1);

use App\Services\QrCodeService;

beforeEach(function () {
    $this->service = new QrCodeService();
});

it('generates svg qr code', function () {
    $svg = $this->service->generateSvg('https://example.com/test');

    expect($svg)->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>');
});

it('generates scannable qr code for url', function () {
    $url = 'https://iptv.example.com/get.php?username=test&password=pass123&type=m3u_plus';
    $svg = $this->service->generateSvg($url);

    expect($svg)->toBeString()
        ->toContain('<svg')
        ->toContain('</svg>');
});

it('generates qr code with default size', function () {
    $svg = $this->service->generateSvg('test');

    // Default size is 200
    expect($svg)->toContain('width="200"')
        ->toContain('height="200"');
});

it('generates qr code with custom size', function () {
    $svg = $this->service->generateSvg('test', 300);

    expect($svg)->toContain('width="300"')
        ->toContain('height="300"');
});

it('handles special characters in data', function () {
    $dataWithSpecialChars = 'https://example.com/test?param=value&other=test%20space';
    $svg = $this->service->generateSvg($dataWithSpecialChars);

    expect($svg)->toBeString()
        ->toContain('<svg');
});

it('handles long urls', function () {
    $longUrl = 'https://example.com/' . str_repeat('a', 500);
    $svg = $this->service->generateSvg($longUrl);

    expect($svg)->toBeString()
        ->toContain('<svg');
});
