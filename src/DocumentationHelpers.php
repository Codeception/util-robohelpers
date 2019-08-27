<?php


namespace Codeception\Util;


trait DocumentationHelpers
{

    /**
     * @param string $className
     * @param string $documentationFile
     * @param string $sourceMessage
     */
    protected function generateDocumentationForClass($className, $documentationFile, $sourceMessage = '')
    {
        $moduleName = (new \ReflectionClass($className))->getShortName();
        $this->taskGenDoc($documentationFile)
            ->docClass($className)
            ->prepend('# ' . $moduleName)
            ->append($sourceMessage)
            ->processClassSignature(false)
            ->processClassDocBlock(function (\ReflectionClass $c, $text) {
                return "$text\n## Actions";
            })
            ->processProperty(false)
            ->filterMethods(function (\ReflectionMethod $method) {
                if ($method->isConstructor() or $method->isDestructor()) {
                    return false;
                }
                if (!$method->isPublic()) {
                    return false;
                }
                if (strpos($method->name, '_') === 0) {
                    $doc = $method->getDocComment();
                    try {
                        $doc = $doc . $method->getPrototype()->getDocComment();
                    } catch (\ReflectionException $e) {
                    }

                    if (strpos($doc, '@api') === false) {
                        return false;
                    }
                };
                return true;
            })->processMethod(function (\ReflectionMethod $method, $text) use ($moduleName) {
                $title = "\n### {$method->name}\n";
                if (strpos($method->name, '_') === 0) {
                    $text = str_replace("@api\n", '', $text);
                    $text = "\n*hidden API method, expected to be used from Helper classes*\n" . $text;
                    $text = str_replace("{{MODULE_NAME}}", $moduleName, $text);
                };

                if (!trim($text)) {
                    return $title . "__not documented__\n";
                }

                $text   = str_replace(
                    [
                        '@since',
                        '@version'
                    ],
                    [
                        ' * `Available since`',
                        ' * `Available since`'
                    ],
                    $text
                );
                $text   = str_replace('@part ', ' * `[Part]` ', $text);
                $text   = str_replace("@return mixed\n", '', $text);
                $text   = preg_replace('~@return (.*?)~', ' * `return` $1', $text);
                $text   = preg_replace("~^@(.*?)([$\s])~", ' * `$1` $2', $text);
                $result = $title . $text;
                return preg_replace('/\n(\s*\n){2,}/', "\n\n", $result);
            })->processMethodSignature(false)
            ->reorderMethods('ksort')
            ->run();
    }
}