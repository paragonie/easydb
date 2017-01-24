<?php
declare(strict_types=1);

namespace ParagonIE\EasyDB;

use ParagonIE\EasyDB\Exception\IdentifierException;

const UNQUALIFIED_IDENTIFIER_REGEX = '/^[a-zA-Z](?:[a-zA-Z0-9_]+)?$/';
const QUALIFIED_IDENTIFIER_REGEX = '/^[a-zA-Z](?:[a-zA-Z0-9_]+)?\.[a-zA-Z](?:[a-zA-Z0-9_]+)?$/';
const QUALIFIED_IDENTIFIER_CAPTURE_REGEX = '/([a-zA-Z](?:[a-zA-Z0-9_]+)?\.[a-zA-Z](?:[a-zA-Z0-9_]+)?)/';

/**
 * Escape qualified identifiers in SQL expressions.
 */
function escape_expression(string $expression, string $engine = null): string
{
    if (\strpos($expression, '.') === false) {
        return $expression;
    }

    preg_match_all(QUALIFIED_IDENTIFIER_CAPTURE_REGEX, $expression, $matches, \PREG_SET_ORDER);
    foreach ($matches as $match) {
        $escaped = escape_identifier($match[1], $engine);
        $expression = \str_replace($match[0], $escaped, $expression);
    }

    return $expression;
}

/**
 * Escape identifiers that may be qualified.
 */
function escape_identifier(string $identifier, string $engine = null): string
{
    // table
    if (\strpos($identifier, '.') === false) {
        return escape_identifier_unqualified($identifier, $engine);
    }

    // table.column
    $parts = \explode('.', $identifier);
    if (\count($parts) > 2) {
        throw IdentifierException::tooManyParts($identifier);
    }

    $escape = static function (string $identifier) use ($engine): string {
        return escape_identifier_unqualified($identifier, $engine);
    };

    return \implode('.', \array_map($escape, $parts));
}

/**
 * Escape identifiers that are unqualified.
 *
 * Delimiters will be added depending on the engine type.
 */
function escape_identifier_unqualified(string $identifier, string $engine = null): string
{
    if ($identifier === '*') {
        return $identifier;
    }

    assert_valid_identifier($identifier, false);

    if ($engine === 'mysql') {
        return "`$identifier`";
    }

    if ($engine === 'mssql') {
        return "[$identifier]";
    }

    // Postgres, SQLite, etc
    return "\"$identifier\"";
}

/**
 * Check if an identifier is valid.
 */
function is_valid_identifier(string $identifier, bool $allow_qualified = false): bool
{
    if ($allow_qualified && \preg_match(QUALIFIED_IDENTIFIER_REGEX, $identifier)) {
        return true;
    }

    return \preg_match(UNQUALIFIED_IDENTIFIER_REGEX, $identifier) > 0;
}

/**
 * Assert that an identifier is valid.
 *
 * @throws IdentifierException
 *  If the identifier is invalid.
 */
function assert_valid_identifier(string $identifier, bool $allow_qualified = false)
{
    if (!is_valid_identifier($identifier, $allow_qualified)) {
        throw IdentifierException::invalidIdentifier($identifier);
    }
}
