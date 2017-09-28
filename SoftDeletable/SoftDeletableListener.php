<?php

/*
 * This file is part of the KnpDoctrineBehaviors package.
 *
 * (c) KnpLabs <http://knplabs.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Parabol\DoctrineBehaviorsBundle\SoftDeletable;

use Knp\DoctrineBehaviors\Reflection\ClassAnalyzer;

use Knp\DoctrineBehaviors\ORM\AbstractSubscriber;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Events;
/**
 * SoftDeletable Doctrine2 subscriber.
 *
 * Listens to onFlush event and marks SoftDeletable entities
 * as deleted instead of really removing them.
 */
class SoftDeletableListener extends \Knp\DoctrineBehaviors\ORM\SoftDeletable\SoftDeletableSubscriber
{
    private $softDeletableTrait;

    public function __construct(ClassAnalyzer $classAnalyzer, $isRecursive, $softDeletableTrait)
    {
        parent::__construct($classAnalyzer, $isRecursive, $softDeletableTrait);

        $this->softDeletableTrait = $softDeletableTrait;
    }

    /**
     * Listens to onFlush event.
     *
     * @param OnFlushEventArgs $args The event arguments
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $classMetadata = $em->getClassMetadata(get_class($entity));
            if ($this->isSoftDeletable($classMetadata)) {
                $oldValue = $entity->getDeletedAt();

                $entity->delete();
                $em->persist($entity);

                $uow->propertyChanged($entity, 'deletedAt', $oldValue, $entity->getDeletedAt());
                $uow->scheduleExtraUpdate($entity, [
                    'deletedAt' => [$oldValue, $entity->getDeletedAt()]
                ]);

                if($this->getClassAnalyzer()->hasTrait(new \ReflectionClass($entity), 'Knp\DoctrineBehaviors\Model\Sortable\Sortable'))
                    $em->getRepository(get_class($entity))->reorder($entity, null);
            }
        }
    }

    /**
     * Checks if entity is softDeletable
     *
     * @param ClassMetadata $classMetadata The metadata
     *
     * @return Boolean
     */
    private function isSoftDeletable(ClassMetadata $classMetadata)
    {
        return $this->getClassAnalyzer()->hasTrait($classMetadata->reflClass, $this->softDeletableTrait, $this->isRecursive);
    }



}
