<?php

declare(strict_types=1);

namespace ElementaryFramework\LightQL\Sessions;

use ElementaryFramework\LightQL\Attributes\Column;
use ElementaryFramework\LightQL\Attributes\ManyToMany;
use ElementaryFramework\LightQL\Attributes\ManyToOne;
use ElementaryFramework\LightQL\Attributes\NamedQuery as NamedQueryAttribute;
use ElementaryFramework\LightQL\Attributes\OneToMany;
use ElementaryFramework\LightQL\Attributes\OneToOne;
use ElementaryFramework\LightQL\Attributes\PersistenceUnit as PersistenceUnitAttribute;
use ElementaryFramework\LightQL\Attributes\Table;
use ElementaryFramework\LightQL\Entities\EntityManager;
use ElementaryFramework\LightQL\Entities\IEntity;
use ElementaryFramework\LightQL\Entities\IPrimaryKey;
use ElementaryFramework\LightQL\Entities\Relation;
use ElementaryFramework\LightQL\Enums\FetchMode;
use ElementaryFramework\LightQL\Exceptions\EntityException;
use ElementaryFramework\LightQL\Exceptions\FacadeException;
use ElementaryFramework\LightQL\Exceptions\LightQLException;
use ElementaryFramework\LightQL\Exceptions\PersistenceUnitException;
use ElementaryFramework\LightQL\Exceptions\QueryException;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;
use ElementaryFramework\LightQL\Query\NamedQuery;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Base class for entity facades.
 *
 * @template TEntity of IEntity
 *
 * @implements IFacade<TEntity>
 */
abstract class Facade implements IFacade
{
    /**
     * The entity manager of this facade.
     */
    protected EntityManager $entityManager;

    /**
     * The entity class name managed by this facade.
     */
    private Table $entityTableAnnotation;

    /**
     * @var class-string<TEntity> The entity class managed by this facade.
     */
    private string $entityClass;

    /**
     * Facade constructor.
     *
     * @param class-string<TEntity>|TEntity $class The entity class name managed by this facade.
     *
     * @throws FacadeException When the entity class or object doesn't have a Table attribute.
     * @throws FacadeException When the "entityManager" property of this Facade doesn't have a PersistenceUnit attribute.
     * @throws FacadeException When the entity class or object doesn't inherit from the Entity class.
     * @throws ReflectionException
     * @throws LightQLException
     * @throws PersistenceUnitException
     */
    public function __construct(string|IEntity $class)
    {
        $propAttributes = new ReflectionProperty($this, 'entityManager')
            ->getAttributes(PersistenceUnitAttribute::class);

        if (empty($propAttributes)) {
            throw new FacadeException('Cannot create the entity facade. The property "entityManager" has no PersistenceUnit attribute.');
        }

        $reflection = new ReflectionClass($class);
        $classAttributes = $reflection->getAttributes(Table::class);

        if (empty($classAttributes)) {
            throw new FacadeException("Cannot create the entity facade. The managed entity is missing the Table attribute.");
        }

        if (!is_subclass_of($class, IEntity::class)) {
            throw new FacadeException("Unable to create a facade. The provided entity class doesn't inherit from the Entity class.");
        }

        $this->entityClass = $reflection->getName();

        /** @var Table $entityTableAnnotation */
        $entityTableAnnotation = $classAttributes[0]->newInstance();
        $this->entityTableAnnotation = $entityTableAnnotation;

        /** @var PersistenceUnitAttribute $persistenceUnit */
        $persistenceUnit = $propAttributes[0]->newInstance();
        $this->entityManager = new EntityManager(PersistenceUnit::create($persistenceUnit->name));
    }

