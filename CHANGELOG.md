# Version 2.7.0

* Changed the behavior of several public APIs to invoke
  `$this->prepare()` instead of `$this->pdo->prepare()`.
  This might seem subtle, but in actuality, it allows classes
  that extend `EasyDB` to implement prepared statement caching.

# Version 2.6.2

* Fix errors when inserting booleans.

# Version 2.6.1

* [#77](https://github.com/paragonie/easydb/pull/77): Detect when the
  driver is missing and throw a distinct error message to aid in debugging.

# Version 2.6.0

* [#69](https://github.com/paragonie/easydb/pull/69): Fixed an error when using EasyDB
  with SQLite.
* [#70](https://github.com/paragonie/easydb/issues/70): You can now use `EasyStatement`
  objects for the conditions instead of arrays in `EasyDB::update()` and `EasyDB::delete()`.
  (Arrays are still supported!)

# Version 2.5.1

* Fixed boolean handling for SQLite databases.

# Version 2.5.0

* [#56](https://github.com/paragonie/easydb/pull/56): `EasyDB::q()` and `EasyDB::row()` no
  longer explicitly force `PDO::FETCH_ASSOC`. Thanks [@nfreader](https://github.com/nfreader).
* [#57](https://github.com/paragonie/easydb/issues/57): Added `EasyDB::insertReturnId()`
  which wraps `insert()` and `lastInsertId()`. **Important:** Do not use this on PostgreSQL,
  as it is not reliable. Use `insertGet()` instead, as you normally would have.
  Reported by [@duskwuff](https://github.com/duskwuff).
* [#58](https://github.com/paragonie/easydb/issues/58): Empty `EasyStatement` clauses
  no longer cause broken queries. Reported by [@duskwuff](https://github.com/duskwuff).
* [#59](https://github.com/paragonie/easydb/issues/59): Fixed grouping/precedence issues
  with `EasyStatement` subqueries. Reported by [@duskwuff](https://github.com/duskwuff).

# Version 2.4.0

* Thanks to [@SignpostMarv](https://github.com/SignpostMarv), you can now easily run
  an entire block of code in a flat transaction:  
  `$easyDb->tryFlatTransaction(function (EasyDB $db) { /* ... */ });`
* EasyDB is now fully type-safe. This is verified by [Psalm](https://github.com/vimeo/psalm).
  If you're using a static analysis tool on your project that uses EasyDB, this should
  eliminate a lot of false positive findings.
* We now allow the `/` character to be used in SQLite identifiers.

# Version 2.3.1

* Fix SQLite setting UTF-8 mode.

# Version 2.3.0

* Added `EasyDB::buildInsertQuery` for building `INSERT` statements without executing.
* Fixed escaping of backslashes in `LIKE` statements.

# Version 2.2.1

* Adopt strict PSR-2 code style and add `phpcs` check.

# Version 2.2.0

* Added `EasyDB::escapeLikeValue()` for escaping wildcards in `LIKE` condition values.

# Version 2.1.1

* Fix PHP version requirement to work with HHVM.

# Version 2.1.0

* Import `EasyStatement` from 1.x version.

# Version 2.0.1 - 2016-10-18

* Fixed a segfault caused by attempting to clone PDO objects.

# Version 2.0.0 - 2016-10-18

The lion's share of the version 2.0.0 release was contributed by
[@SignpostMarv](https://github.com/SignpostMarv).

* Unit testing (with >80% test coverage)
* PHP 7 support
* Added an optional argument to `safeQuery()`, which allows INSERT/UPDATE
  queries to return success/failure instead of an empty array.
* Added optional support for separators in `escapeIdentifier()`.

# Version 1.1.0

* Add `EasySatement` condition builder, thanks [@shadowhand](https://github.com/shadowhand)

# Version 1.0.0 - 2016-04-22

* Version 1.0.0 EasyDB official release.
* Supports PHP 5.

# Version 0.2.4

* Fix more issues with constructor names.

# Version 0.2.3

* Fix exception namespaces

# Version 0.2.2

* Get rid of composer version directive. Use github instead.

# Version 0.2.1

* Do not emulate prepared statements.

# Version 0.2.0

* Optimized `EasyDB::column()` thanks [@Xeoncross](https://github.com/Xeoncross)
* Added `EasyDB::insertMany()`, so it's possible to insert many rows at once using
  the same prepared statement.

# Version 0.1.0

Initial Release
