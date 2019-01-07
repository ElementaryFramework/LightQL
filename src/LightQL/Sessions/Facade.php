<?php

/**
 * LightQL - The lightweight PHP ORM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @category  Library
 * @package   LightQL
 * @author    Axel Nana <ax.lnana@outlook.com>
 * @copyright 2018 Aliens Group, Inc.
 * @license   MIT <https://github.com/ElementaryFramework/LightQL/blob/master/LICENSE>
 * @version   1.0.0
 * @link      http://lightql.na2axl.tk
 */

namespace ElementaryFramework\LightQL\Sessions;

use ElementaryFramework\Annotations\Annotations;
use ElementaryFramework\Annotations\Exceptions\AnnotationException;

use ElementaryFramework\LightQL\Annotations\NamedQueryAnnotation;
use ElementaryFramework\LightQL\Entities\Entity;
use ElementaryFramework\LightQL\Entities\EntityManager;
use ElementaryFramework\LightQL\Entities\IEntity;
use ElementaryFramework\LightQL\Entities\Query;
use ElementaryFramework\LightQL\Exceptions\EntityException;
use ElementaryFramework\LightQL\Exceptions\FacadeException;
use ElementaryFramework\LightQL\Persistence\PersistenceUnit;

/**
 * Facade
 *
 * Base class for all entity facades.
 *
 * @abstract
 * @category Sessions
 * @package  LightQL
 * @author   Nana Axel <ax.lnana@outlook.com>
 * @link     http://lightql.na2axl.tk/docs/api/LightQL/Sessions/Facade
 */
abstract class Facade implements IFacade
{
    /**
     * The entity manager of this facade.
     *
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * The entity class name managed by this facade.
     *
     * @var \ReflectionClass
     */
    private $_class;

    /**
     * Facade constructor.
     *
     * @param string $class The entity class name managed by this facade.
     *
     * @throws EntityException When the entity class or object doesn't have an @entity annotation.
     * @throws FacadeException When the "entityManager" property of this Facade doesn't have a @persistenceUnit annotation.
     * @throws FacadeException When the entity class or object doesn't inherit from the Entity class.
     * @throws AnnotationException When the Facade is unable to read an annotation.
     */
    public function __construct($class)
    {
        if (!Annotations::propertyHasAnnotation($this, "entityManager", "@persistenceUnit")) {
            throw new FacadeException("Cannot create the entity facade. The property \"entityManager\" has no @persistenceUnit annotation.");
        }

        if (!Annotations::classHasAnnotation($class, "@entity")) {
            throw new EntityException("Cannot create an entity without the @entity annotation.");
        }

        if (!is_subclass_of($class, Entity::class)) {
            throw new FacadeException("Unable to create a facade. The entity class or object seems to be invalid.");
        }

        $this->_class = new \ReflectionClass($class);

        $annotations = Annotations::ofProperty($this, "entityManager", "@persistenceUnit");
        $this->entityManager = new EntityManager(PersistenceUnit::create($annotations[0]->name));
    }

    /**
     * Creates an entity.
     *
     * @param Entity $entity The entity to create.
     *
     * @throws FacadeException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\EntityException
     */
    public function create(Entity &$entity)
    {
        if (!$this->_class->isInstance($entity)) {
            throw new FacadeException("Cannot create entity. The type of the entity is not valid for this facade.");
        }

        try {
            $this->entityManager->persist($entity);

            $columns = $entity->getColumns();
            foreach ($columns as $property => $column) {
                if ($column->isOneToMany) {
                    $this->_fetchOneToMany($entity, $property);
                } elseif ($column->isManyToOne) {
                    $this->_fetchManyToOne($entity, $property);
                } elseif ($column->isManyToMany) {
                    $this->_fetchManyToMany($entity, $property);
                } elseif ($column->isOneToOne) {
                    $this->_fetchOneToOne($entity, $property);
                }
            }
        } catch (\Exception $e) {
            throw new FacadeException($e->getMessage());
        }
    }

    /**
     * Edit an entity.
     *
     * @param Entity $entity The entity to edit.
     *
     * @throws FacadeException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\EntityException
     */
    public function edit(Entity &$entity)
    {
        if (!$this->_class->isInstance($entity)) {
            throw new FacadeException("Cannot edit entity. The type of the entity is not valid for this facade.");
        }

        $this->entityManager->merge($entity);
    }

