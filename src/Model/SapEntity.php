<?php

namespace Gtlogistics\Sap\Odata\Model;

use Gtlogistics\Sap\Odata\Util\IterableUtils;

final readonly class SapEntity
{
    /**
     * @var SapProperty[]
     */
    private array $properties;

    /**
     * @param iterable<SapProperty> $properties
     */
    public function __construct(
        private string $name,
        private ?string $label,
        iterable $properties,
    ) {
        $this->properties = iterator_to_array($properties);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @return SapProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public static function fromXml(string $name, \SimpleXMLElement $entity): self
    {
        $entity->registerXPathNamespace('edm', 'http://schemas.microsoft.com/ado/2008/09/edm');

        $sapAttributes = $entity->attributes('sap', true);
        $properties = IterableUtils::map(SapProperty::fromXml(...), $entity->xpath('edm:Property'));

        return new self(
            $name,
            $sapAttributes['label'] ?? null,
            $properties,
        );
    }
}
