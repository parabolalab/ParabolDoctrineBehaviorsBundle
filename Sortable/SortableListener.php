<?php

namespace Parabol\DoctrineBehaviorsBundle\Sortable;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Parabol\AdminCoreBundle\Entity\Path;
use Parabol\AdminCoreBundle\Entity\PathTransation;
use Parabol\BaseBundle\Util\PathUtil;
use Knp\DoctrineBehaviors\Model\Sortable\Sortable;

class SortableListener extends \Knp\DoctrineBehaviors\ORM\AbstractSubscriber
{
	
     public function __construct(\Knp\DoctrineBehaviors\Reflection\ClassAnalyzer $classAnalyser)
    {
        parent::__construct($classAnalyser, true);
    }

    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'onFlush',
        );
    }

    public function prePersist(LifecycleEventArgs $args)
    {


        $entity = $args->getObject();
        $repository = $args->getEntityManager()->getRepository(get_class($entity));
        $refClass = new \ReflectionClass($repository);

        


        if($this->getClassAnalyzer()->hasTrait($refClass, 'Parabol\DoctrineBehaviorsBundle\Sortable\Entity\SortableRepository', true))
        {
            if($repository->sortOrder() == 'desc')
            {
                $sort = $repository->getMaxSort($entity);
                if($sort !== null) $entity->setSort( $sort );
            }
        }

    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em  = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        

        foreach ($uow->getScheduledEntityInsertions() as $inserted) {
            $refClass = new \ReflectionClass($inserted);
            if($this->getClassAnalyzer()->hasTrait($refClass, Sortable::class))
            {
                if($em->getRepository(get_class($inserted))->sortOrder() == 'asc')                  
                $em->getRepository(get_class($inserted))->reorder($inserted, $inserted->getSort());
            } 
        }

        foreach ($uow->getScheduledEntityUpdates() as $updated) {
            $refClass = new \ReflectionClass($updated);
            if($this->getClassAnalyzer()->hasTrait($refClass, Sortable::class))
            {
                $changeSet = $uow->getEntityChangeSet($updated);
                if(isset($changeSet['sort']))
                {
                    $em->getRepository(get_class($updated))->reorder($updated, $changeSet['sort']);
                }
            } 
        }

        foreach ($uow->getScheduledEntityDeletions() as $deleted) {
            $refClass = new \ReflectionClass($deleted);
            if($this->getClassAnalyzer()->hasTrait($refClass, Sortable::class))
            {
                $em->getRepository(get_class($deleted))->reorder($deleted, null);
            } 
        }
        
    }



}