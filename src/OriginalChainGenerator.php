<?php

namespace Angle\CFDI;

use Angle\CFDI\Utility\PathUtility;

use DOMDocument;
use Genkgo\Xsl\Callback\FunctionCollection;
use Genkgo\Xsl\TransformationContext;
use Genkgo\Xsl\Transpiler;
use Genkgo\Xsl\Util\TransformerCollection;
use LibXMLError;

use Genkgo\Xsl\Cache\ArrayCache;
use Genkgo\Xsl\Cache\NullCache;
use Genkgo\Xsl\ProcessorFactory;
use Genkgo\Xsl\XsltProcessor;
use Genkgo\Xsl\Exception\TransformationException;

use XSLTProcessor as PhpXsltProcessor;

use Angle\CFDI\CFDIInterface;
use Angle\CFDI\Node\CFDI33\CFDI33;
use Angle\CFDI\Node\CFDI40\CFDI40;

use Angle\CFDI\Node\Complement\FiscalStamp;

class OriginalChainGenerator
{
    // Relative to the project directory
    const XSLT_RESOURCES_DIR = '/resources/xslt-processor/';

    // This stylesheet file should exist inside the resources directory
    const CFDI_3_3_STYLESHEET   = 'CFDI_3_3.xslt';
    const CFDI_4_0_STYLESHEET   = 'CFDI_4_0.xslt';
    const TFD_1_1_STYLESHEET    = 'TFD_1_1.xslt';

    /**
     * Library directory
     * @var string
     */
    private $resourceDir;

    /**
     * Validations array, in the format: [{type: string, success: true/false, message: string}]
     * @var array
     */
    private $validations = [];

    /**
     * Formatted libxml Error details
     * @var array
     */
    private $errors = [];


    public function __construct()
    {
        $libDir = PathUtility::join(__DIR__, '/../');
        $this->resourceDir = realpath(PathUtility::join($libDir, self::XSLT_RESOURCES_DIR));

        // Create the Stream Wrapper to manipulate our XSLT stylesheet file
        if (in_array(XsltStreamWrapper::PROTOCOL, stream_get_wrappers())) {
            // the stream was previously registered, we'll destroy it and recreate it
            stream_wrapper_unregister(XsltStreamWrapper::PROTOCOL);
        }
        stream_wrapper_register(XsltStreamWrapper::PROTOCOL, XsltStreamWrapper::class, STREAM_IS_URL);

        // Configure our XSLT Stream Wrapper
        XsltStreamWrapper::$RESOURCE_DIR = PathUtility::join($libDir, self::XSLT_RESOURCES_DIR);
    }

    public function __destruct()
    {
        // we'll only unregister the stream if we already had it online
        if (in_array(XsltStreamWrapper::PROTOCOL, stream_get_wrappers())) {
            // the stream was previously registered, we'll destroy it and recreate it
            stream_wrapper_unregister(XsltStreamWrapper::PROTOCOL);
        }
    }

