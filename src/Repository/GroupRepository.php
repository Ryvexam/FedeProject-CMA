<?php

namespace App\Repository;

use App\Entity\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function save(Group $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Group $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find groups that have at least one contact
     */
    public function findNonEmptyGroups(): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.contacts', 'c')
            ->groupBy('g.id')
            ->having('COUNT(c.id) > 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Group[] Returns an array of Group objects
     */
    public function findBySearch(string $term): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.name LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}