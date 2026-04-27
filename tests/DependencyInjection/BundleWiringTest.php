<?php declare(strict_types=1);

namespace Tests\Torr\SimpleNormalizer\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Torr\SimpleNormalizer\Fixture\DummyVO;
use Torr\SimpleNormalizer\Normalizer\SimpleNormalizer;
use Torr\SimpleNormalizer\Normalizer\SimpleObjectNormalizerInterface;
use Torr\SimpleNormalizer\SimpleNormalizerBundle;

/**
 * @internal
 */
final class BundleWiringTest extends TestCase
{
	/**
	 */
	public function testBundleWiresAutoconfiguredObjectNormalizers () : void
	{
		$container = new ContainerBuilder();

		$bundle = new SimpleNormalizerBundle();
		$bundle->build($container);

		$container
			->register(BundleWiringDummyNormalizer::class, BundleWiringDummyNormalizer::class)
			->setAutoconfigured(true)
			->setAutowired(true);

		$container
			->register(SimpleNormalizer::class, SimpleNormalizer::class)
			->setPublic(true)
			->setArguments([
				new ServiceLocatorArgument(
					new TaggedIteratorArgument(
						"torr.normalizer.simple-object-normalizer",
						defaultIndexMethod: "getNormalizedType",
						needsIndexes: true,
					),
				),
				false,
				null,
				null,
			]);

		$container->compile();

		$normalizer = $container->get(SimpleNormalizer::class);
		\assert($normalizer instanceof SimpleNormalizer);

		self::assertSame(
			["id" => 42],
			$normalizer->normalize(new DummyVO(42)),
		);
	}
}

final class BundleWiringDummyNormalizer implements SimpleObjectNormalizerInterface
{
	/**
	 * @inheritDoc
	 */
	public function normalize (object $value, array $context, SimpleNormalizer $normalizer) : mixed
	{
		\assert($value instanceof DummyVO);

		return [
			"id" => $value->id,
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getNormalizedType () : string
	{
		return DummyVO::class;
	}
}