    /**
     * Delete an entity.
     *
     * @param Entity $entity The entity to delete.
     *
     * @throws FacadeException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\EntityException
     */
    public function delete(Entity &$entity)
    {
        if (!$this->_class->isInstance($entity)) {
            throw new FacadeException("Cannot edit entity. The type of the entity is not valid for this facade.");
        }

        $this->entityManager->merge($entity);
        $this->entityManager->delete($entity);
    }

    /**
     * Find an entity.
     *
     * @param mixed $id The id of the entity to find
     *
     * @return Entity
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function find($id): Entity
    {
        $annotations = Annotations::ofClass($this->getEntityClassName(), "@entity");

        return $this->_parseRawEntity(
            $this->entityManager->find($this->getEntityClassName(), $id),
            $annotations
        );
    }

    /**
     * Find all entities.
     *
     * @return Entity[]
     *
     * @throws EntityException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function findAll(): array
    {
        $annotations = Annotations::ofClass($this->getEntityClassName(), "@entity");

        $rawEntities = $this->entityManager
            ->getLightQL()
            ->from($annotations[0]->table)
            ->selectArray();

        return $this->_parseRawEntities($rawEntities, $annotations);
    }

    /**
     * Find all entities in the given range.
     *
     * @param int $start  The starting offset.
     * @param int $length The number of entities to find.
     *
     * @return Entity[]
     *
     * @throws EntityException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function findRange(int $start, int $length): array
    {
        $annotations = Annotations::ofClass($this->getEntityClassName(), "@entity");

        $rawEntities = $this->entityManager
            ->getLightQL()
            ->from($annotations[0]->table)
            ->limit($start, $length)
            ->selectArray();

        return $this->_parseRawEntities($rawEntities, $annotations);
    }

    /**
     * Count the number of entities.
     *
     * @return int
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    public function count(): int
    {
        $annotations = Annotations::ofClass($this->getEntityClassName(), "@entity");

        return $this->entityManager
            ->getLightQL()
            ->from($annotations[0]->table)
            ->count();
    }

    /**
     * Returns the entity class name of this facade.
     *
     * @return string
     */
    public function getEntityClassName(): string
    {
        return $this->_class->getName();
    }

    /**
     * Returns the entity manager for this facade.
     *
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Get a named query.
     *
     * @param string $name The name of the query.
     *
     * @return Query
     *
     * @throws FacadeException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     */
    public function getNamedQuery(string $name): Query
    {
        if (!Annotations::classHasAnnotation($this->_class->name, "@namedQuery")) {
            throw new FacadeException("The {$this->_class->name} has no @namedQuery annotation.");
        }

        $namedQueries = Annotations::ofClass($this->getEntityClassName(), "@namedQuery");
        $query = null;

        /** @var NamedQueryAnnotation $namedQuery */
        foreach ($namedQueries as $namedQuery) {
            if ($namedQuery->name === $name) {
                $query = $namedQuery->query;
                break;
            }
        }

        if ($query === null) {
            throw new FacadeException("The {$this->_class->name} has no @namedQuery annotation with the name {$name}.");
        }

        $q = new Query($this->entityManager);
        $q->setEntity($this->_class);
        $q->setQuery($query);

        return $q;
    }

