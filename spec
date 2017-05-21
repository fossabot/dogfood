src/Internal/Handlers/ItemsHandler.php:            if (count($itemCheck) < $itemCount && $schema->hasMember('additionalItems')) {
src/Internal/Handlers/ItemsHandler.php:                $additionalItems = $schema->getMember('additionalItems');
src/Internal/Handlers/PropertiesHandler.php:        if ($keyword == 'patternProperties' && $schema->hasMember('properties')) {
src/Internal/Handlers/PropertiesHandler.php:            && ($schema->hasMember('properties') || $schema->hasMember('patternProperties'))) {
src/Internal/Handlers/PropertiesHandler.php:        if ($schema->hasMember('properties')) {
src/Internal/Handlers/PropertiesHandler.php:            $schema->getMember('properties')->each(
src/Internal/Handlers/PropertiesHandler.php:        if ($schema->hasMember('patternProperties')) {
src/Internal/Handlers/PropertiesHandler.php:            $schema->getMember('patternProperties')->each(
src/Internal/Handlers/PropertiesHandler.php:        if ($schema->hasMember('additionalProperties')) {
src/Internal/Handlers/PropertiesHandler.php:            $additionalProperties = $schema->getMember('additionalProperties');
src/Internal/Handlers/DependenciesHandler.php:                    if (!$schema->getSpec()->standard('allowSimpleDependencies')) {
