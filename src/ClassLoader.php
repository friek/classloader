<?php

namespace ClassLoader;

use Psr\Log\LoggerInterface;

/**
 * Created by Johan Mulder <johan@cambrium.nl>
 * Date: 2016-05-20 14:19
 */
class ClassLoader
{
    /**
     * An associative array containing <class name> => callbacks.
     * @var callable[]
     */
    private $initializerCallbacks = [];
    /**
     * An associative array containing initialized instances of classes.
     * @var object[]
     */
    private $initializedClasses = [];
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __destruct()
    {
        foreach ($this->initializedClasses as $className => $instance) {
            try {
                if (is_callable(['__destruct', $instance])) {
                    $instance->__destruct();
                }
            } catch (\Exception $e) {
                $this->logger->error('Exception caught while trying to destruct class: ' . $e->getMessage(),
                    ['exception' => $e]);
            }
        }
        $this->initializedClasses = [];
    }

    public function getObject($className)
    {
        if (!class_exists($className)) {
            throw new ClassNotFoundException('Class ' . $className . ' does not exist');
        }

        return $this->loadClass($className);
    }

    /**
     * Register a class initializer.
     * @param string $className The class name to register the initializer for.
     * @param callable $callback The callback which initializes the class
     */
    public function registerInitializer($className, callable  $callback)
    {
        // TODO: Implement checks for duplicates
        $this->initializerCallbacks[$className] = $callback;
    }

    /**
     * @param $className
     * @param $originalClassName
     * @return object
     * @throws CircularDependencyException
     */
    private function loadClass($className, $originalClassName = null)
    {
        if ($className === $originalClassName) {
            throw new CircularDependencyException('Can not load class ' . $className . ' because it references itself');
        }

        if ($originalClassName === null) {
            $originalClassName = $className;
        }

        // If there's an initialization callback registered for the class, hand the construction of the object
        // off to this class.
        if (array_key_exists($className, $this->initializerCallbacks)) {
            return call_user_func($this->initializerCallbacks[$className], $className, $this);
        }

        // If the object has already been constructed, return it.
        if (array_key_exists($className, $this->initializedClasses)) {
            return $this->initializedClasses[$className];
        }

        // Get the constructor for the class.
        $c = new \ReflectionClass($className);
        if (($constructor = $c->getConstructor()) === null) {
            $instance = $c->newInstanceWithoutConstructor();
        }
        else {
            // Retrieve the argument list.
            $argumentList = $this->getConstructorArgumentList($constructor);
            // Initialize the argument list.
            $initializedArguments = $this->initializeConstructorParameters($constructor, $argumentList, $originalClassName);
            // Create a new instance.
            $instance = $c->newInstanceArgs($initializedArguments);
        }

        $this->initializedClasses[$className] = $instance;
        return $this->initializedClasses[$className];

    }

    /**
     * @param \ReflectionMethod $constructor
     * @param \ReflectionParameter $param
     * @return \ReflectionClass|string
     */
    private function getConstructorParameterClass(\ReflectionMethod $constructor, \ReflectionParameter $param)
    {
        $paramType = $param->getClass();
        if ($paramType === null) {
            return $this->getConstructorParameterFromDocBlock($constructor, $param);
        }
        return $paramType;
    }

    private function getConstructorParameterFromDocBlock(\ReflectionMethod $constructor, \ReflectionParameter $param)
    {
        return 'string';
    }

    /**
     * @param \ReflectionMethod $constructor
     * @return array
     */
    private function getConstructorArgumentList(\ReflectionMethod $constructor)
    {
        $argumentList = [];
        foreach ($constructor->getParameters() as $param) {
            $argumentList[$param->getName()] = $this->getConstructorParameterClass($constructor, $param);
        }
        return $argumentList;
    }

    /**
     * @param array $argumentList
     * @param $originalClassName
     * @return array|string
     * @throws CircularDependencyException
     */
    private function initializeConstructorParameters(\ReflectionClass $constructor, array $argumentList,
        $originalClassName)
    {
        $arguments = [];
        foreach ($argumentList as $paramName => $paramType) {
            if ($paramType instanceof \ReflectionClass) {
                // XXX: This may involve recursion. That's why the original class name is included to prevent endless loops.
                $arguments[$paramName] = $this->loadClass($paramType->getName(), $originalClassName);
            }
            else {
                // TODO: Implement.
                // 1. If the docblock indicates a type for the parameter, attempt to initialize it.
                // 2. If it doesn't check if it has a default value.
                // 3. If it doesn't, throw an exception.
                if (false) {
                    // TODO: If the docblock indicates a type for the parameter, attempt to initialize it.
                }
                else {
                    try {
                        return $param->getDefaultValue();
                    } catch (\ReflectionException $e) {
                        throw new UnresolvedParameterException('Unable to resolve type of parameter ' . $param->getName() .
                            ' of constructor of class ' . $constructor->getDeclaringClass()->getName(), 0, $e);
                    }
                }
            }
        }
        return $arguments;
    }
}