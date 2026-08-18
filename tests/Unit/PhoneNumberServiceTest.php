<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Tests\Unit;

use RichnessAgency\RichWhatsApp\Services\PhoneNumberService;
use RichnessAgency\RichWhatsApp\Exceptions\InvalidPhoneNumberException;
use RichnessAgency\RichWhatsApp\Tests\TestCase;

class PhoneNumberServiceTest extends TestCase
{
    protected PhoneNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PhoneNumberService();
    }

    public function test_normalizes_international_format_with_plus(): void
    {
        $this->assertEquals('201012345678', $this->service->normalize('+201012345678'));
        $this->assertEquals('201012345678', $this->service->normalize('+20 10 1234 5678'));
    }

    public function test_normalizes_international_format_with_double_zero(): void
    {
        $this->assertEquals('201012345678', $this->service->normalize('00201012345678'));
    }

    public function test_normalizes_national_format_using_default_country_code(): void
    {
        $this->assertEquals('201012345678', $this->service->normalize('01012345678', '20'));
    }

    public function test_prepends_default_country_code_to_short_plain_digits(): void
    {
        $this->assertEquals('201012345678', $this->service->normalize('1012345678', '20'));
    }

    public function test_rejects_national_format_without_country_code_configured(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        $this->service->normalize('01012345678', '');
    }

    public function test_rejects_empty_input(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        $this->service->normalize('   ');
    }

    public function test_rejects_too_short_or_too_long_numbers(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        $this->service->normalize('+123');
    }

    public function test_rejects_garbage_inputs(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        $this->service->normalize('abc');
    }
}
