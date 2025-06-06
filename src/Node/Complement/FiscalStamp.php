<?php

namespace Angle\CFDI\Node\Complement;

use Angle\CFDI\CFDI33;
use Angle\CFDI\CFDIException;

use Angle\CFDI\CFDINode;

use Angle\CFDI\OriginalChainGenerator;
use DateTime;
use DateTimeZone;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * @method static FiscalStamp createFromDOMNode(DOMNode $node)
 */
class FiscalStamp extends CFDINode
{
    #########################
    ##        PRESETS      ##
    #########################

    const VERSION_1_1 = "1.1";

    const NODE_NAME = "TimbreFiscalDigital";

    const NODE_NS = "tfd";
    const NODE_NS_URI = "http://www.sat.gob.mx/TimbreFiscalDigital";
    const NODE_NS_NAME = self::NODE_NS . ":" . self::NODE_NAME;
    const NODE_NS_URI_NAME = self::NODE_NS_URI . ":" . self::NODE_NAME;

    protected static $baseAttributes = [
        'xmlns:tfd' => "http://www.sat.gob.mx/TimbreFiscalDigital",
        'xmlns:xsi' => "http://www.w3.org/2001/XMLSchema-instance",
        'xsi:schemaLocation' => "http://www.sat.gob.mx/TimbreFiscalDigital http://www.sat.gob.mx/sitio_internet/cfd/TimbreFiscalDigital/TimbreFiscalDigitalv11.xsd",
    ];


    #########################
    ## PROPERTY NAME TRANSLATIONS ##
    #########################

    protected static $attributes = [
        // PropertyName => [spanish (official SAT), english]
        'version'           => [
            'keywords' => ['Version', 'version'],
            'type' => CFDINode::ATTR_REQUIRED
        ],
        'uuid'          => [
            'keywords' => ['UUID', 'uuid'],
            'type' => CFDINode::ATTR_REQUIRED
        ],
        'stampDate'        => [
            'keywords' => ['FechaTimbrado', 'stampDate'],
            'type' => CFDINode::ATTR_REQUIRED
        ],
        'certificateProviderRfc'        => [
            'keywords' => ['RfcProvCertif', 'certificateProviderRfc'],
            'type' => CFDINode::ATTR_REQUIRED
        ],
        'legend'        => [
            'keywords' => ['Leyenda', 'legend'],
            'type' => CFDINode::ATTR_OPTIONAL
        ],
        'cfdiSignature'        => [
            'keywords' => ['SelloCFD', 'cfdiSignature'],
            'type' => CFDINode::ATTR_REQUIRED
        ],
        'satCertificateNumber'        => [
            'keywords' => ['NoCertificadoSAT', 'satCertificateNumber'],
            'type' => CFDINode::ATTR_REQUIRED
        ],
        'satSignature'        => [
            'keywords' => ['SelloSAT', 'satSignature'],
            'type' => CFDINode::ATTR_REQUIRED
        ],
    ];

    protected static $children = [];



    #########################
    ##      PROPERTIES     ##
    #########################

    /**
     * @var string
     */
    protected $version = self::VERSION_1_1;

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var DateTime
     */
    protected $stampDate;

    /**
     * @var string
     */
    protected $certificateProviderRfc;

    /**
     * @var string
     */
    protected $legend;

    /**
     * @var string
     */
    protected $cfdiSignature;

    /**
     * @var string
     */
    protected $satCertificateNumber;

    /**
     * @var string
     */
    protected $satSignature;


    #########################
    ##     CONSTRUCTOR     ##
    #########################

    // constructor implemented in the CFDINode abstract class

    /**
     * @param DOMNode[]
     * @throws CFDIException
     */
    public function setChildrenFromDOMNodes(array $children): void
    {
        // void
    }


    #########################
    ## CFDI NODE TO DOM TRANSLATION
    #########################

    public function toDOMElement(DOMDocument $dom): DOMElement
    {
        $node = $dom->createElementNS(self::NODE_NS_URI, self::NODE_NS_NAME);

        foreach ($this->getAttributes() as $attr => $value) {
            $node->setAttribute($attr, $value);
        }

        // no child nodes

        return $node;
    }


    #########################
    ## TFD TO XML
    #########################

