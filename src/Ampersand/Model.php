<?php

/*
 * This file is part of the Ampersand backend framework.
 *
 */

namespace Ampersand;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Ampersand\Log\Logger;
use Ampersand\Core\Relation;
use Ampersand\Core\Concept;

/**
 *
 * @author Michiel Stornebrink (https://github.com/Michiel-s)
 *
 */
class Model
{
    const HASH_ALGORITHM = 'md5';

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Directory where Ampersand model is generated in
     *
     * @var string
     */
    protected $folder;

    /**
     * Filepath for saving checksums of generated Ampersand model
     *
     * @var string
     */
    protected $checksumFile;

    /**
     * List of files that contain the generated Ampersand model
     *
     * @var array
     */
    protected $modelFiles = [];

    /**
     * List with all defined relations in this Ampersand model
     *
     * @var \Ampersand\Core\Relation[]
     */
    protected $relations = null;

    /**
     * Constructor
     *
     * @param string $folder directory where Ampersand model is generated in
     */
    public function __construct(string $folder, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $fileSystem = new Filesystem;

        if (($this->folder = realpath($folder)) === false) {
            throw new Exception("Specified folder for Ampersand model does not exist: '{$folder}'", 500);
        }
        
        // Ampersand model files
        $this->modelFiles = [
            'concepts' => $this->folder . '/concepts.json',
            'conjuncts' => $this->folder . '/conjuncts.json',
            'interfaces' => $this->folder . '/interfaces.json',
            'populations' => $this->folder . '/populations.json',
            'relations' => $this->folder . '/relations.json',
            'roles' => $this->folder . '/roles.json',
            'rules' => $this->folder . '/rules.json',
            'settings' => $this->folder . '/settings.json',
            'views' => $this->folder . '/views.json',
        ];

        if (!$fileSystem->exists($this->modelFiles)) {
            throw new Exception("Not all Ampersand model files are provided. Check model folder '{$this->folder}'", 500);
        }

        $this->checksumFile = "{$this->folder}/checksums.txt";
        
        // Write checksum file if not yet exists
        if (!file_exists($this->checksumFile)) {
            $this->writeChecksumFile();
        }
    }

    public function init(AmpersandApp $app): Model
    {
        $this->loadRelations(Logger::getLogger('CORE'), $app);
    }

    protected function loadRelations(LoggerInterface $logger, AmpersandApp $app): void
    {
        // Import json file
        $allRelationDefs = (array)json_decode(file_get_contents($this->modelFiles['relations']), true);
    
        $this->relations = [];
        foreach ($allRelationDefs as $relationDef) {
            $relation = new Relation($relationDef, $logger, $app);
            $this->relations[$relation->signature] = $relation;
        }
    }

    /**
     * Returns list of all relation definitions
     *
     * @throws \Exception when relations are not loaded (yet) because model is not initialized
     * @return \Ampersand\Core\Relation[]
     */
    public function getRelations(): array
    {
        if (!isset($this->relations)) {
            throw new Exception("Relation definitions are not loaded yet", 500);
        }
         
        return $this->relations;
    }

    /**
     * Return relation object
     *
     * @param string $relationSignature
     * @param \Ampersand\Core\Concept|null $srcConcept
     * @param \Ampersand\Core\Concept|null $tgtConcept
     *
     * @throws \Exception if relation is not defined
     * @return \Ampersand\Core\Relation
     */
    public function getRelation($relationSignature, Concept $srcConcept = null, Concept $tgtConcept = null): Relation
    {
        if (!isset($this->relations)) {
            throw new Exception("Relation definitions are not loaded yet", 500);
        }
        
        // If relation can be found by its fullRelationSignature return the relation
        if (array_key_exists($relationSignature, $this->relations)) {
            $relation = $this->relations[$relationSignature];
            
            // If srcConceptName and tgtConceptName are provided, check that they match the found relation
            if (!is_null($srcConcept) && !in_array($srcConcept, $relation->srcConcept->getSpecializationsIncl())) {
                throw new Exception("Provided src concept '{$srcConcept}' does not match the relation '{$relation}'", 500);
            }
            if (!is_null($tgtConcept) && !in_array($tgtConcept, $relation->tgtConcept->getSpecializationsIncl())) {
                throw new Exception("Provided tgt concept '{$tgtConcept}' does not match the relation '{$relation}'", 500);
            }
            
            return $relation;
        }
        
        // Else try to find the relation by its name, srcConcept and tgtConcept
        if (!is_null($srcConcept) && !is_null($tgtConcept)) {
            foreach ($this->relations as $relation) {
                if ($relation->name == $relationSignature
                        && in_array($srcConcept, $relation->srcConcept->getSpecializationsIncl())
                        && in_array($tgtConcept, $relation->tgtConcept->getSpecializationsIncl())
                  ) {
                    return $relation;
                }
            }
        }
        
        // Else
        throw new Exception("Relation '{$relationSignature}[{$srcConcept}*{$tgtConcept}]' is not defined", 500);
    }

    /**
     * Write new checksum file of generated model
     *
     * @return void
     */
    public function writeChecksumFile()
    {
        /* Earlier implementation.
        $this->logger->debug("Writing checksum file for generated Ampersand model files");

        $checksums = [];
        foreach ($this->modelFiles as $path) {
            $filename = pathinfo($path, PATHINFO_BASENAME);
            $checksums[$filename] = hash_file(self::HASH_ALGORITHM, $path);
        }

        file_put_contents($this->checksumFile, serialize($checksums));
        */

        // Now: use the hash value from generated output (created by Haskell codebase)
        file_put_contents($this->checksumFile, $this->getSetting('compiler.modelHash'));
    }

    /**
     * Verify checksums of generated model. Return true when valid, false otherwise.
     *
     * @return bool
     */
    public function verifyChecksum(): bool
    {
        $this->logger->debug("Verifying checksum for Ampersand model files");

        return (file_get_contents($this->checksumFile) === $this->getSetting('compiler.modelHash'));

        /* Earlier implementation.
        $valid = true; // assume all checksums match

        // Get stored checksums
        $checkSums = unserialize(file_get_contents($this->checksumFile));

        // Compare checksum with actual file
        foreach ($this->modelFiles as $path) {
            $filename = pathinfo($path, PATHINFO_BASENAME);
            if ($checkSums[$filename] !== hash_file(self::HASH_ALGORITHM, $path)) {
                $this->logger->warning("Invalid checksum of file '{$filename}'");
                $valid = false;
            }
        }

        return $valid;
        */
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function getFilePath(string $filename): string
    {
        if (!array_key_exists($filename, $this->modelFiles)) {
            throw new Exception("File '{$filename}' is not part of the specified Ampersand model files", 500);
        }

        return $this->modelFiles[$filename];
    }

    protected function loadFile(string $filename)
    {
        $decoder = new JsonDecode(false);
        return $decoder->decode(file_get_contents($this->getFilePath($filename)), JsonEncoder::FORMAT);
    }

    protected function getFileContent(string $filename)
    {
        static $loadedFiles = [];

        if (!array_key_exists($filename, $loadedFiles)) {
            $loadedFiles[$filename] = $this->loadFile($filename);
        }

        return $loadedFiles[$filename];
    }

    protected function getSetting(string $setting)
    {
        $settings = $this->getFileContent('settings');
        
        if (!property_exists($settings, $setting)) {
            throw new Exception("Undefined setting '{$setting}'", 500);
        }

        return $settings->$setting;
    }
}
