<?php

namespace Angle\CFDI\Node\Complement\Payment20;

use Angle\CFDI\CFDIException;
use Angle\CFDI\CFDINode;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * @method static TaxesTransferredList createFromDOMNode(DOMNode $node)
 */
class TaxesTransferredList extends CFDINode
{
    #########################
    ##        PRESETS      ##
    #########################

    public const NODE_NAME = "TrasladosP";

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
        'transfers' => [
            'keywords' => ['TrasladoP', 'transfers'],
            'class' => TaxesRetained::class,
            'type' => CFDINode::CHILD_ARRAY,
        ],
    ];


    #########################
    ##      PROPERTIES     ##
    #########################


    // CHILDREN NODES
    /**
     * @var TaxesTransferred[]
     */
    protected $transfers = [];


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
                case TaxesTransferred::NODE_NAME:
                    $transfer = TaxesTransferred::createFromDomNode($node);
                    $this->addTransfer($transfer);
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

        // Transfers node (array)
        foreach ($this->transfers as $transfer) {
            $transferNode = $transfer->toDOMElement($dom);
            $node->appendChild($transferNode);
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
     * @return TaxesTransferred[]
     */
    public function getTransfers(): ?array
    {
        return $this->transfers;
    }

    /**
     * @param TaxesTransferred $transfer
     * @return TaxesTransferredList
     */
    public function addTransfer(TaxesTransferred $transfer): self
    {
        $this->transfers[] = $transfer;
        return $this;
    }

    /**
     * @param TaxesTransferred[] $transfers
     * @return TaxesTransferredList
     */
    public function setTransfers(array $transfers): self
    {
        $this->transfers = $transfers;
        return $this;
    }

}