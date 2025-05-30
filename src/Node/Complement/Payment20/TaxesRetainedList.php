<?php

namespace Angle\CFDI\Node\Complement\Payment20;

use Angle\CFDI\CFDIException;
use Angle\CFDI\CFDINode;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * @method static TaxesRetainedList createFromDOMNode(DOMNode $node)
 */
class TaxesRetainedList extends CFDINode
{
    #########################
    ##        PRESETS      ##
    #########################

    public const NODE_NAME = "RetencionesP";

    public const NODE_NS = "pago20";
    public const NODE_NS_URI = "http://www.sat.gob.mx/Pagos20";
    public const NODE_NS_NAME = self::NODE_NS . ":" . self::NODE_NAME;
    public const NODE_NS_URI_NAME = self::NODE_NS_URI . ":" . self::NODE_NAME;

    protected static array $baseAttributes = [];


    #########################
    ## PROPERTY NAME TRANSLATIONS ##
    #########################

    protected static $attributes = [];

    protected static $children = [
        'retentions' => [
            'keywords' => ['RetencionP', 'retentions'],
            'class' => TaxesRetained::class,
            'type' => CFDINode::CHILD_ARRAY,
        ],
    ];


    #########################
    ##      PROPERTIES     ##
    #########################


    // CHILDREN NODES
    /**
     * @var TaxesRetained[]
     */
    protected $retentions = [];


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
        foreach ($children as $node) {
            if ($node instanceof DOMText) {
                // TODO: we are skipping the actual text inside the Node.. is this useful?
                continue;
            }

            switch ($node->localName) {
                case TaxesRetained::NODE_NAME:
                    $retention = TaxesRetained::createFromDomNode($node);
                    $this->addRetention($retention);
                    break;
                default:
                    throw new CFDIException(sprintf("Unknown children node '%s' in %s", $node->nodeName, self::NODE_NS_NAME));
            }
        }
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

        // Retentions node (array)
        foreach ($this->retentions as $retention) {
            $retentionNode = $retention->toDOMElement($dom);
            $node->appendChild($retentionNode);
        }

        return $node;
    }


    #########################
    ## VALIDATION
    #########################

    public function validate(): bool
    {
        // TODO: implement the full set of validation, including type and Business Logic

        return true;
    }


    #########################
    ## GETTERS AND SETTERS ##
    #########################

    // none


    #########################
    ## CHILDREN
    #########################

    /**
     * @return TaxesRetained[]
     */
    public function getRetentions(): ?array
    {
        return $this->retentions;
    }

    /**
     * @param TaxesRetained $retention
     * @return TaxesRetainedList
     */
    public function addRetention(TaxesRetained $retention): self
    {
        $this->retentions[] = $retention;
        return $this;
    }

    /**
     * @param TaxesRetained[] $retentions
     * @return TaxesRetainedList
     */
    public function setRetentions(array $retentions): self
    {
        $this->retentions = $retentions;
        return $this;
    }

}