<?php

namespace Gtlogistics\Sap\Odata\Model;

use Gtlogistics\Sap\Odata\Enum\Type;
use Gtlogistics\Sap\Odata\Util\TypeUtils;

final readonly class SapProperty
{
    public function __construct(
        private string $name,
        private ?string $label,
        private Type $type,
        private bool $nullable,
        private bool $mandatory,
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

    public function getType(): Type
    {
        return $this->type;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $sap = $xml->attributes('sap', true);

        return new self(
            $xml['Name'] ?? null,
            $sap['label'] ?? null,
            Type::from($xml['Type']),
            TypeUtils::parseBoolean($xml['Nullable'] ?? ''),
            TypeUtils::parseBoolean($sap['mandatory'] ?? ''),
        );
    }
}
