Doctrine Datatables library
===========================

Doctrine Datatables library provides a Doctrine2 server side processing for [Datatables](http://datatables.net/) Version 1.10.x.

This library was created because existing libraries lack of flexibility around field types and field filtering.
This library does not provide any JavaScript code generation nor datatables.js sources, you need to install and run datatables.js yourself.

Installation
------------

You can install this library using composer

```
composer require lexx911/doctrine-datatables
```

or add the package name to your composer.json

```js
"require": {
    ...
    "lexx911/doctrine-datatables": "dev-master"
}
```

Features
--------
 * support of doctrine query builder
 * support of column search with custom column definitions (ex. number, date, composed fields)

It does not support global search (yet)

Usage
-----
```php
$builder = new TableBuilder($entityManager, $_GET);
$builder
    ->from('Foo\Bar\Entity\Sample', 's')
    ->leftJoin('s.oneToManyField', 'x')
    ->add('name')              // field will be a text field filtered with LIKE "%value%"
    ->add('price', 'number')   // field will be a number field which can be filtered by value range
    ->add('refs', 'text', 'x', 'x.otherField') // filter by field of a one-to-many relation
    ;

$response = $builder->getTable()
    ->getResponseArray('entity') // hydrate entity, defaults to array
    ;

// now you can simply return a response
// header ('Content-Type', 'application/json');
// echo json_encode($response);
```

Composed fields example:

```php
$builder
    ->from('Foo\Bar\Entity\Sample', 's')
    ->join('s.user', 'u')
    ->add('name')                          // select and filter by a name field
    ->add('username', 'text', 'u.firstName, u.lastName', 'u.id') // select firstName and lastName but filter by an id field
    ->add('modifiedOn', 'date')
    ;
```

Custom query builder example:
```php
$responseArray = $builder
    ->setQueryBuilder($customQueryBuilder)
    ->add('foo', 'text', 's.foo', 's.bar') // select foo field but filter by a bar field
    ->getTable()
    ->getResponseArray();
```

Available field types
---------------------

 * text
 * index (like text but filter with LIKE "value%". Database indexs can be used this way)
 * number
 * date
 * boolean
 * choice

Twig field rendering
--------------------
Default renderer is PhpRenderer, this can be changed by passing another renderer as 4th argument to the TableBuilder:
```php
new TableBuilder($entityManager, $_GET, null, new TwigRenderer($twigEnvironment));
```

To set field template pass template option:
```php
$builder
    ->add('date', 's.createdAt', null, array(
        'template' => 'path/to/template.html.twig'
    ))
```

In template.html.twig
```twig
{{ value | date }}
```

Warning
-------

This library is still in development, API is most likely to change.

License
-------

Doctrine Datatables is licensed under the MIT license.
