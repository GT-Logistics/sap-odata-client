<?php

namespace Gtlogistics\Sap\Odata\Model;

final readonly class SapProperty
{
    public function __construct(
        private string $name,
        private ?string $label,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $sapAttributes = $xml->attributes('sap', true);

        return new self(
            $xml['Name'] ?? null,
            $sapAttributes['label'] ?? null,
        );
    }
}
