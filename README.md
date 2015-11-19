NOTE: A redesigned version of the forked [PGBI/cakephp3-soft-delete](http://github.com/pgbi/cakephp3-soft-delete)

# CakeSoftDelete plugin for CakePHP

## Purpose

This Cakephp plugin enables you to make your models soft deletable.
When soft deleting an entity, it is not actually removed from your database. Instead, a `deleted` timestamp is set on the record.

## Requirements

This plugins has been developed for cakephp 3.x.

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org) vcs.

Update your composer file to include this plugin:

```json
"repositories": [{
    "type": "vcs",
    "url": "https://github.com/mojatter/cakephp3-soft-delete"
}],
"require": {
    "cakephp/cakephp": "~3.1",
    "cakephp/plugin-installer": "*",
    "mojatter/cakephp3-soft-delete": "dev-mojatter",
},
```

```
composer update
```

## Configuration

### Load the plugin:
```
// In /config/bootstrap.php
Plugin::load('SoftDelete');
```
### Make a model soft deleteable:

Use the SoftDelete trait on your model Table class:

```
// in src/Model/Table/UsersTable.php
...
use SoftDelete\Model\Table\SoftDeleteTrait;

class UsersTable extends Table
{
    use SoftDeleteTrait;
    ...
```

Your soft deletable model database table should have a field called `deleted` of type DateTime with NULL as default value.
If you want to customise this field you can declare the field in your Table class.

```php
// in src/Model/Table/UsersTable.php
...
use SoftDelete\Model\Table\SoftDeleteTrait;

class UsersTable extends Table
{
    use SoftDeleteTrait;

    protected $softDeleteConfig = [
        'values' => [
            'is_deleted' => 1,
            'deleted' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME'])
        ],
        'without' => ['is_deleted' => 0],
        'in' => ['is_deleted' => 1]
    ];
    ...
```

Other examples.

```sql
create table users (
    id int not null auto_increment,
    email varchar(255) not null,
    is_deleted int not null default 0,
    deleted datetime default null,
    constraint pk primary key (id),
    constraint uk unique key (email, is_deleted)
);

-- soft deleting using column `id`
update users set is_deleted = id, deleted = now() where id = 1;
-- selecting active users
select * from users where is_deleted = 0;
-- selecting soft deleted users
select * from users where is_deleted > 0;
```

```php
// in src/Model/Table/UsersTable.php
...
use SoftDelete\Model\Table\SoftDeleteTrait;
use Cake\Database\Expression\IdentifierExpression;

class UsersTable extends Table
{
    use SoftDeleteTrait;

    protected $softDeleteConfig = [
        'values' => [
            'is_deleted' => new IdentifierExpression('id'),
            'deleted' => IdentifierExpression('now()')
        ],
        'without' => ['is_deleted' => 0],
        'in' => ['is_deleted >' => 0]
    ];
    ...
```


## Use

### Soft deleting records

`delete` and `deleteAll` functions will now soft delete records by populating `deleted` field with the date of the deletion.

```php
// in src/Model/Table/UsersTable.php
$this->delete($user); // $user entity is now soft deleted if UsersTable uses SoftDeleteTrait.
```

### Finding records

`find`, `get` or dynamic finders (such as `findById`) will only return non soft deleted records.
To also return soft deleted records, `$options` must contain `'withDeleted'`. Example:

```php
// in src/Model/Table/UsersTable.php
$nonSoftDeletedRecords = $this->find('all');
$allRecords            = $this->find('all', ['withDeleted']);
$maleOfAllRecords      = $this->find('all', [
  'withDeleted' => true,
  'conditions' => ['gender' => 'male']
]);
```

### Hard deleting records

To hard delete a single entity:
```php
// in src/Model/Table/UsersTable.php
$user = $this->get($userId);
$success = $this->hardDelete($user);
```

To hard delete entities:

```
// in src/Model/Table/UsersTable.php
$this->hardDeleteAll([]); // all
$this->hardDeleteAll(['deleted is not' => null]); // only soft delete
```

## Soft deleting & associations

Associations are correctly handled by SoftDelete plugin.

1. Soft deletion will be cascaded to related models as usual. If related models also use SoftDelete Trait, they will be soft deleted.
2. Soft deletes records will be excluded from counter caches.
