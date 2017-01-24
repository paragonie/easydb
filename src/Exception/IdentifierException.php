<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB\Exception;

class IdentifierException extends \InvalidArgumentException
{
    const INVALID_IDENTIFIER = 1;
    const TOO_MANY_PARTS = 2;

    public static function invalidIdentifier(string $identifier): IdentifierException
    {
        return new static(
            "Invalid SQL identifier: $identifier",
            self::INVALID_IDENTIFIER
        );
    }

    public static function tooManyParts(string $identifier): IdentifierException
    {
        return new static(
            "Too many parts in SQL identifier: $identifier",
            self::TOO_MANY_PARTS
        );
    }
}
