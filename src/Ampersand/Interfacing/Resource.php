<?php

/*
 * This file is part of the Ampersand backend framework.
 *
 */

namespace Ampersand\Interfacing;

use stdClass;
use Exception;
use Ampersand\Core\Atom;
use Ampersand\Core\Concept;
use Ampersand\Log\Logger;
use Ampersand\Interfacing\Options;
use Ampersand\Interfacing\InterfaceObjectInterface;
use Ampersand\Interfacing\ResourcePath;
use Ampersand\Interfacing\ResourceList;
use Ampersand\Exception\AtomNotFoundException;
use Ampersand\Exception\BadRequestException;

/**
 *
 * @author Michiel Stornebrink (https://github.com/Michiel-s)
 *
 */
class Resource extends Atom
{
    /**
     * Interface for this resource
     *
     * The interface defines which properties and methods the resource has.
     * Interface definitions are generated by the Ampersand prototype generator.
     */
    protected InterfaceObjectInterface $ifc;

    /**
     * Parent resource list
     */
    protected ResourceList $parentList;
    
    /**
     * Constructor
     */
    public function __construct(string $resourceId, Concept $cpt, ResourceList $parentList)
    {
        // if (!$cpt->isObject()) {
        //     throw new BadRequestException("Cannot instantiate resource, because its type '{$cpt}' is a non-object concept");
        // }
        
        // Call Atom constructor
        parent::__construct($resourceId, $cpt);

        $this->ifc = $parentList->getIfcObject(); // shortcut
        $this->parentList = $parentList;
    }

    /**
     * Function is called when object is treated as a string
     *
     * This functionality is needed when the ArrayAccess::offsetGet method below is used by internal code
     */
    public function __toString(): string
    {
        return (string) parent::jsonSerialize();
    }

    public function getLabel(): string
    {
        return implode('', $this->ifc->getViewData($this));
    }

    /**
     * Return interface for this resource
     */
    public function getIfc(): InterfaceObjectInterface
    {
        return $this->ifc;
    }

    /**********************************************************************************************
     * Methods to navigate through list
     *********************************************************************************************/

    /**
     * Get a single tgt resource for a certain sub interface of this resource
     *
     * If multiple resources are set then the resource returned may be arbitrary.
     * Throws exception when no resource is set
     *
     * @throws \Ampersand\Exception\AtomNotFoundException
     */
    public function one(string $ifcId, ?string $tgtId = null): Resource
    {
        return $this->list($ifcId)->one($tgtId);
    }

    /**
     * Undocumented function
     *
     * @return \Ampersand\Interfacing\Resource[]
     */
    public function all(string $ifcId): array
    {
        return $this->list($ifcId)->getResources();
    }

    public function list(string $ifcId): ResourceList
    {
        return new ResourceList(
            $this,
            $this->ifc->getSubinterface($ifcId, Options::INCLUDE_REF_IFCS | Options::INCLUDE_LINKTO_IFCS),
            $this->parentList->getResourcePath($this)
        );
    }

    public function isset(string $ifcId): bool
    {
        return !empty($this->all($ifcId));
    }

    /**
     * Get a single string value for a certain sub interface of this resource
     *
     * If multiple values are set then the value returned may be arbitrary.
     * Returns null if there is no value set
     */
    public function value(string $ifcId): ?string
    {
        $tgts = $this->all($ifcId);

        return empty($tgts) ? null : current($tgts)->getId();
    }

    public function mandatoryValue(string $ifcId): string
    {
        $tgts = $this->all($ifcId);

        if (empty($tgts)) {
            throw new AtomNotFoundException("No value set for sub interface '{$ifcId}' of '{$this->parentList->getResourcePath($this)}'");
        }
        
        return current($tgts)->getId();
    }

    /**
     * Get string values for a certain sub interface of this resource
     *
     * Returns empty list if there are no values set
     * @return string[]
     */
    public function values(string $ifcId): array
    {
        return array_map(
            function (Resource $resource) {
                return $resource->getId();
            },
            $this->all($ifcId)
        );
    }

    /**
     * Undocumented function
     */
    public function walkPath(array $pathList): Resource|ResourceList
    {
        if (empty($pathList)) {
            return $this;
        } else {
            return $this->list(array_shift($pathList))->walkPath($pathList);
        }
    }

    public function walkPathToResource(array $pathList): Resource
    {
        if (empty($pathList)) {
            return $this;
        } else {
            return $this->list(array_shift($pathList))->walkPathToResource($pathList);
        }
    }

    public function walkPathToList(array $pathList): ResourceList
    {
        if (empty($pathList)) {
            throw new BadRequestException("Provided path MUST NOT end with a resource identifier");
        } else {
            return $this->list(array_shift($pathList))->walkPathToList($pathList);
        }
    }

/**************************************************************************************************
 * REST methods to call on Resource
 *************************************************************************************************/
 
    /**
     * Get resource data according to provided interface
     */
    public function get(int $options = Options::DEFAULT_OPTIONS, ?int $depth = null): mixed
    {
        return $this->parentList->getOne($this->id, $options, $depth);
    }
    
