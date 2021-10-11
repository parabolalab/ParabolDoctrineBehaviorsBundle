<?php

namespace Parabol\DoctrineBehaviorsBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Aliso\CoreBundle\DoctrineFunctions;

class SluggableSubscriber extends \Knp\DoctrineBehaviors\ORM\Sluggable\SluggableSubscriber
{
    
    public function prePersist(LifecycleEventArgs $args)
    {
        parent::prePersist($args);
        $this->setSlug($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        parent::preUpdate($args);
        $this->setSlug($args, true);
    }

    
    private function setSlug(LifecycleEventArgs $args, $update = false)
    {

        $entity = $args->getEntity();
        if (method_exists($entity, 'getSlug')) {

            $uow = $args->getEntityManager()->getUnitOfWork();

            if ($update == true) {
                $uow->recomputeSingleEntityChangeSet(
                    $args->getEntityManager()->getClassMetadata(get_class($entity)),
                    $args->getEntity()
                );
                $changes = $uow->getEntityChangeset($entity);

                if (!isset($changes['slug']) || $changes['slug'][0] == $changes['slug'][1]) {
                    return;
                }
            }

            $entityManager = $args->getEntityManager();
            $queryBuilder = $entityManager->createQueryBuilder();

            

            $query = $queryBuilder->select('n, LENGTH(n.slug) as length')
                                  ->from(get_class($entity), 'n');

            if(method_exists($entity, 'addSluggableScope'))
            {
              $entity->addSluggableScope($query);
              

            }

            $query->andWhere("n.slug = :slug OR regexp(n.slug, '^" . $entity->getSlug() . "[-]{1}[1-9]{1}[0-9]*$') != false")->setParameter('slug', $entity->getSlug());
            
            if (method_exists($entity, 'getLocale')){
                $query->andWhere("n.locale = '" . $entity->getLocale() . "'");
            }

            $query
                ->addOrderBy('length', 'DESC')
                ->addOrderBy('n.slug', 'DESC')
                ->setMaxResults(1)
            ;

            $result = $query->getQuery()->getOneOrNullResult(); 
                         
            
            if (null !== $result) {

                $slug = $entity->getSlug() . '-2';

                if(str_replace($entity->getSlug(), '', $result[0]->getSlug()))
                {
                    $explode = explode('-', $result[0]->getSlug());
                    $lastE = count($explode) - 1;
                    if (count($explode) && isset($explode[$lastE]) && ctype_digit($explode[$lastE])) {
                        $explode[$lastE] = $explode[$lastE] + 1;
                        $slug = implode('-', $explode);
                        
                    }
                }
                
                
                 $entity->setSlug($slug);
                
            }


        }
    }
}