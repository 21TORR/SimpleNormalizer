<?php declare(strict_types=1);

namespace Torr\SimpleNormalizer\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Torr\SimpleNormalizer\Exception\ObjectTypeNotSupportedException;
use Torr\SimpleNormalizer\Exception\UnsupportedTypeException;
use Torr\SimpleNormalizer\Normalizer\Validator\ValidJsonVerifier;

/**
 * The normalizer to use in your app.
 *
 * Can't be readonly, as it needs to be mock-able.
 *
 * The verifier is done on the top-level of every method (instead of at the point where the invalid values could occur
 * = the object normalizers), as this way we can provide a full path to the invalid element in the JSON.
 *
 * @readonly
 *
 * @final
 */
class SimpleNormalizer
{
	private readonly ?ClassMetadataFactory $doctrineMetadata;
	private const string STACK_CONTEXT = "simple-normalizer.debug-stack";
	/** @var array<class-string, class-string> */
	private array $normalizedClassNames = [];

	/**
	 * @param ServiceLocator<SimpleObjectNormalizerInterface> $objectNormalizers
	 */
	public function __construct (
		private readonly ServiceLocator $objectNormalizers,
		private readonly bool $isDebug = false,
		private readonly ?ValidJsonVerifier $validJsonVerifier = null,
		?EntityManagerInterface $entityManager = null,
	)
	{
		$this->doctrineMetadata = $entityManager?->getMetadataFactory();
	}

	/**
	 */
	public function normalize (mixed $value, array $context = []) : mixed
	{
		$stack = $this->extractInitialStack($context);
		$normalizedValue = $this->recursiveNormalize($value, $context, $stack);

		if ($this->isDebug)
		{
			$this->validJsonVerifier?->ensureValidOnlyJsonTypes($normalizedValue);
		}

		return $normalizedValue;
	}

	/**
	 */
	public function normalizeArray (array $array, array $context = []) : array
	{
		$stack = $this->extractInitialStack($context);
		$normalizedValue = $this->recursiveNormalizeArray($array, $context, $stack);

		if ($this->isDebug)
		{
			$this->validJsonVerifier?->ensureValidOnlyJsonTypes($normalizedValue);
		}

		return $normalizedValue;
	}

	/**
	 * Normalizes a map of values.
	 * Will JSON-encode to `{}` when empty.
	 */
	public function normalizeMap (array $array, array $context = []) : array|\stdClass
	{
		// return stdClass if the array is empty here, as it will be automatically normalized to `{}` in JSON.
		$stack = $this->extractInitialStack($context);
		$normalizedValue = $this->recursiveNormalizeArray($array, $context, $stack) ?: new \stdClass();

		if ($this->isDebug)
		{
			$this->validJsonVerifier?->ensureValidOnlyJsonTypes($normalizedValue);
		}

		return $normalizedValue;
	}

	/**
	 * The actual normalize logic, that recursively normalizes the value.
	 * It must never call one of the public methods above and just normalizes the value.
	 */
	private function recursiveNormalize (mixed $value, array $context, array &$stack) : mixed
	{
		if (null === $value || \is_scalar($value))
		{
			return $value;
		}

		$stack[] = get_debug_type($value);

		try
		{
			if (\is_array($value))
			{
				return $this->recursiveNormalizeArray($value, $context, $stack);
			}

			if (\is_object($value))
			{
				// Allow empty stdClass as a way to force a JSON {} instead of an
				// array which would encode to []
				if ($value instanceof \stdClass && [] === get_object_vars($value))
				{
					return $value;
				}

				try
				{
					$className = $this->normalizeClassName($value::class);
					$normalizer = $this->objectNormalizers->get($className);
					\assert($normalizer instanceof SimpleObjectNormalizerInterface);

					// Preserve debug stack visibility for custom object normalizers.
					$context[self::STACK_CONTEXT] = $stack;

					return $normalizer->normalize($value, $context, $this);
				}
				catch (ServiceNotFoundException $exception)
				{
					throw new ObjectTypeNotSupportedException(\sprintf(
						"Can't normalize type '%s' in stack %s",
						get_debug_type($value),
						implode(" > ", array_reverse($stack)),
					), 0, $exception);
				}
			}

			throw new UnsupportedTypeException(\sprintf(
				"Can't normalize type %s in stack %s",
				get_debug_type($value),
				implode(" > ", array_reverse($stack)),
			));
		}
		finally
		{
			array_pop($stack);
		}
	}

	/**
	 * Normalizes the class name
	 *
	 * @param class-string $className
	 *
	 * @return class-string
	 */
	private function normalizeClassName (string $className) : string
	{
		// if there is no doctrine, just return
		if (null === $this->doctrineMetadata)
		{
			return $className;
		}

		if (isset($this->normalizedClassNames[$className]))
		{
			return $this->normalizedClassNames[$className];
		}

		return $this->normalizedClassNames[$className] = $this->doctrineMetadata->hasMetadataFor($className)
			? $this->doctrineMetadata->getMetadataFor($className)->getName()
			: $className;
	}

	/**
	 * The actual customized normalization logic for arrays, that recursively normalizes the value.
	 * It must never call one of the public methods above and just normalizes the value.
	 */
	private function recursiveNormalizeArray (array $array, array $context, array &$stack) : array
	{
		$result = [];
		$isList = array_is_list($array);

		foreach ($array as $key => $value)
		{
			$normalized = $this->recursiveNormalize($value, $context, $stack);

			// if the array was a list and the normalized value is null, just filter it out
			if ($isList && null === $normalized)
			{
				continue;
			}

			if ($isList)
			{
				// for list: reindex without holes
				$result[] = $normalized;
			}
			else
			{
				// for map: keep key
				$result[$key] = $normalized;
			}
		}

		return $result;
	}

	/**
	 * @return list<string>
	 */
	private function extractInitialStack (array $context) : array
	{
		return isset($context[self::STACK_CONTEXT]) && \is_array($context[self::STACK_CONTEXT])
			? $context[self::STACK_CONTEXT]
			: [];
	}
}
