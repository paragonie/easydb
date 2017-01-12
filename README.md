# EasyDB - Simple Database Abstraction Layer

[![Build Status](https://travis-ci.org/paragonie/easydb.svg?branch=master)](https://travis-ci.org/paragonie/easydb)

PDO lacks brevity and simplicity; EasyDB makes separating data from instructions
easy (and aesthetically pleasing).

EasyDB was created by [Paragon Initiative Enterprises](https://paragonie.com)
as part of our effort to encourage better [application security](https://paragonie.com/service/appsec) practices.

Check out our other [open source projects](https://paragonie.com/projects) too.

## Why Use EasyDB? Because it's cleaner!

Let's refactor the following legacy insecure code snippet to prevent SQL injection.

```php
$query = mysql_query(
    "SELECT * FROM comments WHERE blogpostid = {$_GET['blogpostid']} ORDER BY created ASC"
);
while($row = mysql_fetch_assoc($query)) {
    $template_engine->render('comment', $row);
}
```

### The PDO Way

```php
$db = new \PDO(
    'mysql:host=localhost;dbname=something',
    'username',
    'putastrongpasswordhere'
);

$statement = $db->prepare('SELECT * FROM comments WHERE blogpostid = ? ORDER BY created ASC');
$exec = $statement->execute([$_GET['blogpostid']]);
$rows = $exec->fetchAll(\PDO::FETCH_ASSOC);
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
    'approved' => true
], [
    'commentid' => $_POST['comment']
]);
```

### Delete a row from a database table

```php
// Delete all of this user's comments
$db->delete('comments', [
    'userid' => 3
]);
```

### Fetch a single row from a table

```php
$userData = $db->row(
    "SELECT * FROM users WHERE userid = ?",
    $_GET['userid']
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
./phpunit.sh
```
