<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\Produit;
use App\Repository\AnimalRepository;
use App\Repository\CategorieRepository;
use App\Repository\EspeceRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CatalogueController extends AbstractController
{
    /**
     * Liste des produits, filtrable par catégorie, espèce et mot-clé (?categorie=1&espece=2&q=balle).
     */
    #[Route('/catalogue', name: 'app_catalogue')]
    public function index(
        Request $request,
        ProduitRepository $produitRepository,
        AnimalRepository $animalRepository,
        CategorieRepository $categorieRepository,
        EspeceRepository $especeRepository,
    ): Response {
        $categorie = $request->query->getInt('categorie') ? $categorieRepository->find($request->query->getInt('categorie')) : null;
        $espece = $request->query->getInt('espece') ? $especeRepository->find($request->query->getInt('espece')) : null;
        $recherche = trim($request->query->getString('q')) ?: null;

        return $this->render('catalogue/index.html.twig', [
            'produits' => $produitRepository->rechercher($categorie, $espece, $recherche),
            'animaux' => $recherche ? $animalRepository->rechercher(null, $recherche) : [],
            'categorie' => $categorie,
            'espece' => $espece,
            'recherche' => $recherche,
        ]);
    }

    #[Route('/animaux', name: 'app_animaux')]
    public function animaux(Request $request, AnimalRepository $animalRepository, EspeceRepository $especeRepository): Response
    {
        $espece = $request->query->getInt('espece') ? $especeRepository->find($request->query->getInt('espece')) : null;

        return $this->render('catalogue/animaux.html.twig', [
            'animaux' => $animalRepository->rechercher($espece),
            'espece' => $espece,
        ]);
    }

    #[Route('/produit/{id}', name: 'app_produit_show', requirements: ['id' => '\d+'])]
    public function produit(Produit $produit, ProduitRepository $produitRepository): Response
    {
        $similaires = array_filter(
            $produitRepository->findBy(['categorie' => $produit->getCategorie()], ['prix' => 'DESC'], 7),
            fn (Produit $p) => $p->getId() !== $produit->getId(),
        );

        return $this->render('catalogue/produit.html.twig', [
            'produit' => $produit,
            'similaires' => $similaires,
        ]);
    }

    #[Route('/animal/{id}', name: 'app_animal_show', requirements: ['id' => '\d+'])]
    public function animal(Animal $animal, ProduitRepository $produitRepository): Response
    {
        // Âge lisible calculé à partir de la date de naissance
        $age = null;
        if ($animal->getDateNaissance()) {
            $diff = $animal->getDateNaissance()->diff(new \DateTimeImmutable());
            $age = $diff->y > 0
                ? $diff->y.' an'.($diff->y > 1 ? 's' : '')
                : max(1, $diff->m).' mois';
        }

        return $this->render('catalogue/animal.html.twig', [
            'animal' => $animal,
            'age' => $age,
            'produitsConseilles' => $produitRepository->rechercher(null, $animal->getEspece()),
        ]);
    }
}
