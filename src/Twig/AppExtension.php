<?php

namespace App\Twig;

use App\Entity\Animal;
use App\Entity\Categorie;
use App\Entity\Espece;
use App\Repository\AnimalRepository;
use App\Repository\CategorieRepository;
use App\Repository\EspeceRepository;
use App\Service\PanierService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Fonctions Twig utilisées dans le layout (menu, pied de page, pastille du panier).
 */
class AppExtension extends AbstractExtension
{
    public function __construct(
        private CategorieRepository $categorieRepository,
        private EspeceRepository $especeRepository,
        private AnimalRepository $animalRepository,
        private PanierService $panierService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nav_categories', $this->categories(...)),
            new TwigFunction('nav_especes', $this->especes(...)),
            new TwigFunction('animal_vedette', $this->animalVedette(...)),
            new TwigFunction('panier_quantite', $this->panierService->compterArticles(...)),
        ];
    }

    /** @return Categorie[] */
    public function categories(): array
    {
        return $this->categorieRepository->findBy([], ['nom' => 'ASC']);
    }

    /** @return Espece[] */
    public function especes(): array
    {
        return $this->especeRepository->findBy([], ['nom' => 'ASC']);
    }

    public function animalVedette(): ?Animal
    {
        return $this->animalRepository->findOneBy(['disponible' => true], ['prix' => 'DESC']);
    }
}
