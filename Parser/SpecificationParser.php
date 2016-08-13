<?php

namespace IndraGunawan\RestService\Parser;

use IndraGunawan\RestService\Exception\InvalidSpecificationException;
use IndraGunawan\RestService\Validator\SpecificationConfiguration;
use IndraGunawan\RestService\Validator\Validator;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Resource\FileResource;

class SpecificationParser
{
    /**
     * Parse from specificationFile to specificationArray.
     *
     * @param string $specificationFile
     * @param array  $defaults
     * @param string $cacheDir
     * @param bool   $debug
     *
     * @throws InvalidSpecificationException
     *
     * @return array
     */
    public function parse($specificationFile, array $defaults = [], $cacheDir = null, $debug = true)
    {
        $cachePath = $cacheDir.'/restService_'.md5($specificationFile);

        $restServiceCache = new ConfigCache($cachePath, $debug);
        if (false === $restServiceCache->isFresh()) {
            try {
                $specificationArray = require $specificationFile;
                $specificationArray = ['rest_service' => $specificationArray];
                $processor = new Processor();
                $specification = $processor->processConfiguration(
                    new SpecificationConfiguration(),
                    $specificationArray
                );

                $this->validateDefaults($specification, $defaults);
                $this->parseShapes($specification['shapes']);
                $this->parseOperations(
                    $specification['operations'],
                    $specification['shapes'],
                    $specification['errorShapes']
                );

                $restServiceCache->write(sprintf('<?php return %s;', var_export($specification, true)), [new FileResource($specificationFile)]);

                return $specification;
            } catch (\Exception $e) {
                throw new InvalidSpecificationException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return require $cachePath;
    }

    /**
     * Validate and Merge defaults from specification with user input.
     *
     * @param array &$specification
     * @param array $defaults
     */
    private function validateDefaults(array &$specification, array $defaults)
    {
        $validator = new Validator();

        foreach ($specification['defaults'] as $key => $default) {
            if (array_key_exists($key, $defaults)) {
                $validator->add($key, $default, $defaults[$key]);
                unset($defaults[$key]);
            } else {
                $validator->add($key, $default);
            }
        }

        if (count($defaults) > 0) {
            throw new InvalidSpecificationException(sprintf(
                'Undefined defaults "%s".',
                implode('", "', array_keys($defaults))
            ));
        }

        if (!$validator->isValid()) {
            throw $validator->createValidatorException();
        }

        $patterns = [];
        $replacements = [];
        foreach ($validator->getDatas() as $key => $value) {
            $specification['defaults'][$key]['value'] = $value;

            $patterns[] = '/{'.$key.'}/';
            $replacements[] = $value;
        }

        // assign default value to all posibility
        $specification['endpoint'] = rtrim(preg_replace($patterns, $replacements, $specification['endpoint']), '/').'/';

        foreach ($specification['shapes'] as $name => $shape) {
            foreach ($shape['members'] as $memberName => $member) {
                if ($member['defaultValue']) {
                    $defaultValue = preg_replace($patterns, $replacements, $member['defaultValue']);
                    $specification['shapes'][$name]['members'][$memberName]['defaultValue'] = $defaultValue;
                }
            }
        }

        foreach ($specification['operations'] as $name => $operation) {
            foreach (['request', 'response'] as $placement) {
                if (isset($operation[$placement])) {
                    foreach ($operation[$placement]['members'] as $memberName => $member) {
                        if ($member['defaultValue']) {
                            $defaultValue = preg_replace($patterns, $replacements, $member['defaultValue']);
                            $specification['operations'][$name][$placement]['members'][$memberName]['defaultValue'] = $defaultValue;
                        }
                    }
                }
            }
        }
    }

    /**
     * Parse Operations section of specification.
     *
     * @param array &$operations
     * @param array $shapes
     * @param array $errorShapes
     */
    private function parseOperations(array &$operations, array $shapes, array $errorShapes)
    {
        foreach ($operations as $name => $operation) {
            foreach (['request', 'response'] as $placement) {
                if (isset($operation[$placement])) {
                    // merge member shape if exist
                    foreach ($operation[$placement]['members'] as $memberName => $member) {
                        $shapeName = $member['shape'];
                        if ($shapeName) {
                            if (!array_key_exists($shapeName, $shapes)) {
                                throw new InvalidSpecificationException(sprintf(
                                    'Shape "%s" not found, used by "%s"',
                                    $shapeName,
                                    $name
                                ));
                            }

                            $newMemberShape = array_replace_recursive(
                                $shapes[$shapeName],
                                $operation[$placement]['members'][$memberName]
                            );
                            $newMemberShape['shape'] = null;

                            $operations[$name][$placement]['members'][$memberName] = $newMemberShape;
                        }
                    }

                    foreach (['shape', 'extends'] as $property) {
                        $shapeName = $operation[$placement][$property];
                        if ($shapeName) {
                            if (!array_key_exists($shapeName, $shapes)) {
                                throw new InvalidSpecificationException(sprintf(
                                    'Shape "%s" not found, used by "%s"',
                                    $shapeName,
                                    $name
                                ));
                            }

                            $newShape = array_replace_recursive(
                                $shapes[$shapeName],
                                $operation[$placement]
                            );
                            $newShape[$property] = null;

                            $operations[$name][$placement] = $newShape;
                        }
                    }
                }
            }

            // errors
            foreach ($operation['errors'] as $errorName => $error) {
                $errorShape = $error['errorShape'];
                if ($errorShape) {
                    if (!array_key_exists($errorShape, $errorShapes)) {
                        throw new InvalidSpecificationException(sprintf(
                            'Error Shape "%s" not found, used by "%s"',
                            $errorShape,
                            $name
                        ));
                    }

                    $newErrorShape = array_replace_recursive(
                        $error,
                        $errorShapes[$errorShape]
                    );
                    $newErrorShape['errorShape'] = null;

                    $operations[$name]['errors'][$errorName] = $newErrorShape;
                }
            }
        }
    }

    /**
     * Parse shapes section of specification.
     *
     * @param array &$shapes
     */
    private function parseShapes(array &$shapes)
    {
        // merge extends
        foreach (array_keys($shapes) as $name) {
            $this->mergeExtendsShape($shapes, $name);
        }

        // merge member reference
        foreach (array_keys($shapes) as $name) {
            $this->mergetMemberReferenceShape($shapes, $name);
        }
    }

    /**
     * Merge reference member of a shape.
     *
     * @param array  &$shapes
     * @param string $shapeName
     *
     * @return array
     */
    private function mergetMemberReferenceShape(array &$shapes, $shapeName)
    {
        $members = $shapes[$shapeName]['members'];
        foreach ($members as $memberName => $member) {
            $referenceShape = $members[$memberName]['shape'];
            if ($referenceShape) {
                if (!array_key_exists($referenceShape, $shapes)) {
                    throw new InvalidSpecificationException(sprintf(
                        '"%s" not found, used by "%s"',
                        $referenceShape,
                        $memberName
                    ));
                }

                $newMemberShape = array_replace_recursive(
                    $members[$memberName],
                    $this->mergetMemberReferenceShape($shapes, $referenceShape)
                );
                $newMemberShape['shape'] = null;

                $shapes[$shapeName]['members'][$memberName] = $newMemberShape;
            }
        }

        return $shapes[$shapeName];
    }

    /**
     * Merge reference extends of shape.
     *
     * @param array  &$shapes
     * @param string $shapeName
     *
     * @return array
     */
    private function mergeExtendsShape(array &$shapes, $shapeName)
    {
        $shape = $shapes[$shapeName]['extends'];
        if ($shape) {
            if (!array_key_exists($shape, $shapes)) {
                throw new InvalidSpecificationException(sprintf(
                    '"%s" not found, extends by "%s"',
                    $shape,
                    $shapeName
                ));
            }

            $newShape = array_replace_recursive(
                $this->mergeExtendsShape($shapes, $shape),
                $shapes[$shapeName]
            );
            $newShape['extends'] = null;

            $shapes[$shapeName] = $newShape;
        }

        return $shapes[$shapeName];
    }
}
