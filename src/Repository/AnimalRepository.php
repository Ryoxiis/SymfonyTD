<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\Espece;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Animal>
 */
class AnimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Animal::class);
    }

    /**
     * Animaux encore disponibles, filtrés par espèce et/ou mot-clé.
     *
     * @return Animal[]
     */
    public function rechercher(?Espece $espece = null, ?string $motCle = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.disponible = true')
            ->orderBy('a.nom', 'ASC');

        if ($espece) {
            $qb->andWhere('a.espece = :espece')
                ->setParameter('espece', $espece);
        }

        if ($motCle) {
            $qb->andWhere('a.nom LIKE :motCle OR a.race LIKE :motCle OR a.description LIKE :motCle')
                ->setParameter('motCle', '%'.$motCle.'%');
        }

        return $qb->getQuery()->getResult();
    }
}
