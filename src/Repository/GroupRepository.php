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
            ->orderBy('LOWER(g.name)', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Group[] Returns an array of Group objects
     */
    public function findBySearch(string $term): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('LOWER(g.name) LIKE LOWER(:term)')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('LOWER(g.name)', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all groups sorted by name case-insensitively
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('LOWER(g.name)', 'ASC')
            ->getQuery()
            ->getResult();
    }
}