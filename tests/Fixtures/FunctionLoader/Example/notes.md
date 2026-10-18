Fixtures for FunctionLoaderCacheTest.

- AlphaFunction.php / BetaFunction.php implement XPathFunctionInterface and must be discovered.
- NotAFunction.php matches the *Function.php name filter but implements no interface and must be skipped.
- IgnoredHelper.php implements the interface but must be skipped by the *Function.php name filter.
- This file must be skipped because it is not a PHP function file.
