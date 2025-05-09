<?php

namespace Gtlogistics\Sap\Odata\Model;

final readonly class SapMetadata
{
    /**
     * @var array<string, SapEntity> $entities
     */
    private array $entities;

    /**
     * @param SapEntity[] $reports
     */
    public function __construct(
        iterable $reports,
    ) {
        $this->entities = iterator_to_array(\iter\flatMap(
            static fn (SapEntity $entity) => yield $entity->getName() => $entity,
            $reports,
        ));
    }

    /**
     * @return SapEntity
     */
    public function getEntity(string $entity): SapEntity
    {
        return $this->entities[$entity];
    }

    public static function fromXml(\SimpleXMLElement $metadata): self
    {
        $metadata->registerXPathNamespace('edmx', 'http://schemas.microsoft.com/ado/2007/06/edmx');
        $metadata->registerXPathNamespace('m', 'http://schemas.microsoft.com/ado/2007/08/dataservices/metadata');
        $metadata->registerXPathNamespace('edm', 'http://schemas.microsoft.com/ado/2008/09/edm');

        $entitySets = $metadata->xpath('//edm:EntitySet');
        $reports = \iter\map(static function (\SimpleXMLElement $entitySet) use ($metadata) {
            $name = $entitySet['Name'];
            [, $entityType] = explode('.', $entitySet['EntityType']);
            $entity = $metadata->xpath("//edm:EntityType[@Name='$entityType']")[0];

            return SapEntity::fromXml($name, $entity);
        }, $entitySets);

        return new self($reports);
    }
}
