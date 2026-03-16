<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Normalizer\Validator;

use PHPUnit\Framework\TestCase;
use Tests\Torr\SimpleNormalizer\Fixture\DummyVO;
use Torr\SimpleNormalizer\Exception\IncompleteNormalizationException;
use Torr\SimpleNormalizer\Normalizer\Validator\ValidJsonVerifier;

/**
 * @internal
 */
final class ValidJsonVerifierTest extends TestCase
{
	/**
	 */
	public function testAcceptsJsonCompatibleValue () : void
	{
		$value = [
			"scalar" => 42,
			"bool" => true,
			"nested" => [
				"null" => null,
				"list" => [1, 2, 3],
			],
			"emptyObject" => new \stdClass(),
		];

		$verifier = new ValidJsonVerifier();
		$verifier->ensureValidOnlyJsonTypes($value);
		self::assertTrue(true);
	}

	/**
	 */
	public function testReportsDeepInvalidPath () : void
	{
		$verifier = new ValidJsonVerifier();

		$this->expectException(IncompleteNormalizationException::class);
		$this->expectExceptionMessage("Found a JSON-incompatible value when normalizing. Found 'Tests\Torr\SimpleNormalizer\Fixture\DummyVO' at path '$.nested.deeply.0', but expected only scalars, arrays and empty objects.");

		$verifier->ensureValidOnlyJsonTypes([
			"nested" => [
				"deeply" => [
					new DummyVO(42),
				],
			],
		]);
	}

	/**
	 */
	public function testReportsFirstInvalidElement () : void
	{
		$verifier = new ValidJsonVerifier();

		$this->expectException(IncompleteNormalizationException::class);
		$this->expectExceptionMessage("Found a JSON-incompatible value when normalizing. Found 'Tests\Torr\SimpleNormalizer\Fixture\DummyVO' at path '$.first', but expected only scalars, arrays and empty objects.");

		$invalidStdClass = new \stdClass();
		$invalidStdClass->prop = "x";

		$verifier->ensureValidOnlyJsonTypes([
			"first" => new DummyVO(5),
			"second" => $invalidStdClass,
		]);
	}

	/**
	 */
	public function testReportsMixedNumericAndStringKeyPath () : void
	{
		$verifier = new ValidJsonVerifier();

		$this->expectException(IncompleteNormalizationException::class);
		$this->expectExceptionMessage("Found a JSON-incompatible value when normalizing. Found 'Tests\Torr\SimpleNormalizer\Fixture\DummyVO' at path '$.outer.01.2.inner', but expected only scalars, arrays and empty objects.");

		$verifier->ensureValidOnlyJsonTypes([
			"outer" => [
				"01" => [
					2 => [
						"inner" => new DummyVO(6),
					],
				],
			],
		]);
	}

	/**
	 */
	public function testReportsVeryDeepPath () : void
	{
		$verifier = new ValidJsonVerifier();

		$this->expectException(IncompleteNormalizationException::class);
		$this->expectExceptionMessage("Found a JSON-incompatible value when normalizing. Found 'Tests\\Torr\\SimpleNormalizer\\Fixture\\DummyVO' at path '$.root.lvl1.lvl2.lvl3.lvl4.lvl5.lvl6.target', but expected only scalars, arrays and empty objects.");

		$verifier->ensureValidOnlyJsonTypes([
			"root" => [
				"lvl1" => [
					"lvl2" => [
						"lvl3" => [
							"lvl4" => [
								"lvl5" => [
									"lvl6" => [
										"target" => new DummyVO(9),
									],
								],
							],
						],
					],
				],
			],
		]);
	}
}
