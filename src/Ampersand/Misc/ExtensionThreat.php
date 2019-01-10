<?php

/*
 * This file is part of the Ampersand backend framework.
 *
 */

namespace Ampersand\Misc;

use Ampersand\AmpersandApp;

/**
 * Use this threat in Extension classes to have access to instance of AmpersandApp and more
 *
 * @author Michiel Stornebrink (https://github.com/Michiel-s)
 *
 */
trait ExtensionThreat
{
    /**
     * Reference to AmpersandApp instance
     *
     * @var \Ampersand\AmpersandApp
     */
    public $ampersandApp;

    /**
     * Setter method for $ampersandApp
     *
     * @param \Ampersand\AmpersandApp $app
     * @return void
     */
    public function setAmpersandApp(AmpersandApp $app): void
    {
        $this->ampersandApp = $app;
    }
}
