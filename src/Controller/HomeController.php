<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ProduitRepository $produitRepository,
        AnimalRepository $animalRepository,
        CategorieRepository $categorieRepository,
    ): Response {
        // Les tuiles de l'accueil pointent vers des catégories précises : on les indexe par nom.
        $categoriesParNom = [];
        $exemples = [];
        foreach ($categorieRepository->findAll() as $categorie) {
            $categoriesParNom[$categorie->getNom()] = $categorie;
            $exemples[$categorie->getNom()] = $produitRepository->findOneBy(['categorie' => $categorie], ['prix' => 'DESC']);
        }

        return $this->render('home/index.html.twig', [
            // La bannière (« Mieux nourri va plus loin ») met en avant le produit alimentaire le plus cher
            'vedette' => isset($categoriesParNom['Alimentaire'])
                ? $exemples['Alimentaire']
                : $produitRepository->findOneBy([], ['prix' => 'DESC']),
            'galerie' => $produitRepository->findBy([], ['id' => 'DESC'], 9),
            'animaux' => $animalRepository->findBy(['disponible' => true], ['prix' => 'DESC'], 3),
            'categoriesParNom' => $categoriesParNom,
            'exemples' => $exemples,
        ]);
    }
}
