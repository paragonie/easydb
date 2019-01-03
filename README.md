# EasyDB - Simple Database Abstraction Layer

[![Build Status](https://travis-ci.org/paragonie/easydb.svg?branch=master)](https://travis-ci.org/paragonie/easydb)
[![Latest Stable Version](https://poser.pugx.org/paragonie/easydb/v/stable)](https://packagist.org/packages/paragonie/easydb)
[![Latest Unstable Version](https://poser.pugx.org/paragonie/easydb/v/unstable)](https://packagist.org/packages/paragonie/easydb)
[![License](https://poser.pugx.org/paragonie/easydb/license)](https://packagist.org/packages/paragonie/easydb)
[![Downloads](https://img.shields.io/packagist/dt/paragonie/easydb.svg)](https://packagist.org/packages/paragonie/easydb)

PDO lacks brevity and simplicity; EasyDB makes separating data from instructions
easy (and aesthetically pleasing).

EasyDB was created by [Paragon Initiative Enterprises](https://paragonie.com)
as part of our effort to encourage better [application security](https://paragonie.com/service/appsec) practices.

Check out our other [open source projects](https://paragonie.com/projects) too.

If you're looking for a full-fledged query builder, check out
[Latitude](https://github.com/shadowhand/latitude) and [Aura.SqlQuery](https://github.com/auraphp/Aura.SqlQuery),
which can be used with EasyDB.

If you'd like to use EasyDB but cache prepared statements in memory for
multiple queries (i.e. to reduce database round-trips), check out our
[EasyDB-Cache](https://github.com/paragonie/easydb-cache) wrapper class.

## Installing EasyDB

First, [get Composer](https://getcomposer.org/download/), if you don't already use it.

Next, run the following command:

```bash
/path/to/your/local/composer.phar require paragonie/easydb:^2
```

If you've installed Composer in `/usr/bin`, you can replace
`/path/to/your/local/composer.phar` with just `composer`.

## Why Use EasyDB? Because it's cleaner!

Let's refactor a dangerous PHP snippet that previously used string concatenation to pass user input
instead of prepared statements. For example, imagine something that just dropped `{$_GET['blogpostid']}` into the
middle of a `mysql_query()` statement. Let's make it secure.

### The PDO Way

```php
$db = new \PDO(
    'mysql:host=localhost;dbname=something',
    'username',
    'putastrongpasswordhere'
);

$statement = $db->prepare('SELECT * FROM comments WHERE blogpostid = ? ORDER BY created ASC');
$exec = $statement->execute([$_GET['blogpostid']]);
$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $template_engine->render('comment', $row);
}
```

That's a little wordy for such a simple task. If we do this in multiple places,
we end up repeating ourselves a lot.

### The EasyDB Solution

```php
$db = \ParagonIE\EasyDB\Factory::create(
    'mysql:host=localhost;dbname=something',
    'username',
    'putastrongpasswordhere'
);

$rows = $db->run('SELECT * FROM comments WHERE blogpostid = ? ORDER BY created ASC', $_GET['blogpostid']);
foreach ($rows as $row) {
    $template_engine->render('comment', $row);
}
```

We made it a one-liner.

## What else can EasyDB do quickly?

### Insert a row into a database table

```php
$db->insert('comments', [
    'blogpostid' => $_POST['blogpost'],
    'userid' => $_SESSION['user'],
    'comment' => $_POST['body'],
    'parent' => isset($_POST['replyTo']) ? $_POST['replyTo'] : null
]);
```

This is equivalent to the following SQL query (assuming `$_POST['blogpostid']`
is equal to `123`, `$_SESSION['user']` is equal to `234`, `$_POST['body']` is
equal to `test`, and `$_POST['replyTo']` is equal to `3456`):

```sql
INSERT INTO comments (blogpostid, userid, comment, parent) VALUES (
    123,
    234,
    'test',
    3456
);
```

#### Build an insert without executing

```php
$sql = $db->buildInsertQuery('comments', [
    'blogpostid',
    'userid',
    'comment'
]);

// INSERT INTO comments (blogpostid, userid, comment) VALUES (?, ?, ?)

$result = $db->q(
    $sql,
    $values,
    \PDO::FETCH_BOTH,
    true
);
```

### Update a row from a database table

```php
$db->update('comments', [
    'column' => 'foo',
    'otherColumn' => 123456,
    'approved' => true
], [
    'commentid' => $_POST['comment']
]);
```

This is equivalent to the following SQL query
(assuming `$_POST['comment']` is equal to `789`):

```sql
UPDATE comments
SET 
  column = 'foo',
  otherColumn = 123456,
  approved = TRUE
WHERE commentid = 789
```

### Delete a row from a database table

```php
// Delete all of this user's comments
$db->delete('comments', [
    'userid' => 3
]);
```

This is equivalent to the following SQL query:

```sql
DELETE FROM comments WHERE userid = 3
```

### Fetch a single row from a table

```php
$userData = $db->row(
    "SELECT * FROM users WHERE userid = ?",
    $_GET['userid']
);
```

Note: This expects a variadic list of arguments, not an array. If you have
multiple parameters, stack them like this:

```php
$userData = $db->row(
    "SELECT * FROM users WHERE userid = ? AND other = ?",
    $_GET['userid'],
    $_GET['other']
);
```

This is **wrong**:

```php
$userData = $db->row(
    "SELECT * FROM users WHERE userid = ? AND other = ?",
    array($userid, $other) // WRONG, should not be in an array
);
```

### Fetch a single column from a single row from a table

```php
$exists = $db->cell(
    "SELECT count(id) FROM users WHERE email = ?",
    $_POST['email']
);

/* OR YOU CAN CALL IT THIS WAY: */
$exists = $db->single(
    "SELECT count(id) FROM users WHERE email = ?",
    array(
        $_POST['email']
    )
);
```

Note: `cell()` expects a variadic list of arguments, not an array. If you have
multiple parameters, stack them like this:

```php
$exists = $db->cell(
    "SELECT count(id) FROM users WHERE email = ? AND username = ?",
    $_POST['email'],
    $_POST['usenrame']
);
```

This is **wrong**:

```php
$exists = $db->cell(
    "SELECT count(id) FROM users WHERE email = ? AND username = ?",
    array($email, $username) // WRONG, should not be in an array
);
```

Alternatively, you can use `single()` instead of `cell()` if you really 
want to pass an array.

### Try to perform a transaction
```php
$save = function (EasyDB $db) use ($userData, $query) : int {
    $db->safeQuery($query, [$userData['userId']]);
    return \Some\Other\Package::CleanUpTable($db);
};
// auto starts, commits and rolls back a transaction as necessary
$returnedInt = $db->tryFlatTransaction($save);
```

### Generate dynamic query conditions

```php
$statement = EasyStatement::open()
    ->with('last_login IS NOT NULL');

if (strpos($_POST['search'], '@') !== false) {
    // Perform a username search
    $statement->orWith('username LIKE ?', '%' . $db->escapeLikeValue($_POST['search']) . '%');
} else {
    // Perform an email search
    $statement->orWith('email = ?', $_POST['search']);
}

// The statement can compile itself to a string with placeholders:
echo $statement; /* last_login IS NOT NULL OR username LIKE ? */

// All the values passed to the statement are captured and can be used for querying:
$user = $db->single("SELECT * FROM users WHERE $statement", $statement->values());
```

_**Note**: Passing values with conditions is entirely optional but recommended._

#### Variable number of "IN" arguments

```php
// Statements also handle translation for IN conditions with variable arguments,
// using a special ?* placeholder:
$roles = [1];
if ($_GET['with_managers']) {
    $roles[] = 2;
}

$statement = EasyStatement::open()->in('role IN (?*)', $roles);

// The ?* placeholder is replaced by the correct number of ? placeholders:
echo $statement; /* role IN (?, ?) */

// And the values will be unpacked accordingly:
print_r($statement->values()); /* [1, 2] */
```

#### Grouping of conditions

```php
// Statements can also be grouped when necessary:
$statement = EasyStatement::open()
    ->group()
        ->with('subtotal > ?')
        ->andWith('taxes > ?')
    ->end()
    ->orGroup()
        ->with('cost > ?')
        ->andWith('cancelled = 1')
    ->end();

echo $statement; /* (subtotal > ? AND taxes > ?) OR (cost > ? AND cancelled = 1) */
```

## What if I need PDO for something specific?

```php
$pdo = $db->getPdo();
```

## Can I create an EasyDB wrapper for an existing PDO instance?

**Yes!** It's as simple as doing this:

```php
$easy = new \ParagonIE\EasyDB\EasyDB($pdo, 'mysql');
```

## How do I run tests ?

```sh
vendor/bin/phpunit
```

## Troubleshooting Common Issues

### Only one-dimensional arrays are allowed

This comes up a lot when trying to pass an array of parameters to `run()`.

`EasyDB::run()` expects a query string, then any number of optional parameters.
It does **NOT** expect an array of all the parameters.

If you want to use an API that looks like `$obj->method($string, $array)`,
use `safeQuery()` instead of `run()`.

```diff
<?php
/**
 * @var EasyDB $db
 * @var string $query
 * @var array $params 
 */
- $rows = $db->run($query, $params);
+ $rows = $db->safeQuery($query, $params);
```

Alternatively, you can flatten your array with the [splat operator](https://secure.php.net/manual/en/migration56.new-features.php#migration56.new-features.splat):

```diff
<?php
/**
 * @var EasyDB $db
 * @var string $query
 * @var array $params 
 */
- $rows = $db->run($query, $params);
+ $rows = $db->run($query, ...$params);
```

EasyDB's `run()` method is a variadic wrapper for `safeQuery()`, so either
solution is correct.

## Support Contracts

If your company uses this library in their products or services, you may be
interested in [purchasing a support contract from Paragon Initiative Enterprises](https://paragonie.com/enterprise).
