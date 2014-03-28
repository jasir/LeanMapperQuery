Lean Mapper Query
=================

Lean Mapper Query is a concept of a *query object* for [Lean Mapper library](https://github.com/Tharos/LeanMapper) which helps to build complex queries using automatic joins (*idea taken from [NotORM library](http://www.notorm.com/)*). Look at the [suggested base classes](https://gist.github.com/mbohuslavek/9410266).

Features
--------

- it behaves as an `SQL` preprocessor, hence most SQL expressions are available
- automatic joins using *dot notation* (`@book.tags.name`)
- ability to query both repositories or entities
- support for implicit filters


Installation
------------

It can be installed via [Composer](http://getcomposer.org/download).

```
composer require mbohuslavek/leanmapper-query:@dev
```


What does it do?
----------------

Suppose we have following repositories:

```php
class BaseRepository extends LeanMapper\Repository
{
	public function find(IQuery $query)
	{
		$this->createEntities($query
			->applyQuery($this->createFluent(), $this->mapper)
			->fetchAll()
		);
	}
}

class BookRepository extends BaseRepository
{
}
```

and following entities:

```php
/**
 * @property int $id
 * @property string $name
 */
class Tag extends LeanMapper\Entity
{
}

/**
 * @property int $id
 * @property Author $author m:hasOne
 * @property Tag[] $tags m:hasMany
 * @property DateTime $pubdate
 * @property string $name
 * @property bool $available
 */
class Book extends LeanMapper\Entity
{
}

/**
 * @property int $id
 * @property string $name
 * @property Book[] $books m:belongsToMany
 */
class Author extends LeanMapper\Entity
{
}
```

We build a *query*:

```php
$query = new LeanMapperQuery\Query;
$query->where('@author.name', 'Karel');
```

Now, if we want to get all books whose author's name is Karel, we have to do this:

```php
$bookRepository = new BookRepository(...);
$books = $bookRepository->find($query);
```

Database query will look like this:
```sql
SELECT [book].*
FROM [book]
LEFT JOIN [author] ON [book].[author_id] = [author].[id]
WHERE ([author].[name] = 'Karel')
```

You can see it performs automatic joins via *dot notation*. It supports all types of relationships which are known to **Lean Mapper**.

It is very easy to use SQL functions. We can update query like this:
```php
$query->where('DATE(@pubdate) > %d', '1998-01-01');
$books = $bookRepository->find($query);
```

and change the database query into following:
```sql
SELECT [book].*
FROM [book]
LEFT JOIN [author] ON [book].[author_id] = [author].[id]
WHERE ([author].[name] = 'Karel') AND (DATE([book].[pubdate]) > '1998-01-01')
```

Don't repeat yourself
---------------------

You can extend `Query` and define own methods.

```php
class BookQuery extends LeanMapperQuery\Query
{
	public function restrictAvailable()
	{
		$this->where('@available', TRUE)
			->orderBy('@author.name');
		return $this;
	}
}

/////////

$query = new BookQuery;
$query->restrictAvailable();
$books = $this->bookRepository->find($query);
```

Querying entities
-----------------

It is also possible to query an entity property (*currently only those properties with `BelongsToMany` or `HasMany` relationships*). Let's build `BaseEntity`:

```php
class BaseEntity extends LeanMapperQuery\Entity
{
	protected static $magicMethodsPrefixes = array('find');

	protected function find($field, IQuery $query)
	{
		$entities = $this->queryProperty($field, $query);
		return $this->entityFactory->createCollection($entities);
	}
}

/*
 * ...
 */
class Book extends BaseEntity
{
}
```

*Note that `BaseEntity` extends `LeanMapperQuery\Entity` to make the following possible.*

We have defined the `find` method as `protected` because with specifying the method name in `$magicMethodsPrefixes` property you can query entities like this:

```php
$book; // previously fetched instance of an entity from a repository
$query = new LeanMapper\Query;
$query->where('@name !=', 'ebook');
$tags = $book->findTags($query);
```

*The magic method `findTags` will eventually call your protected method `find` with 'tags' as the 1 argument.*

The resulting database query looks like this:

```sql
SELECT [tag].*
FROM [tag]
WHERE [tag].[id] IN (1, 2) AND ([tag].[name] != 'ebook')
```

The first condition in where clause `[tag].[id] IN (1, 2)` is taken from the entity traversing (*tags are queried against this particular book entity's own tags*).


What else you can do?
---------------------

If we slightly modify our `BaseRepository` and `BaseEntity` we can simplify working with query objects. *To achieve this look at the [suggested base classes](https://gist.github.com/mbohuslavek/9410266)*. It makes the following possible.

```php
$books = $bookRepository->query()
	->where('@author.name', 'Karel')
	->where('DATE(@pubdate) > ?', '1998-01-01')
	->find();

// or...

$tags = $book->queryTags()
	->where('@name !=', 'ebook')
	->find();
```


License
-------

Copyright (c) 2013 Michal Bohuslávek

Licensed under the MIT license.
