<?php

namespace Kavinsky\CloverMerge;

/**
 * Represents a single lines coverage information.
 */
class Line
{
    /**
     * Number of hits on this line.
     *
     * @var int
     */
    private $count;

    /**
     * Other properties on the line.
     * E.g. name, visibility, complexity, crap.
     *
     * @var \Ds\Map $properties
     */
    private $properties;

    /**
     * Initialise with a hit count.
     *
     * @param \Ds\Map $properties Any other properties on the XML node.
     * @param integer $count
     */
    public function __construct(\Ds\Map $properties, int $count = 0)
    {
        $this->count = $count;
        $this->properties = $properties;
    }

    /**
     * Construct from XML.
     *
     * @param \SimpleXMLElement $xml
     * @return Line
     * @throws ParseException
     */
    public static function fromXML(\SimpleXMLElement $xml) : Line
    {
        $properties = new \Ds\Map($xml->attributes());
        $properties->apply(function ($_, $value) {
            return (string) $value;
        });
        if (!$properties->hasKey('count')) {
            throw new ParseException('Unable to parse line, missing count attribute.');
        }
        return new Line($properties, (int)$properties->remove('count'));
    }

    /**
     * Produce an XML representation.
     *
     * @param \DomDocument $document The parent document.
     * @return \DOMElement
     */
    public function toXml(
        \DomDocument $document
    ) : \DOMElement {
        $xml_line = $document->createElement('line');
        foreach ($this->properties as $key => $value) {
            $xml_line->setAttribute($key, $value);
        }
        $xml_line->setAttribute('count', (string)$this->count);
        return $xml_line;
    }

    /**
     * Merge another line with this one.
     *
     * @param Line $other
     * @return void
     */
    public function merge($other) : void
    {
        // Merge in this order so that the fist set overrides the second.
        $this->properties = $other->getProperties()->merge($this->properties);
        $this->count += $other->getCount();
    }

    /**
     * Get the hit count.
     *
     * @return integer
     */
    public function getCount() : int
    {
        return $this->count;
    }

    /**
     * Get the other properties.
     *
     * @return \Ds\Map
     */
    public function getProperties() : \Ds\Map
    {
        return $this->properties;
    }
}
