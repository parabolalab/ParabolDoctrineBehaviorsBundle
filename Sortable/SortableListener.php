<?php

namespace Parabol\DoctrineBehaviorsBundle\Sortable;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Parabol\AdminCoreBundle\Entity\Path;
use Parabol\AdminCoreBundle\Entity\PathTransation;
use Knp\DoctrineBehaviors\Model\Sortable\Sortable;
use Parabol\DoctrineBehaviorsBundle\Reflection\ClassAnalyzer;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

class SortableListener implements EventSubscriber
{
    private $classAnalyser;
    protected $isRecursive;
    protected $sortableTrait;
	
    public function __construct(ClassAnalyzer $classAnalyser, $sortableTrait, $isRecursive)
    {
        $this->classAnalyser = $classAnalyser;
        $this->sortableTrait = $sortableTrait;
        $this->isRecursive   = (bool) $isRecursive;
    }

    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::onFlush,
            Events::loadClassMetadata
        );
    }

    protected function getClassAnalyzer()
    {
        return $this->classAnalyser;
    }

    private function isSortable(ClassMetadata $classMetadata)
    {
        return $this->getClassAnalyzer()->hasTrait(
            $classMetadata->reflClass,
            $this->sortableTrait,
            $this->isRecursive
        );
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();

        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($this->isSortable($classMetadata)) {

            if (!$classMetadata->hasField('sort')) {
                $classMetadata->mapField(array(
                    'fieldName' => 'sort',
                    'type'      => 'integer'
                ));
            }
        }
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