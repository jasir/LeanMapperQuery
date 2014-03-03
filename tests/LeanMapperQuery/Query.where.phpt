<?php

use LeanMapper\Entity;
use LeanMapper\Fluent;
use LeanMapperQuery\Query;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

class OtherDateTime extends DateTime
{}

/**
 * @property int $id
 * @property DateTime $pubdate m:type(date)
 * @property OtherDateTime $created
 * @property string $name
 * @property string|NULL $website
 * @property bool $available
 */
class Book extends Entity
{
}

function getFluent($table)
{
	global $connection;
	$fluent = new Fluent($connection);
	return $fluent->select('%n.*', $table)->from($table);
}

function getQuery()
{
	return new Query;
}

/////////// TEST 2 ARGS WHERE ////////////

// Test replacing placeholders
$datetime = new DateTime('2000-04-04');
$fluent = getFluent('book');
getQuery()
	->where('@id', 1)
	->where('@pubdate', $datetime)
	->where('@created <', $datetime)
	->where('@available =', FALSE)
	->applyQuery($fluent, $mapper);

$expected = getFluent('book')
	->where('([book].[id] = %i)', 1)
	->where('([book].[pubdate] = %d)', $datetime)
	->where('([book].[created] < %t)', $datetime)
	->where('([book].[available] = %b)', FALSE);

Assert::same($expected->_export(), $fluent->_export());