<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\Normalizer\ObjectNormalizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Tests\Torr\SimpleNormalizer\Fixture\DummyVO;
use Torr\SimpleNormalizer\Data\ValueWithContext;
use Torr\SimpleNormalizer\Normalizer\ObjectNormalizer\ValueWithContextNormalizer;
use Torr\SimpleNormalizer\Normalizer\SimpleNormalizer;
use Torr\SimpleNormalizer\Normalizer\SimpleObjectNormalizerInterface;

/**
 * @internal
 */
final class ValueWithContextNormalizerTest extends TestCase
{
	/**
	 *
	 */
	public function testContextPassing () : void
	{
		$value = new ValueWithContext(
			new DummyVO(5),
			[
				"o" => "hai",
			],
		);

		$dummyNormalizer = $this->createMock(SimpleObjectNormalizerInterface::class);
		$dummyNormalizer
			->expects(self::once())
			->method("normalize")
			->with(
				$value->value,
				[
					"test" => 123,
					"o" => "hai",
				],
			);

		$normalizer = new SimpleNormalizer(new ServiceLocator([
			ValueWithContext::class => static fn () => new ValueWithContextNormalizer(),
			DummyVO::class => static fn () => $dummyNormalizer,
		]));

		$normalizer->normalize($value, [
			"test" => 123,
			"o" => 5,
		]);
	}

	/**
	 */
	public function testEmptyWrappedContextKeepsIncomingContext () : void
	{
		$value = new ValueWithContext(new DummyVO(5), []);

		$dummyNormalizer = $this->createMock(SimpleObjectNormalizerInterface::class);
		$dummyNormalizer
			->expects(self::once())
			->method("normalize")
			->with(
				$value->value,
				[
					"test" => 123,
				],
			);

		$normalizer = new SimpleNormalizer(new ServiceLocator([
			ValueWithContext::class => static fn () => new ValueWithContextNormalizer(),
			DummyVO::class => static fn () => $dummyNormalizer,
		]));

		$normalizer->normalize($value, [
			"test" => 123,
		]);
	}

	/**
	 */
	public function testEmptyIncomingContextUsesWrappedContext () : void
	{
		$value = new ValueWithContext(new DummyVO(5), [
			"o" => "hai",
		]);

		$dummyNormalizer = $this->createMock(SimpleObjectNormalizerInterface::class);
		$dummyNormalizer
			->expects(self::once())
			->method("normalize")
			->with(
				$value->value,
				[
					"o" => "hai",
				],
			);

		$normalizer = new SimpleNormalizer(new ServiceLocator([
			ValueWithContext::class => static fn () => new ValueWithContextNormalizer(),
			DummyVO::class => static fn () => $dummyNormalizer,
		]));

		$normalizer->normalize($value);
	}
}
