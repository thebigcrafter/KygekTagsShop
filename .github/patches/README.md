# Maintained dependency patch

`libasynql-php84-nullability.patch` applies only the nullable parameter type declarations from `poggit/libasynql` commit `0cb1cb8451513d9fd2e903f7652083fe7b7d6717` to the locked `sof3/libasynql` source.

The patch keeps the dependency lock unchanged and avoids unrelated upstream changes. It is applied after Composer installation and before API checks, static analysis and PHAR packaging. The `git apply --check` guard makes a dependency layout or source change fail the workflow instead of silently producing an unverified artifact.
