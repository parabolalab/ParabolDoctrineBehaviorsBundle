<?php

namespace Parabol\DoctrineBehaviorsBundle\Sortable\Entity;

use Doctrine\ORM\QueryBuilder;

trait SortableRepository
{

    public function reorder($entity, $sort)
    {
        // var_dump($entity->isReordered());
        // if (!$entity->isReordered()) {
        //     return;
        // }

        $qb = $this->createQueryBuilder('e')
            ->update($this->getEntityName(), 'e')
            ;

        if(is_array($sort))
        {
            // var_dump($sort);
            $qb->andWhere('e.sort >= :from')
               ->andWhere('e.sort <= :to')
            ;
        
            if($sort[0] <= $sort[1])    
            {
                $qb->setParameter('from', (int)$sort[0])
                   ->setParameter('to', (int)$sort[1])
                   ->set('e.sort', 'e.sort - 1')
                ;
            }
            else
            {

                $qb->setParameter('from', (int)$sort[1])
                   ->setParameter('to', (int)$sort[0])
                   ->set('e.sort', 'e.sort + 1')
                ;

                // var_dump($qb->getParameters());
            }
        }
        else
        {
            $qb->andWhere('e.sort >= :sort')
                ->setParameter('sort', $entity->getSort())
                ->set('e.sort', 'e.sort '. ($sort ? '+' : '-') . ' 1')
            ;
             
        }

        if(method_exists($entity, 'getDeletedAt'))
        {
             $qb->andWhere('e.deletedAt IS NULL');
        }
        
        
        $entity->setReordered();

        $this->addSortingScope($qb, $entity);

        // echo $qb->getQuery()->getSql();
        // die();
        // var_dump($qb
        //     ->getQuery()->getSql());
        // var_dump($qb->getParameters());
        //     die();
        $qb
            ->getQuery()
            ->execute()
        ;
    }

    protected function addSortingScope(\Doctrine\ORM\QueryBuilder $qb, $entity)
    {

    }

    public function sortOrder()
    {
        return 'asc'; // or desc
    }    

}

