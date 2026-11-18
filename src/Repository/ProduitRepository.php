<?php

namespace App\Repository;

use App\Entity\Categorie;
use App\Entity\Espece;
use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /**
     * Recherche de produits : chaque critère est optionnel.
     *
     * @return Produit[]
     */
    public function rechercher(?Categorie $categorie = null, ?Espece $espece = null, ?string $motCle = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.nom', 'ASC');

        if ($categorie) {
            $qb->andWhere('p.categorie = :categorie')
                ->setParameter('categorie', $categorie);
        }

        if ($espece) {
            // Produit ↔ Espece est un ManyToMany : on passe par la table de liaison
            $qb->andWhere(':espece MEMBER OF p.especes')
                ->setParameter('espece', $espece);
        }

        if ($motCle) {
            $qb->andWhere('p.nom LIKE :motCle OR p.description LIKE :motCle')
                ->setParameter('motCle', '%'.$motCle.'%');
        }

        return $qb->getQuery()->getResult();
    }
}
