<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Context;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Torr\SimpleNormalizer\Fixture\ExampleBackedEnum;
use Tests\Torr\SimpleNormalizer\Fixture\ExampleEnum;
use Torr\SimpleNormalizer\Context\ContextBag;
use Torr\SimpleNormalizer\Exception\Context\InvalidContextTypeException;
use Torr\SimpleNormalizer\Exception\Context\MissingContextException;

/**
 * @internal
 */
final class ContextBagTest extends TestCase
{
	/**
	 *
	 */
	public function testValidGetters () : void
	{
		$bag = self::createBag();

		// check specialized getters
		self::assertSame(ExampleEnum::Test, $bag->getObject("enum", ExampleEnum::class));
		self::assertSame(ExampleBackedEnum::Test, $bag->getObject("backed-enum", ExampleBackedEnum::class));
		self::assertSame(11, $bag->getInt("int"));
		self::assertSame(4.2, $bag->getFloat("float"));
		self::assertSame("test", $bag->getString("string"));
		self::assertTrue($bag->getBool("bool"));
		self::assertNull($bag->get("null"));

		// check generic getters
		self::assertSame(ExampleEnum::Test, $bag->get("enum"));
		self::assertSame(ExampleBackedEnum::Test, $bag->get("backed-enum"));
		self::assertSame(11, $bag->get("int"));
		self::assertSame(4.2, $bag->get("float"));
		self::assertSame("test", $bag->get("string"));
		self::assertTrue($bag->get("bool"));
		self::assertNull($bag->get("null"));

		// optional: missing
		self::assertNull($bag->getOptional("missing"));
	}

	/**
	 *
	 */
	public function testMissingGetter () : void
	{
		$this->expectException(MissingContextException::class);
		$bag = self::createBag();

		$bag->get("missing");
	}

	/**
	 *
	 */
	public static function provideInvalidGetters () : iterable
	{
		$bag = self::createBag();

		$correctMapping = [
			"enum" => static fn (string $key) => $bag->getObject($key, ExampleEnum::class),
			"backed-enum" => static fn (string $key) => $bag->getObject($key, ExampleBackedEnum::class),
			"int" => $bag->getInt(...),
			"float" => $bag->getFloat(...),
			"string" => $bag->getString(...),
			"bool" => $bag->getBool(...),
		];

		foreach ($correctMapping as $key => $getter)
		{
			foreach (array_keys($correctMapping) as $invalidKey)
			{
				if ($invalidKey === $key)
				{
					continue;
				}

				yield "testing required getter for '{$key}' with key '{$invalidKey}'" => [
					$getter,
					$invalidKey,
				];
			}

			yield "testing required getter for '{$key}' with key 'null'" => [
				$getter,
				"null",
			];
		}
	}

	/**
	 */
	#[DataProvider("provideInvalidGetters")]
	public function testInvalidGetters (callable $getter, string $key) : void
	{
		$this->expectException(InvalidContextTypeException::class);
		$getter($key);
	}

	/**
	 *
	 */
	public function testOptionalTypedGetters () : void
	{
		$bag = self::createBag();

		self::assertSame("test", $bag->getOptionalString("string"));
		self::assertSame(11, $bag->getOptionalInt("int"));
		self::assertSame(4.2, $bag->getOptionalFloat("float"));
		self::assertTrue($bag->getOptionalBool("bool"));
		self::assertSame(ExampleEnum::Test, $bag->getOptionalObject("enum", ExampleEnum::class));
		self::assertNull($bag->getOptionalArray("missing"));
	}

	/**
	 *
	 */
	public function testUtilityMethods () : void
	{
		$bag = self::createBag();

		self::assertSame(
			["enum", "backed-enum", "int", "float", "string", "bool", "null"],
			$bag->keys(),
		);
		self::assertTrue($bag->has("null"));
		self::assertFalse($bag->has("missing"));
		self::assertCount(7, $bag);
		self::assertSame($bag->all(), iterator_to_array($bag));
	}

	/**
	 *
	 */
	private static function createBag () : ContextBag
	{
		return new ContextBag([
			"enum" => ExampleEnum::Test,
			"backed-enum" => ExampleBackedEnum::Test,
			"int" => 11,
			"float" => 4.2,
			"string" => "test",
			"bool" => true,
			"null" => null,
		]);
	}
}
