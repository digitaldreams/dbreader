For some kind of automation tools we need to know database constraints like tables, fields and its indexes. But its very hard and painful to get this information from directly database. This tool will make this task super simple.

## Settings
Set Database information on top of page. 
```php
\DbReader\Database::$database = "YOUR_DATABASE_NAME";
\DbReader\Database::$username = "YOUR_DATABASE_USERNAME";
\DbReader\Database::$password = "YOUR_DATABSE_PASSWORD";
\DbReader\Database::$port = "YOUR_DATABSE_PORT"; // Default to 3306
\DbReader\Database::$host = "YOUR_DATABSE_PORT"; // Default to 127.0.0.1
\DbReader\Database::$connection = "YOUR_DATABSE_ENGINE"; // Default to mysql
// or you can just assign a pdo object via 
\DbReader\Database::$pdo= $your_pdo_object;
```
### Database
```php
$db=new \DbReader\Database();
print_r($db->tables()); // return array of tables
// You can also access a individual table object
print_r($db->users); // It will return \DbReader\Table Object
// Even further
print_r($db->users->id) // It will return \DbReader\Column Object
```
### Table
```php
$user=new \DbReader\Table('users');
print_r($user->columns()) // return all columns as array of StdClass
print_r($user->columnClasses()) // return list of Column Class object as array. Most preferable rather than columns()
print_r($user->relations()); // return all the Foreign Relation of a given table. 
print_r($user->indexes()); // return all the Indexes of given table. 
```
### Column
```php
$user=new \DbReader\Table('users');
echo $user->email->name(); // name of the column
echo $user->email->type(); // type Column data type enum, int, text etc
echo $user->email->length(); //  return length e.g. 255 for varchar
echo $user->email->defaultValue(); 
echo $user->email->isPk();
echo $user->email->isUnique();
echo $user->email->isNull();
echo $user->email->isForeign();
```

*N.B*:
This is a submodule of [LaraCrud](https://github.com/digitaldreams/laracrud) and
only Support Mysql Database now.
