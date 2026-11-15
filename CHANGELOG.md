# Changelog

All notable changes to this package are documented here.

## Unreleased

- Added Laravel 13 support.
- Added transactional instance deletion, including quiet deletes, with rollback when deletion is refused or fails.
- Added explicit anonymizeAndDelete() and chunked anonymizeAndDeleteQuery() operations.
- Added anonymizationRules() methods while preserving the existing $anonymize property.
- Added a validated strategy registry that preserves built-in aliases when custom aliases are configured.
- Removed Faker integration, its direct Composer dependency, and its strategy argument.
- Added LiteralValue for explicit literal replacements and HMAC for keyed pseudonymization.
- Evaluate rules before applying their results and restore attributes on strategy or assignment failure.
- Resolve services with the current application configuration instead of retaining singleton state.
- Fixed static analysis configuration and added pull-request checks, PHP 8.5 coverage, and production smoke tests.
- Rewrote and verified the usage examples.

### Behavior changes

- Invalid rules now throw InvalidArgumentException instead of being stored as literal attribute values. Use LiteralValue or a closure for literal replacements.
- Instance deleteQuietly() now anonymizes; forceDelete() and models without SoftDeletes skip anonymization.
- Rules see the attributes from before the current anonymization call.
- Direct constructor callers should resolve LaravelAnonymizer through the container, which now supplies the strategy registry.
- Models implementing their own delete() method must explicitly integrate anonymizeAndDelete().
- Strategies now receive only the current value and model. Callables may require at most two arguments; phone generation uses PHP directly.
