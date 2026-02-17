<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NormalizeUkWhatsAppPhoneTest extends TestCase
{
    #[DataProvider('validUkNumbersProvider')]
    public function test_normalizes_valid_uk_formats_to_e164(string $input, string $expected): void
    {
        $this->assertEquals($expected, normalizeUkWhatsAppPhone($input));
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function validUkNumbersProvider(): array
    {
        return [
            ['07 123 456 789', '+447123456789'],
            ['07123456789', '+447123456789'],
            ['+447123456789', '+447123456789'],
            ['+44 7123 456789', '+447123456789'],
            ['+4407123456789', '+447123456789'],
            ['447123456789', '+447123456789'],
            ['00447123456789', '+447123456789'],
            ['07700900123', '+447700900123'],
        ];
    }

    #[DataProvider('invalidNumbersProvider')]
    public function test_returns_null_for_invalid_input(mixed $input): void
    {
        $this->assertNull(normalizeUkWhatsAppPhone($input));
    }

    /**
     * @return array<int, array{0: mixed}>
     */
    public static function invalidNumbersProvider(): array
    {
        return [
            [null],
            [''],
            [' '],
            ['+251912345678'],
            ['123'],
            ['0712345678'],
            ['08123456789'],
            ['06123456789'],
        ];
    }
}
