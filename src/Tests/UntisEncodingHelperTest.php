<?php

namespace App\Tests;

use App\Service\UntisEncodingHelper;
use PHPUnit\Framework\TestCase;

class UntisEncodingHelperTest extends TestCase
{
    public function testNullReturnsEmptyString(): void
    {
        $this->assertSame('', UntisEncodingHelper::toSafeUtf8(null));
    }

    public function testEmptyStringReturnsEmptyString(): void
    {
        $this->assertSame('', UntisEncodingHelper::toSafeUtf8(''));
    }

    public function testWhitespaceOnlyReturnsEmptyString(): void
    {
        $this->assertSame('', UntisEncodingHelper::toSafeUtf8('   '));
    }

    public function testUtf8StringIsUnchanged(): void
    {
        $input = 'Präsenz: 10A; HA für 10B';
        $this->assertSame($input, UntisEncodingHelper::toSafeUtf8($input));
    }

    public function testWindows1252IsConverted(): void
    {
        // \xE4 ist "ä" in Windows-1252
        $input = "Pr\xE4senz";
        $this->assertSame('Präsenz', UntisEncodingHelper::toSafeUtf8($input));
    }

    public function testMultipleUmlauteWindows1252(): void
    {
        // ä ö ü Ä Ö Ü ß in Windows-1252
        $input = "\xE4\xF6\xFC\xC4\xD6\xDC\xDF";
        $this->assertSame('äöüÄÖÜß', UntisEncodingHelper::toSafeUtf8($input));
    }

    public function testUtf8BomIsRemoved(): void
    {
        $input = "\xEF\xBB\xBF7035";
        $this->assertSame('7035', UntisEncodingHelper::toSafeUtf8($input));
    }

    public function testTrimmingIsApplied(): void
    {
        $this->assertSame('hello', UntisEncodingHelper::toSafeUtf8('  hello  '));
    }

    public function testPureAsciiIsUnchanged(): void
    {
        $this->assertSame('WPF_2.1_Bi', UntisEncodingHelper::toSafeUtf8('WPF_2.1_Bi'));
    }
}
