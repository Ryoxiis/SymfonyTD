<?php

namespace App\Service;

use App\Entity\Animal;
use App\Entity\LignePanier;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Toute la logique du panier au même endroit.
 *
 * Le panier est enregistré en base et lié à l'utilisateur (OneToOne) :
 * il survit au rafraîchissement, à la fermeture du navigateur et au changement d'appareil.
 */
class PanierService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    /**
     * Renvoie le panier de l'utilisateur connecté (créé au besoin), ou null si personne n'est connecté.
     */
    public function getPanier(): ?Panier
    {
        $utilisateur = $this->getUtilisateur();
        if (!$utilisateur) {
            return null;
        }

        $panier = $utilisateur->getPanier();
        if (!$panier) {
            $panier = new Panier();
            $utilisateur->setPanier($panier);
            $this->em->persist($panier);
            $this->em->flush();
        }

        return $panier;
    }

    /** Nombre total d'articles, pour la pastille du menu. */
    public function compterArticles(): int
    {
        $panier = $this->getUtilisateur()?->getPanier();
        if (!$panier) {
            return 0;
        }

        $total = 0;
        foreach ($panier->getLignes() as $ligne) {
            $total += $ligne->getQuantite();
        }

        return $total;
    }

    /**
     * Ajoute un produit ; si le produit est déjà dans le panier, on augmente la quantité.
     * La quantité ne dépasse jamais le stock.
     */
    public function ajouterProduit(Panier $panier, Produit $produit, int $quantite = 1): void
    {
        $ligne = $this->trouverLigne($panier, $produit);
        if (!$ligne) {
            $ligne = (new LignePanier())->setProduit($produit)->setQuantite(0);
            $panier->addLigne($ligne);
            $this->em->persist($ligne);
        }

        $ligne->setQuantite(min($ligne->getQuantite() + max(1, $quantite), $produit->getStock()));
        $this->em->flush();
    }

    /**
     * Ajoute un animal : il est unique, donc la quantité vaut toujours 1.
     */
    public function ajouterAnimal(Panier $panier, Animal $animal): void
    {
        if ($this->trouverLigne($panier, $animal)) {
            return;
        }

        $ligne = (new LignePanier())->setAnimal($animal)->setQuantite(1);
        $panier->addLigne($ligne);
        $this->em->persist($ligne);
        $this->em->flush();
    }

    public function modifierQuantite(LignePanier $ligne, int $quantite): void
    {
        if ($quantite <= 0) {
            $this->retirer($ligne);

            return;
        }

        if ($ligne->getAnimal()) {
            $quantite = 1;
        } elseif ($ligne->getProduit()) {
            $quantite = min($quantite, $ligne->getProduit()->getStock());
        }

        $ligne->setQuantite($quantite);
        $this->em->flush();
    }

    public function retirer(LignePanier $ligne): void
    {
        $ligne->getPanier()?->removeLigne($ligne);
        $this->em->remove($ligne);
        $this->em->flush();
    }

    public function prixLigne(LignePanier $ligne): float
    {
        $article = $ligne->getProduit() ?? $ligne->getAnimal();

        return $article ? (float) $article->getPrix() * $ligne->getQuantite() : 0.0;
    }

    public function total(Panier $panier): float
    {
        $total = 0.0;
        foreach ($panier->getLignes() as $ligne) {
            $total += $this->prixLigne($ligne);
        }

        return $total;
    }

    private function trouverLigne(Panier $panier, Produit|Animal $article): ?LignePanier
    {
        foreach ($panier->getLignes() as $ligne) {
            if ($ligne->getProduit() === $article || $ligne->getAnimal() === $article) {
                return $ligne;
            }
        }

        return null;
    }

    private function getUtilisateur(): ?Utilisateur
    {
        $utilisateur = $this->security->getUser();

        return $utilisateur instanceof Utilisateur ? $utilisateur : null;
    }
}