    /**
     * Fetch data for a many-to-many relation.
     *
     * @param IEntity $entity   The managed entity.
     * @param string  $property The property in many-to-many relation.
     *
     * @throws EntityException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    private function _fetchManyToMany(&$entity, $property)
    {
        $manyToMany = Annotations::ofProperty($entity, $property, "@manyToMany");
        $column = Annotations::ofProperty($entity, $property, "@column");
        $entityAnnotations = Annotations::ofClass($entity, "@entity");

        $mappedPropertyName = null;

        $referencedEntity = new $manyToMany[0]->entity;
        foreach ($referencedEntity->getColumns() as $p => $c) {
            if ($c->isManyToMany) {
                $mappedManyToMany = Annotations::ofProperty($referencedEntity, $p, "@manyToMany");
                if ($mappedManyToMany[0]->crossTable === $manyToMany[0]->crossTable) {
                    $mappedPropertyName = $p;
                    break;
                }
            }
        }
        unset($referencedEntity);

        if ($mappedPropertyName === null) {
            throw new EntityException("Unable to find a suitable property with a @manyToMany annotation in the entity \"$manyToMany[0]->entity\".");
        }

        $mappedPropertyManyToManyAnnotation = Annotations::ofProperty($manyToMany[0]->entity, $mappedPropertyName, "@manyToMany");
        $mappedPropertyColumnAnnotation = Annotations::ofProperty($manyToMany[0]->entity, $mappedPropertyName, "@column");
        $referencedEntityAnnotations = Annotations::ofClass($manyToMany[0]->entity, "@entity");

        $lightql = $this->entityManager->getLightQL();

        $results = $lightql
            ->from($manyToMany[0]->crossTable)
            ->where(array("{$manyToMany[0]->crossTable}.{$manyToMany[0]->referencedColumn}" => $lightql->quote($entity->get($column[0]->name))))
            ->joinArray(
                "{$referencedEntityAnnotations[0]->table}.*",
                array(
                    array(
                        "side" => "LEFT",
                        "table" => $referencedEntityAnnotations[0]->table,
                        "cond" => "{$manyToMany[0]->crossTable}.{$mappedPropertyAnnotation[0]->referencedColumn} = {$referencedEntityAnnotations[0]->table}.{$mappedPropertyColumnAnnotation[0]->name}"
                    )
                )
            );

        $className = $manyToMany[0]->entity;
        $entity->{$property} = array_map(function($item) use ($manyToMany, $className) {
            return new $className($item);
        }, $results);
    }

    /**
     * Fetch data for a one-to-many relation.
     *
     * @param IEntity $entity   The managed entity.
     * @param string  $property The property in one-to-many relation.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    private function _fetchOneToMany(&$entity, $property)
    {
        $oneToMany = Annotations::ofProperty($entity, $property, "@oneToMany");
        $column = Annotations::ofProperty($entity, $property, "@column");
        $referencedEntityAnnotations = Annotations::ofClass($oneToMany[0]->entity, "@entity");

        $mappedPropertyName = $this->_resolveMappedPropertyName($oneToMany[0]->entity, "ManyToOne", $oneToMany[0]->referencedColumn);

        if ($mappedPropertyName === null) {
            throw new EntityException("Unable to find a suitable property with @manyToOne annotation in the entity \"{$oneToMany[0]->entity}\".");
        }

        $mappedPropertyManyToOneAnnotation = Annotations::ofProperty($oneToMany[0]->entity, $mappedPropertyName, "@manyToOne");

        $lightql = $this->entityManager->getLightQL();

        $result = $lightql
            ->from($referencedEntityAnnotations[0]->table)
            ->where(array("{$referencedEntityAnnotations[0]->table}.{$oneToMany[0]->referencedColumn}" => $lightql->quote($entity->get($column[0]->name))))
            ->selectFirst("{$referencedEntityAnnotations[0]->table}.*");

        $className = $oneToMany[0]->entity;

        $entity->{$property} = $result;

        if ($result !== null) {
            $entity->{$property} = new $className($result);
        }
    }

    /**
     * Fetch data for a many-to-one relation.
     *
     * @param IEntity $entity   The managed entity.
     * @param string  $property The property in many-to-one relation.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    private function _fetchManyToOne(&$entity, $property)
    {
        $manyToOne = Annotations::ofProperty($entity, $property, "@manyToOne");
        $column = Annotations::ofProperty($entity, $property, "@column");
        $referencedEntityAnnotations = Annotations::ofClass($manyToOne[0]->entity, "@entity");

        $mappedPropertyName = $this->_resolveMappedPropertyName($manyToOne[0]->entity, "OneToMany", $manyToOne[0]->referencedColumn);

        if ($mappedPropertyName === null) {
            throw new EntityException("Unable to find a suitable property with @oneToMany annotation in the entity \"{$manyToOne[0]->entity}\".");
        }

        $lightql = $this->entityManager->getLightQL();

        $results = $lightql
            ->from($referencedEntityAnnotations[0]->table)
            ->where(array("{$referencedEntityAnnotations[0]->table}.{$manyToOne[0]->referencedColumn}" => $lightql->quote($entity->get($column[0]->name))))
            ->selectArray("{$referencedEntityAnnotations[0]->table}.*");

        $entity->{$property} = array_map(function($item) use ($manyToOne, $entity, $mappedPropertyName) {
            $className = $manyToOne[0]->entity;
            $e = new $className($item);
            $e->{$mappedPropertyName} = &$entity;
            return $e;
        }, $results);
    }

    /**
     * Fetch data for a one-to-one relation.
     *
     * @param IEntity $entity   The managed entity.
     * @param string  $property The property in one-to-one relation.
     *
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    private function _fetchOneToOne(&$entity, $property)
    {
        $oneToOne = Annotations::ofProperty($entity, $property, "@oneToOne");
        $column = Annotations::ofProperty($entity, $property, "@column");
        $referencedEntityAnnotations = Annotations::ofClass($oneToOne[0]->entity, "@entity");

        $mappedPropertyName = $this->_resolveMappedPropertyName($oneToOne[0]->entity, "OneToOne", $oneToOne[0]->referencedColumn);

        if ($mappedPropertyName === null) {
            throw new EntityException("Unable to find a suitable property with @oneToOne annotation in the entity \"{$oneToOne[0]->entity}\".");
        }

        $mappedPropertyAnnotation = Annotations::ofProperty($oneToOne[0]->entity, $mappedPropertyName, "@oneToOne");

        $lightql = $this->entityManager->getLightQL();

        $result = $lightql
            ->from($referencedEntityAnnotations[0]->table)
            ->where(array("{$referencedEntityAnnotations[0]->table}.{$oneToOne[0]->referencedColumn}" => $lightql->quote($entity->get($column[0]->name))))
            ->selectFirst("{$referencedEntityAnnotations[0]->table}.*");

        $className = $oneToOne[0]->entity;

        $entity->{$property} = $result;

        if ($result !== null) {
            $entity->{$property} = new $className($result);
            $entity->{$property}->{$mappedPropertyName} = &$entity;
        }
    }

    /**
     * Resolve the name of a property mapped by an annotation.
     *
     * @param string $entityClass The class name of the mapped property.
     * @param string $check       The type of annotation to find.
     * @param string $column      The mapped column name.
     *
     * @return string|null
     */
    private function _resolveMappedPropertyName(string $entityClass, string $check, string $column): bool
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
     * @param array $rawEntities The set of raw entities data provided fromm database.
     * @param array $annotations The set of entity annotations.
     *
     * @return Entity[]
     *
     * @throws EntityException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    private function _parseRawEntities($rawEntities, $annotations): array
    {
        $entities = array();

        foreach ($rawEntities as $rawEntity) {
            array_push($entities, $this->_parseRawEntity($rawEntity, $annotations));
        }

        return $entities;
    }