    /**
     * Generate the OriginalChain string for a given CFDI object
     * Returns false on failure
     * @param CFDIInterface $cfdi
     * @return string|false
     */
    public function generateForCFDI(CFDIInterface $cfdi)
    {
        // Check that the version is correct
        if ($cfdi->getVersion() == CFDI33::VERSION_3_3) {
            $versionStylesheet = self::CFDI_3_3_STYLESHEET;
            $versionString = 'CFDIv3.3';

            $this->validations[] = [
                'type' => 'chain:cfdi',
                'success' => true,
                'message' => 'CFDI OriginalChainGenerator for ' . $versionString,
            ];


        } elseif ($cfdi->getVersion() == CFDI40::VERSION_4_0) {
            $versionStylesheet = self::CFDI_4_0_STYLESHEET;
            $versionString = 'CFDIv4.0';

            $this->validations[] = [
                'type' => 'chain:cfdi',
                'success' => true,
                'message' => 'CFDI OriginalChainGenerator for ' . $versionString,
            ];

        } else {
            $this->validations[] = [
                'type' => 'chain:cfdi',
                'success' => false,
                'message' => 'CFDI OriginalChainGenerator unsupported CFDI version: ' . $cfdi->getVersion(),
            ];

            return false;
        }

        libxml_use_internal_errors(true);

        // Initialize the XSLT processor
        // Option 1: Genkgo XSLT Processor with Cache
        //$factory = new ProcessorFactory(new ArrayCache());
        //$processor = $factory->newProcessor();

        // Option 2: Genkgo XSLT Processor without cache
        //$processor = new XsltProcessor(new NullCache());

        // Option 3: Built-in PHP XSLT Processor, using a Transpiled XSLT generated with Genkgo's library
        $processor = new PhpXsltProcessor();

        // Load the XSLT transformation rules as a DOMDocument
        $stylesheet = XsltStreamWrapper::PROTOCOL . '://' . $versionStylesheet;

        $xslt = new DOMDocument();
        $r = $xslt->load($stylesheet);

        if ($r === false) {
            $this->errors = array_merge($this->errors, $this->libxmlErrors());

            $this->validations[] = [
                'type' => 'chain:cfdi',
                'success' => false,
                'message' => sprintf('Failed to load %s XLST file: %s', $versionString),
            ];

            return false;
        }

        $r = $processor->importStyleSheet($xslt);

        if ($r === false) {
            $this->errors = array_merge($this->errors, $this->libxmlErrors());

            $this->validations[] = [
                'type' => 'chain:cfdi',
                'success' => false,
                'message' => sprintf('Failed to load %s XLST into Processor', $versionString),

            ];

            return false;
        }

        // Everything's loaded, now try to generate the chain
        try {
            if ($cfdi->getOriginalXml() !== null) {
                // if the CFDI has a copy of its original XML string, use that instead of the self-reproduced one
                $xml = new DOMDocument();
                $xml->loadXML($cfdi->getOriginalXml());
                $chain = $processor->transformToXML($xml);
            } else {
                $chain = $processor->transformToXML($cfdi->toDOMDocument());
            }
        } catch (\Exception $e) {
            $this->errors = array_merge($this->errors, $this->libxmlErrors());

            $this->validations[] = [
                'type' => 'chain:cfdi',
                'success' => false,
                'message' => sprintf('%s XLST %s', $versionString, $e->getMessage()),
            ];

            return false;
        }

        if ($chain === null) return false;

        return $chain;
    }

    /**
     * Generate the OriginalChain string for a given FiscalStamp object
     * Returns false on failure
     * @param FiscalStamp $tfd
     * @return string|false
     */
    public function generateForTFD(FiscalStamp $tfd)
    {
        // Check that the version is correct
        if ($tfd->getVersion() != FiscalStamp::VERSION_1_1) {
            $this->validations[] = [
                'type' => 'chain:tfd',
                'success' => false,
                'message' => 'TFD OriginalChainGenerator only supports TFDv1.1',
            ];

            return false;
        }

        libxml_use_internal_errors(true);

        // Initialize the XSLT processor
        // Option 1: Genkgo XSLT Processor with Cache
        //$factory = new ProcessorFactory(new ArrayCache());
        //$processor = $factory->newProcessor();

        // Option 2: Genkgo XSLT Processor without cache
        //$processor = new XsltProcessor(new NullCache());

        // Option 3: Built-in PHP XSLT Processor, using a Transpiled XSLT generated with Genkgo's library
        $processor = new PhpXsltProcessor();

        // Load the XSLT transformation rules as a DOMDocument
        $stylesheet = XsltStreamWrapper::PROTOCOL . '://' . self::TFD_1_1_STYLESHEET;

        $xslt = new DOMDocument();
        $r = $xslt->load($stylesheet);

        if ($r === false) {
            $this->errors = array_merge($this->errors, $this->libxmlErrors());

            $this->validations[] = [
                'type' => 'chain:tfd',
                'success' => false,
                'message' => 'Failed to load TFDv1.1 XLST file',
            ];

            return false;
        }

        $r = $processor->importStyleSheet($xslt);

        if ($r === false) {
            $this->errors = array_merge($this->errors, $this->libxmlErrors());

            $this->validations[] = [
                'type' => 'chain:tfd',
                'success' => false,
                'message' => 'Failed to load TFDv1.1 XLST into Processor',
            ];

            return false;
        }

        // Everything's loaded, now try to generate the chain
        try {
            $chain = $processor->transformToXML($tfd->toDOMDocument());
        } catch (\Exception $e) {
            $this->errors = array_merge($this->errors, $this->libxmlErrors());

            $this->validations[] = [
                'type' => 'chain:tfd',
                'success' => false,
                'message' => 'TFD1.1 XLST ' . $e->getMessage(),
            ];

            return false;
        }

        if ($chain === null) return false;

        return $chain;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function getValidations()
    {
        return $this->validations;
    }

    /**
     * @return array
     */
    private function libxmlErrors()
    {
        $errors = libxml_get_errors();
        $result = [];
        foreach ($errors as $error) {
            $result[] = sprintf("Error %d (Line:%d): %s", $error->code, $error->line, trim($error->message));
        }
        libxml_clear_errors();
        return $result;
    }
}