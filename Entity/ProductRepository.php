<?php

namespace eDemy\ProductBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * ProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductRepository extends EntityRepository
{
    public function findAllOrderedByName($namespace, $query = false)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->andWhere('p.namespace = :namespace');
        $qb->orderBy('p.name','ASC');
        $qb->setParameter('namespace', $namespace);
        $query = $qb->getQuery();
        if($query) {
            return $query;
        } else {
            return $query->getResult();
        }
    }

    public function findAllByCategory($id, $namespace = null, $query = false)
    {
        $qb = $this->createQueryBuilder('p');
        if($namespace == null) {
            $qb->andWhere('p.namespace is null');
        } else {
            $qb->andWhere('p.namespace = :namespace');
            $qb->setParameter('namespace', $namespace);
        }
        $qb->andWhere('p.category = :category_id');
        $qb->orderBy('p.name','ASC');
        $qb->setParameter('category_id', $id);
        $query = $qb->getQuery();

        if($query) {
            return $query;
        } else {
            return $query->getResult();
        }
    }

    public function findLastModified($id, $namespace = null)
    {
        $qb = $this->createQueryBuilder('p');
        if($namespace == null) {
            $qb->andWhere('p.namespace is null');
        } else {
            $qb->andWhere('p.namespace = :namespace');
            $qb->setParameter('namespace', $namespace);
        }
        $qb->andWhere('p.category = :category_id');
        $qb->orderBy('p.updated','DESC');
        $qb->setParameter('category_id', $id);
        $qb->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    public function findBySearchQuery($query, $namespace = null)
    {
//        die(var_dump($query . $namespace));
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.name LIKE :query');
        $qb->andWhere('p.namespace = :namespace');
        $qb->orderBy('p.name','ASC');
        $qb->setParameter('query', '%'.$query.'%');
        $qb->setParameter('namespace', $namespace);
        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function findOneRandomBy($namespace = null)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('COUNT(u)');
        $qb->where('u.namespace = :namespace');
        $qb->setParameter('namespace', $namespace);
        $query = $qb->getQuery();
        $count = $query->getSingleScalarResult();

        $qb = $this->createQueryBuilder('u');
        $qb->where('u.namespace = :namespace');
        $qb->setParameter('namespace', $namespace);
        $qb->setFirstResult(rand(0, $count - 1));
        $qb->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getSingleResult();
    }
}
