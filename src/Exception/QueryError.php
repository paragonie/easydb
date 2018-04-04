<?php
declare(strict_types=1);
/**
 * Paragon Initiative Enterprises.
 *
 * @author  Scott Arciszewski   <scott@paragonie.com>.
 * @author  EasyDB Contributors <https://github.com/paragonie/easydb/graphs/contributors>
 *
 * @link    <https://github.com/paragonie/easydb> Github Repository.
 * @license <https://github.com/paragonie/easydb/blob/master/LICENSE> MIT License.
 *
 * @package ParagonIE\EasyDB
 */

namespace ParagonIE\EasyDB\Exception;

use RuntimeException;

/**
 * QueryError.
 */
class QueryError extends RuntimeException implements ExceptionInterface
{
}