    /**
     * Update a resource (updates only first level of subinterfaces, for now)
     */
    public function put(?stdClass $resourceToPut = null): self
    {
        if (!isset($resourceToPut)) {
            return $this; // nothing to do
        }

        // Perform PUT using the interface definition
        foreach ($resourceToPut as $ifcId => $value) {
            if (substr($ifcId, 0, 1) === '_' && substr($ifcId, -1) === '_') {
                continue; // skip special internal attributes
            }
            try {
                $list = $this->list($ifcId);
            } catch (Exception $e) {
                Logger::getLogger('INTERFACING')->warning("Unknown attribute '{$ifcId}' in PUT data");
                continue;
            }

            if ($list->isUni()) {
                if (is_null($value) || is_scalar($value)) { // null or scalar (i.e. int, float, string, bool)
                    $list->set($value);
                } elseif (is_object($value)) {
                    if (isset($value->_id_)) { // object with _id_ attribute
                        $list->set($value->_id_);
                    } else { // object to post
                        $list->post($value);
                    }
                } else {
                    throw new BadRequestException("Wrong datatype provided: expecting null, scalar or object for '{$list->getIfcObject()->getPath()}'");
                }
            } else { // expect value to be array
                if (!is_array($value)) {
                    throw new BadRequestException("Wrong datatype provided: expecting array for {$list->getIfcObject()->getPath()}");
                }
                
                // First empty existing list
                $list->removeAll();
                
                // Add provided values
                foreach ($value as $item) {
                    if (is_scalar($item)) { // scalar (i.e. int, float, string, bool)
                        $list->add($item);
                    } elseif (is_object($item)) {
                        if (isset($item->_id_)) { // object with _id_ attribute
                            $list->add($item->_id_);
                        } else { // object to post
                            $list->post($item);
                        }
                    } else {
                        throw new BadRequestException("Wrong datatype provided: expecting scalar or object for '{$list->getIfcObject()->getPath()}'");
                    }
                }
            }
        }
        
        // Clear query data
        $this->setQueryData(null);
        
        return $this;
    }
    
    /**
     * Patch this resource with provided patches
     *
     * Use JSONPatch specification for $patches (see: http://jsonpatch.com/)
     */
    public function patch(array $patches): self
    {
        foreach ($patches as $key => $patch) {
            if (!property_exists($patch, 'op')) {
                throw new BadRequestException("No 'op' (i.e. operation) specfied for patch #{$key}");
            }
            if (!property_exists($patch, 'path')) {
                throw new BadRequestException("No 'path' specfied for patch #{$key}");
            }

            $pathList = ResourcePath::makePathList($patch->path);
            
            try {
                // Process patch
                switch ($patch->op) {
                    case "replace":
                        if (!property_exists($patch, 'value')) {
                            throw new BadRequestException("No 'value' specfied");
                        }
                        $this->walkPathToList($pathList)->set($patch->value);
                        break;
                    case "add":
                        if (!property_exists($patch, 'value')) {
                            throw new BadRequestException("No 'value' specfied");
                        }
                        $this->walkPathToList($pathList)->add($patch->value);
                        break;
                    case "remove":
                        // Regular json patch remove operation, uses last part of 'path' attribuut as resource to remove from list
                        if (!property_exists($patch, 'value')) {
                            $this->walkPathToResource($pathList)->remove();
                        // Not part of official json path specification. Uses 'value' attribute that must be removed from list
                        } elseif (property_exists($patch, 'value')) {
                            $this->walkPathToList($pathList)->remove($patch->value);
                        }
                        break;
                    case "create":
                        if (!property_exists($patch, 'value')) {
                            throw new BadRequestException("No 'value' specfied");
                        }
                        $this->walkPathToList($pathList)->create($patch->value);
                        break;
                    default:
                        throw new BadRequestException("Unknown patch operation '{$patch->op}'. Supported are: 'replace', 'add' and 'remove', 'create'");
                }
            } catch (BadRequestException $e) {
                // Add patch # to all bad request exceptions
                throw new BadRequestException("Error in patch #{$key}: {$e->getMessage()}", previous: $e);
            }
        }
        
        // Clear query data
        $this->setQueryData(null);
        
        return $this;
    }

    public function post($subIfcId, stdClass $resourceToPost = null): Resource
    {
        return $this->list($subIfcId)->post($resourceToPost);
    }
    
    /**
     * Delete this resource and remove as target atom from current interface
     */
    public function delete(): self
    {
        // Special case for FileObject: get filepath before deleting the atom
        if ($this->concept->isFileObject()) {
            $filePaths = []; // filePath[FileObject*FilePath] is UNI, so we expect max 1 link
            foreach ($this->getLinks('filePath[FileObject*FilePath]') as $link) {
                $filePaths[] = $link->tgt()->getId();
            }

            // Perform DELETE using the interface definition
            $this->ifc->delete($this);

            // Special case for FileObject: delete files from file system
            foreach ($filePaths as $path) {
                $this->concept->getApp()->fileSystem()->delete($path);
            }
        } else {
            // Perform DELETE using the interface definition
            $this->ifc->delete($this);
        }
        
        return $this;
    }

    public function remove(): void
    {
        $this->parentList->remove($this->id);
    }
}