    /**
     * Parses raw data to Entity.
     *
     * @param array $rawEntity   Raw entity data provided from database.
     * @param array $annotations The set of entity annotations.
     *
     * @return Entity
     *
     * @throws EntityException
     * @throws \ElementaryFramework\Annotations\Exceptions\AnnotationException
     * @throws \ElementaryFramework\LightQL\Exceptions\LightQLException
     */
    private function _parseRawEntity($rawEntity, $annotations): Entity
    {
        /** @var Entity $entity */
        $entity = $this->_class->newInstance($rawEntity);

        if ($annotations[0]->fetchMode === Entity::FETCH_EAGER) {
            $properties = $this->_class->getProperties();

            foreach ($properties as $property) {
                if (Annotations::propertyHasAnnotation($entity, $property->name, "@manyToMany")) {
                    $this->_fetchManyToMany($entity, $property->name);
                } elseif (Annotations::propertyHasAnnotation($entity, $property->name, "@oneToMany")) {
                    $this->_fetchOneToMany($entity, $property->name);
                } elseif (Annotations::propertyHasAnnotation($entity, $property->name, "@manyToOne")) {
                    $this->_fetchManyToOne($entity, $property->name);
                } elseif (Annotations::propertyHasAnnotation($entity, $property->name, "@oneToOne")) {
                    $this->_fetchOneToOne($entity, $property->name);
                }
            }
        }

        return $entity;
    }
}
