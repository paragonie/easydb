# Version 3.1.0

* Calling `insertReturnId()` no longer unavoidably throws on PostgreSQL. Now, it only throws if you don't specify a
  sequence name (optional third parameter).
* The README documentation now covers `insertReturnId()`
* Improved test coverage in [#162](https://github.com/paragonie/easydb/pull/162), which includes integration tests with
  Firebird, MySQL, SQLite, Microsoft SQL Server, and PostgreSQL.
* We also now test with PHP 8.5 in CI.

# Version 3.0.4

* Use prepare/statement also when there is no parameters by @jeanmonod in https://github.com/paragonie/easydb/pull/159
* Ignore tests and workflows with "export-ignore" on .gitattributes by @erikn69 in https://github.com/paragonie/easydb/pull/161
* Fix csv() when using non-default fetch style by @alffonsse in https://github.com/paragonie/easydb/pull/160 

# Version 3.0.3

* Psalm: accept EasyPlaceholder by @kamil-tekiela in [#147](https://github.com/paragonie/easydb/pull/147)
* Remove (and replace when needed) @psalm-taint-source annotations by @LeSuisse in [#149](https://github.com/paragonie/easydb/pull/149)
* Mark nullable parameters as nullable by @gijsdev in [#156](https://github.com/paragonie/easydb/pull/156)

# Version 3.0.2

* Guarantee that `EasyDB::row()` always returns an `array` instead of throwing
  a `TypeError` when encountering `null`.
  See also: [#144](https://github.com/paragonie/easydb/pull/144).

# Version 3.0.1

* [#143](https://github.com/paragonie/easydb/issues/143):
  Don't assume the array passed to insertMany() is indexed at 0.
* Fixed the type declaration of the `$duplicates_mode` parameter to be nullable in
  `buildInsertQueryBoolSafe()`.

# Version 3.0.0

* [#141](https://github.com/paragonie/easydb/pull/141):
  Increased minimum PHP Version to 8.0
  * Lots of code refactoring went into this, including strict-typing with PHP 8's
    new support for Union Types.
* [#142](https://github.com/paragonie/easydb/pull/142):
  Added support for Psalm Security Analysis

# Version 2.12.0

* Migrated from Travis CI to GitHub Actions
* [#136](https://github.com/paragonie/easydb/pull/136):
  Added `EasyPlaceholder` for calling SQL functions on insert/update queries
* [#137](https://github.com/paragonie/easydb/pull/137):
  Added `csv()` method to satisfy feature request [#100](https://github.com/paragonie/easydb/issues/100)
* Miscellaneous boyscouting

# Version 2.11.0

* [#120](https://github.com/paragonie/easydb/pull/120):
  `EasyStatement` now defaults to `WHERE 1 = 1` instead of `WHERE 1`
  to ensure success with PostgreSQL.
* [#122](https://github.com/paragonie/easydb/pull/122):
  Builds on PHP 7.4 in Travis CI, installs on PHP 8. 

# Version 2.10.0

* You can now pull the original exception (which may leak credentials via
  stack trace) from a `ConstructorFailed` exception by calling the new
  `getRealException()` method.
* Added `insertIgnore()` (Insert a row into the table, ignoring on key
  collisions)
* Added `insertOnDuplicateKeyUpdate()` (Insert a row into the table; or if
  a key collision occurs, doing an update instead)
* [#111](https://github.com/paragonie/easydb/issues/111):
  `EasyStatement`: Don't fail with empty `IN()` statements

# Version 2.9.0

* You can now side-step credential leakage in the `Factory` class
  by calling `Factory::fromArray([$dsn, $username, $password, $options])`
  instead of `Factory::create($dsn, $username, $password, $options)`.

# Version 2.8.0

* Our exceptions now integrate with [Corner](https://github.com/paragonie/corner).

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
