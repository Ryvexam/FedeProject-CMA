<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function search(string $query): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.groups', 'g')
            ->where('LOWER(c.firstName) LIKE LOWER(:query)')
            ->orWhere('LOWER(c.lastName) LIKE LOWER(:query)')
            ->orWhere('LOWER(c.phone) LIKE LOWER(:query)')
            ->orWhere('LOWER(c.email) LIKE LOWER(:query)')
            ->orWhere('LOWER(g.name) LIKE LOWER(:query)')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('LOWER(c.firstName)', 'ASC')
            ->addOrderBy('LOWER(c.lastName)', 'ASC');

        $results = $qb->getQuery()->getResult();

        $queryLower = strtolower($query);
        return array_filter($results, function(Contact $contact) use ($queryLower) {
            foreach ($contact->getCustomFields() as $field => $value) {
                if (str_contains(strtolower($field), $queryLower) ||
                    str_contains(strtolower($value), $queryLower)) {
                    return true;
                }
            }
            return false;
        });
    }

    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('LOWER(c.firstName)', 'ASC')
            ->addOrderBy('LOWER(c.lastName)', 'ASC')
            ->getQuery()
            ->getResult();
    }
}