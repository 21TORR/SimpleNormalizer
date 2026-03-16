1.5.2 (unreleased)
=====

* (improvement) Reduce normalization overhead by tracking the debug stack internally across recursion instead of mutating it in context on each nested value.
* (improvement) Cache Doctrine-normalized class names in `SimpleNormalizer` to avoid repeated metadata lookups for the same object type.
* (improvement) Add test coverage to ensure class-name normalization is cached across repeated normalization calls.
* (improvement) Optimize `ValidJsonVerifier` by reusing a mutable path stack during traversal instead of allocating a new path array for each nested element.
* (improvement) Add dedicated verifier tests for deep-path reporting and first-invalid-element detection.


1.5.1
=====

* (improvement) Support Symfony v8+


1.5.0
=====

* (feature) Add normalization stack for better error reporting.


1.4.1
=====

* (improvement) Avoid using deprecated Doctrine internals when resolving class names.


1.4.0
=====

* (feature) Add `ValidJsonVerifier`, that checks, that every normalizer correctly generated JSON-compatible values.


1.3.3
=====

* (improvement) Add convenience default parameters.
* (improvement) Require PHP 8.4+
* (improvement) Bump dependencies.


1.3.2
=====

* (improvement) Allow normalizing empty `stdClass`.


1.3.1
=====

* (improvement) Make `SimpleNormalizer` non-readonly, to allow it to be mocked.


1.3.0
=====

* (feature) Make `SimpleNormalizer` extendable (for testing).
* (improvement) Make `SimpleNormalizer` readonly.


1.2.2
=====

* (improvement) Make `SimpleNormalizer::normalizeArray` public.


1.2.1
=====

* (improvement) Allow Symfony v7.


1.2.0
=====

* (feature) Add `SimpleNormalizer::normalizeMap()`.
* (feature) Add helper VO `ValueWithContext`.
* (improvement) Require PHP 8.3.


1.1.1
=====

* (bug) Move test trait to published namespace.


1.1.0
=====

* (feature) Add public helper trait `SimpleNormalizerTestTrait`.


1.0.1
=====

* (improvement) Add generic `NormalizationFailedException` for usage inside your normalizers.


1.0.0
=====

Initial Release `\o/`
