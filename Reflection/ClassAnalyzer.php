<?php

namespace Parabol\DoctrineBehaviorsBundle\Reflection;

class ClassAnalyzer 
{
	  /**
     * Return TRUE if the given object use the given trait, FALSE if not
     * @param ReflectionClass $class
     * @param string $traitName
     * @param boolean $isRecursive
     */
    public function hasTrait($class, $traitName, $isRecursive = false)
    {
        if(is_string($class)) $class = new \ReflectionClass($class);

        if (in_array($traitName, $class->getTraitNames())) {
            return true;
        }

        $parentClass = $class->getParentClass();

        if ((false === $isRecursive) || (false === $parentClass) || (null === $parentClass)) {
            return false;
        }

        return $this->hasTrait($parentClass, $traitName, $isRecursive);
    }

    /**
     * Return TRUE if the given object has the given method, FALSE if not
     * @param ReflectionClass $class
     * @param string $methodName
     */
    public function hasMethod(\ReflectionClass $class, $methodName)
    {
        return $class->hasMethod($methodName);
    }

    /**
     * Return TRUE if the given object has the given property, FALSE if not
     * @param ReflectionClass $class
     * @param string $propertyName
     */
    public function hasProperty($class, $propertyName)
    {
        if(is_string($class)) $class = new \ReflectionClass($class);

        if ($class->hasProperty($propertyName)) {
            return true;
        }

        $parentClass = $class->getParentClass();

        if (false === $parentClass) {
            return false;
        }
        
        return $this->hasProperty($parentClass, $propertyName);
    }
}