    /**
     * {@inheritdoc}
     */
    public function create(IEntity &$entity): void
    {
        $this->ensureEntityType($entity);

        $this->entityManager->persist($entity);

        $columns = $entity->columns;
        foreach ($columns as $property => $column) {
            if ($column->isOneToMany) {
                $this->handleRelation($entity, $property, $this->fetchOneToMany(...));
            } elseif ($column->isManyToOne) {
                $this->handleRelation($entity, $property, $this->fetchManyToOne(...));
            } elseif ($column->isManyToMany) {
                $this->handleRelation($entity, $property, $this->fetchManyToMany(...));
            } elseif ($column->isOneToOne) {
                $this->handleRelation($entity, $property, $this->fetchOneToOne(...));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(IEntity &$entity): void
    {
        $this->ensureEntityType($entity);

        $this->entityManager->merge($entity);
    }

    /**
     * {@inheritdoc }
     */
    public function delete(IEntity &$entity): void
    {
        $this->ensureEntityType($entity);

        $this->entityManager->merge($entity);
        $this->entityManager->delete($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function retrieve(int|string|IPrimaryKey $id): IEntity
    {
        return $this->parseRow(
            $this->entityManager->find($this->entityClass, $id),
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function list(): array
    {
        $rawEntities = $this->entityManager
            ->getLightQL()
            ->builder()
            ->from($this->entityTableAnnotation->table)
            ->select()
            ->execute()
            ->fetchAll();

        return $this->parseRows($rawEntities);
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function range(int $start, int $length): array
    {
        $rawEntities = $this->entityManager
            ->getLightQL()
            ->builder()
            ->from($this->entityTableAnnotation->table)
            ->limit($start, $length)
            ->select()
            ->execute()
            ->fetchAll();

        return $this->parseRows($rawEntities);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->entityManager
            ->getLightQL()
            ->builder()
            ->from($this->entityTableAnnotation->table)
            ->count();
    }

    /**
     * Get a named query.
     *
     * @param string $name The name of the query.
     *
     * @return NamedQuery
     *
     * @throws FacadeException
     * @throws ReflectionException
     */
    public function getNamedQuery(string $name): NamedQuery
    {
        $reflection = new ReflectionClass($this->entityClass);
        $attributes = array_map(fn(ReflectionAttribute $attribute) => $attribute->newInstance(), $reflection->getAttributes(NamedQueryAttribute::class));

        if (empty($attributes)) {
            throw new FacadeException("The {$reflection->name} class has no NamedQuery attribute.");
        }

        $query = null;

        /** @var NamedQueryAttribute $namedQuery */
        foreach ($attributes as $namedQuery) {
            if ($namedQuery->name === $name) {
                $query = $namedQuery->query;
                break;
            }
        }

        if ($query === null) {
            throw new FacadeException("The {$reflection->name} class has no NamedQuery attribute with the name {$name}.");
        }

        return new NamedQuery($this->entityManager)
            ->setEntity($this->entityClass)
            ->setQuery($query);
    }

    /**
     * Fetch data for a many-to-many relation.
     *
     * @param TEntity $entity The managed entity.
     * @param string $property The property in many-to-many relation.
     * @return list<TEntity>
     *
     * @throws EntityException
     * @throws LightQLException
     * @throws ReflectionException
     * @throws QueryException
     */
    private function fetchManyToMany(IEntity $entity, string $property): array
    {
        $propReflection = new ReflectionProperty($entity, $property);

        /** @var ManyToMany $manyToMany */
        $manyToMany = $propReflection->getAttributes(ManyToMany::class)[0]->newInstance();
        /** @var Column $column */
        $column = $propReflection->getAttributes(Column::class)[0]->newInstance();

        $mappedPropertyName = null;

        /** @var IEntity $referencedEntity */
        $referencedEntity = new $manyToMany->entity;
        foreach ($referencedEntity->columns as $p => $c) {
            if ($c->isManyToMany) {
                /** @var ManyToMany $mappedManyToMany */
                $mappedManyToMany = new ReflectionProperty($referencedEntity, $p)->getAttributes(ManyToMany::class)[0]->newInstance();
                if ($mappedManyToMany->pivotTable === $manyToMany->pivotTable) {
                    $mappedPropertyName = $p;
                    break;
                }
            }
        }
        unset($referencedEntity);

        if ($mappedPropertyName === null) {
            throw new EntityException("Unable to find a suitable property with a ManyToMany attribute in the entity \"{$manyToMany->entity}\".");
        }

        /** @var ManyToMany $mappedPropertyManyToManyAnnotation */
        $mappedPropertyManyToManyAnnotation = new ReflectionProperty($manyToMany->entity, $mappedPropertyName)->getAttributes(ManyToMany::class)[0]->newInstance();
        /** @var Column $mappedPropertyColumnAnnotation */
        $mappedPropertyColumnAnnotation = new ReflectionProperty($manyToMany->entity, $mappedPropertyName)->getAttributes(Column::class)[0]->newInstance();
        /** @var Table $referencedEntityAnnotation */
        $referencedEntityAnnotation = new ReflectionClass($manyToMany->entity)->getAttributes(Table::class)[0]->newInstance();

        $className = $manyToMany->entity;

        return array_map(
            fn($item) => new $className($item),
            $this->entityManager
                ->getLightQL()
                ->builder()
                ->from($manyToMany->pivotTable)
                ->where([
                    "{$manyToMany->pivotTable}.{$manyToMany->foreignColumn}" => $entity->get($column->name)
                ])
                ->leftJoin(
                    $referencedEntityAnnotation->table,
                    "{$manyToMany->pivotTable}.{$mappedPropertyManyToManyAnnotation->foreignColumn} = {$referencedEntityAnnotation->table}.{$mappedPropertyColumnAnnotation->name}"
                )
                ->select()
                ->execute()
                ->fetchAll()
        );
    }

    /**
     * Fetch data for a one-to-many relation.
     *
     * @param TEntity $entity The managed entity.
     * @param string $property The property in one-to-many relation.
     * @return TEntity|null
     *
     * @throws LightQLException
     * @throws ReflectionException
     * @throws QueryException
     */
    private function fetchOneToMany(IEntity $entity, string $property): IEntity|null
    {
        /** @var OneToMany $oneToMany */
        $oneToMany = new ReflectionProperty($entity, $property)->getAttributes(OneToMany::class)[0]->newInstance();
        /** @var Column $column */
        $column = new ReflectionProperty($entity, $property)->getAttributes(Column::class)[0]->newInstance();
        /** @var Table $referencedEntityAnnotation */
        $referencedEntityAnnotation = new ReflectionClass($oneToMany->entity)->getAttributes(Table::class)[0]->newInstance();

        $mappedPropertyName = $oneToMany->mappedBy;

        /** @var ManyToOne $mappedPropertyManyToOneAnnotation */
        $mappedPropertyManyToOneAnnotation = new ReflectionProperty($oneToMany->entity, $mappedPropertyName)->getAttributes(ManyToOne::class)[0]->newInstance();

        $result = $this->entityManager
            ->getLightQL()
            ->builder()
            ->from($referencedEntityAnnotation->table)
            ->where([
                "{$referencedEntityAnnotation->table}.{$mappedPropertyManyToOneAnnotation->localColumn}" => $entity->get($column->name)
            ])
            ->select("{$referencedEntityAnnotation->table}.*")
            ->execute()
            ->fetchFirst();

        return $result !== null ? new ($oneToMany->entity)($result) : null;
    }

    /**
     * Fetch data for a many-to-one relation.
     *
     * @param TEntity $entity The managed entity.
     * @param string $property The property in many-to-one relation.
     * @return list<TEntity>
     *
     * @throws LightQLException
     * @throws EntityException
     * @throws ReflectionException
     * @throws QueryException
     */
    private function fetchManyToOne(IEntity &$entity, string $property): array
    {
        /** @var ManyToOne $manyToOne */
        $manyToOne = new ReflectionProperty($entity, $property)->getAttributes(ManyToOne::class)[0]->newInstance();
        /** @var Column $column */
        $column = new ReflectionProperty($entity, $property)->getAttributes(Column::class)[0]->newInstance();
        /** @var Table $referencedEntityAnnotation */
        $referencedEntityAnnotation = new ReflectionClass($manyToOne->entity)->getAttributes(Table::class)[0]->newInstance();

        $mappedPropertyName = $this->resolveMappedPropertyName($manyToOne->entity, "OneToMany", $manyToOne->referencedColumn);

        if ($mappedPropertyName === null) {
            throw new EntityException("Unable to find a suitable property with @oneToMany annotation in the entity \"{$manyToOne->entity}\".");
        }

        $className = $manyToOne->entity;

        return array_map(
            function ($item) use ($className, &$entity, $mappedPropertyName) {
                $e = new $className($item);
                $e->{$mappedPropertyName} = &$entity;
                return $e;
            },
            $this->entityManager
                ->getLightQL()
                ->builder()
                ->from($referencedEntityAnnotation->table)
                ->where([
                    "{$referencedEntityAnnotation->table}.{$manyToOne->referencedColumn}" => $entity->get($column->name)
                ])
                ->select("{$referencedEntityAnnotation->table}.*")
                ->execute()
                ->fetchAll()
        );
    }

    /**
     * Fetch data for a one-to-one relation.
     *
     * @param TEntity $entity The managed entity.
     * @param string $property The property in one-to-one relation.
     * @return TEntity|null
     *
     * @throws LightQLException
     * @throws ReflectionException
     * @throws EntityException
     * @throws QueryException
     */
    private function fetchOneToOne(IEntity &$entity, string $property): IEntity|null
    {
        /** @var OneToOne $oneToOne */
        $oneToOne = new ReflectionProperty($entity, $property)->getAttributes(OneToOne::class)[0]->newInstance();
        /** @var Column $column */
        $column = new ReflectionProperty($entity, $property)->getAttributes(Column::class)[0]->newInstance();
        /** @var Table $referencedEntityAnnotation */
        $referencedEntityAnnotation = new ReflectionClass($oneToOne->entity)->getAttributes(Table::class)[0]->newInstance();

        $mappedPropertyName = $oneToOne->mappedBy ?? $this->resolveMappedPropertyName($oneToOne->entity, "OneToOne", $oneToOne->referencedColumn);

        if ($mappedPropertyName === null) {
            throw new EntityException("Unable to find a suitable property with OneToOne attribute in the entity \"{$oneToOne->entity}\".");
        }

        $result = $this->entityManager
            ->getLightQL()
            ->builder()
            ->from($referencedEntityAnnotation->table)
            ->where([
                "{$referencedEntityAnnotation->table}.{$oneToOne->referencedColumn}" => $entity->get($column->name)
            ])
            ->select("{$referencedEntityAnnotation->table}.*")
            ->execute()
            ->fetchFirst();


        if ($result !== null) {
            $relation = new ($oneToOne->entity)($result);
            $relation->{$mappedPropertyName} = &$entity;
            return $relation;
        }

        return null;
    }

    /**
     * Resolve the name of a property mapped by an annotation.
     *
     * @param string $entityClass The class name of the mapped property.
     * @param string $check The type of annotation to find.
     * @param string $column The mapped column name.
     *
     * @return string|null
     */
    private function resolveMappedPropertyName(string $entityClass, string $check, string $column): string|null
    {
        $mappedPropertyName = null;

        $referencedEntity = new $entityClass;
        foreach ($referencedEntity->getColumns() as $p => $c) {
            if ($c->{"is{$check}"} && $c->getName() === $column) {
                $mappedPropertyName = $p;
                break;
            }
        }
        unset($referencedEntity);

        return $mappedPropertyName;
    }

    /**
     * Parse a set of raw data to a set of Entities.
     *
     * @param list<array<string, mixed>> $rawEntities The set of raw entities data provided from the database.
     *
     * @return list<TEntity>
     *
     * @throws EntityException
     * @throws ReflectionException
     */
    private function parseRows(array $rawEntities): array
    {
        return array_map(
            fn(array $rawEntity) => $this->parseRow($rawEntity),
            $rawEntities
        );
    }

    /**
     * Parses raw data to Entity.
     *
     * @param array $row Raw entity data provided from database.
     *
     * @return TEntity
     *
     * @throws EntityException
     * @throws ReflectionException
     */
    private function parseRow(array $row): IEntity
    {
        /** @var TEntity $entity */
        $entity = new ($this->entityClass)();

        // Unserialize values
        foreach ($entity->columns as $column) {
            if (array_key_exists($column->name, $row)) {
                $row[$column->name] = $column->unserialize($row[$column->name], $row);
            }
        }

        $entity->hydrate($row);

        $properties = new ReflectionClass($this->entityClass)->getProperties();

        foreach ($properties as $property) {
            $attributes = $property->getAttributes();

            if (array_any($attributes, fn(ReflectionAttribute $attribute) => $attribute->name === ManyToMany::class)) {
                $this->handleRelation($entity, $property->name, $this->fetchManyToMany(...));
            } elseif (array_any($attributes, fn(ReflectionAttribute $attribute) => $attribute->name === OneToMany::class)) {
                $this->handleRelation($entity, $property->name, $this->fetchOneToMany(...));
            } elseif (array_any($attributes, fn(ReflectionAttribute $attribute) => $attribute->name === ManyToOne::class)) {
                $this->handleRelation($entity, $property->name, $this->fetchManyToOne(...));
            } elseif (array_any($attributes, fn(ReflectionAttribute $attribute) => $attribute->name === OneToOne::class)) {
                $this->handleRelation($entity, $property->name, $this->fetchOneToOne(...));
            }
        }

        return $entity;
    }

    /**
     * @throws FacadeException
     */
    private function ensureEntityType(IEntity $entity): void
    {
        if (!$entity instanceof $this->entityClass) {
            throw new FacadeException("Cannot perform action on entity. The type of the entity is not valid for this facade.");
        }
    }

    /**
     * Handles a relation property by ensuring to respect the entity fetch mode (EAGER or LAZY).
     *
     * @param TEntity $entity The managed entity.
     * @param string $property The property in relation.
     * @param callable $initializer The relation initializer function.
     *
     * @throws EntityException
     */
    private function handleRelation(IEntity $entity, string $property, callable $initializer): void
    {
        if ($this->entityTableAnnotation->fetchMode === FetchMode::EAGER) {
            $entity->{$property} = new Relation($initializer());
        } elseif ($this->entityTableAnnotation->fetchMode === FetchMode::LAZY) {
            $entity->{$property} = new ReflectionClass(Relation::class)->newLazyGhost(function (Relation $relation) use ($initializer) {
                $relation->__construct($initializer());
            });
        } else {
            throw new EntityException("The fetch mode of the entity \"{$this->entityClass}\" is not valid.");
        }
    }
}