    public function toDOMDocument(): DOMDocument
    {
        $dom = new \DOMDocument('1.0','UTF-8');
        $dom->preserveWhiteSpace = false;

        $tfdNode = $this->toDOMElement($dom);
        $dom->appendChild($tfdNode);

        return $dom;
    }

    public function toXML(): string
    {
        return $this->toDOMDocument()->saveXML();
    }


    #########################
    ## VALIDATION
    #########################

    public function validate(): bool
    {
        // TODO: implement the full set of validation, including type and Business Logic

        return true;
    }

    /**
     * Builds the Original Chain Sequence for the Fiscal Stamp
     * Returns false on failure
     *
     * This method is meant for simple representations of the Chain Sequence and should not be used for
     * certificate signatures or validations.
     *
     * For the most correct results, use the OriginalChainGenerator service instead
     * @see OriginalChainGenerator
     *
     * @return string|false
     */
    public function getChainSequence(): string
    {
        $items = [];

        $items[] = $this->version;
        $items[] = $this->uuid;

        if (!($this->stampDate instanceof DateTime)) {
            //throw new CFDIException('StampDate is not a valid DateTime');
            return false;
        }

        $items[] = $this->stampDate->format(CFDINode::DATETIME_FORMAT);
        $items[] = $this->certificateProviderRfc;

        if ($this->legend) {
            $items[] = $this->legend;
        }

        $items[] = $this->cfdiSignature;
        $items[] = $this->satCertificateNumber;

        $items = array_map('Angle\CFDI\Node\CFDI40\CFDI40::cleanWhitespace', $items);

        return '||' . implode('|', $items) . '||';
    }


    #########################
    ## GETTERS AND SETTERS ##
    #########################

    /**
     * @return string
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return FiscalStamp
     */
    public function setVersion(?string $version): self
    {
        // Note: this value is fixed, it cannot be set or changed
        //$this->version = $version;
        return $this;
    }

    /**
     * @return string
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return FiscalStamp
     */
    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStampDate(): ?DateTime
    {
        return $this->stampDate;
    }

    /**
     * @param DateTime|string $rawDate
     * @throws CFDIException
     * @return FiscalStamp
     */
    public function setStampDate($rawDate): self
    {
        if ($rawDate instanceof DateTime) {
            $this->stampDate = $rawDate;
        }

        // sample format: 2019-09-06T10:09:46
        // TODO: We are assuming that dates ARE in Mexico City's timezone
        try {
            $tz = new DateTimeZone(CFDINode::DATETIME_TIMEZONE);
            $date = DateTime::createFromFormat(CFDINode::DATETIME_FORMAT, $rawDate, $tz);
        } catch (\Exception $e) {
            throw new CFDIException('Raw date string is in invalid format, cannot parse stamp date');
        }

        $this->stampDate = $date;

        return $this;
    }

    /**
     * @return string
     */
    public function getCertificateProviderRfc(): ?string
    {
        return $this->certificateProviderRfc;
    }

    /**
     * @param string $certificateProviderRfc
     * @return FiscalStamp
     */
    public function setCertificateProviderRfc(?string $certificateProviderRfc): self
    {
        $this->certificateProviderRfc = $certificateProviderRfc;
        return $this;
    }

    /**
     * @return string
     */
    public function getLegend(): ?string
    {
        return $this->legend;
    }

    /**
     * @param string $legend
     * @return FiscalStamp
     */
    public function setLegend(?string $legend): self
    {
        $this->legend = $legend;
        return $this;
    }

    /**
     * @return string
     */
    public function getCfdiSignature(): ?string
    {
        return $this->cfdiSignature;
    }

    /**
     * @param string $cfdiSignature
     * @return FiscalStamp
     */
    public function setCfdiSignature(?string $cfdiSignature): self
    {
        $this->cfdiSignature = $cfdiSignature;
        return $this;
    }

    /**
     * @return string
     */
    public function getSatCertificateNumber(): ?string
    {
        return $this->satCertificateNumber;
    }

    /**
     * @param string $satCertificateNumber
     * @return FiscalStamp
     */
    public function setSatCertificateNumber(?string $satCertificateNumber): self
    {
        $this->satCertificateNumber = $satCertificateNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getSatSignature(): ?string
    {
        return $this->satSignature;
    }

    /**
     * @param string $satSignature
     * @return FiscalStamp
     */
    public function setSatSignature(?string $satSignature): self
    {
        $this->satSignature = $satSignature;
        return $this;
    }
}