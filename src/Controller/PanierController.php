<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\LignePanier;
use App\Entity\Produit;
use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[Route('/panier')]
class PanierController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(private PanierService $panierService)
    {
    }

    #[Route('', name: 'app_panier', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: 'Connectez-vous pour voir votre panier.')]
    public function index(): Response
    {
        $panier = $this->panierService->getPanier();

        $prixLignes = [];
        foreach ($panier->getLignes() as $ligne) {
            $prixLignes[$ligne->getId()] = $this->panierService->prixLigne($ligne);
        }

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'prixLignes' => $prixLignes,
            'total' => $this->panierService->total($panier),
        ]);
    }

    #[Route('/produit/{id}', name: 'app_panier_ajouter_produit', methods: ['POST'])]
    public function ajouterProduit(Produit $produit, Request $request): Response
    {
        if ($refus = $this->verifier($request)) {
            return $refus;
        }

        if ($produit->getStock() < 1) {
            $this->addFlash('erreur', 'Ce produit est en rupture de stock.');

            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        $this->panierService->ajouterProduit($this->panierService->getPanier(), $produit, $request->request->getInt('quantite', 1));
        $this->addFlash('succes', sprintf('« %s » a été ajouté au panier.', $produit->getNom()));

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/animal/{id}', name: 'app_panier_ajouter_animal', methods: ['POST'])]
    public function ajouterAnimal(Animal $animal, Request $request): Response
    {
        if ($refus = $this->verifier($request)) {
            return $refus;
        }

        if (!$animal->isDisponible()) {
            $this->addFlash('erreur', sprintf('%s a déjà été adopté.', $animal->getNom()));

            return $this->redirectToRoute('app_animal_show', ['id' => $animal->getId()]);
        }

        $this->panierService->ajouterAnimal($this->panierService->getPanier(), $animal);
        $this->addFlash('succes', sprintf('%s vous attend dans votre panier.', $animal->getNom()));

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/ligne/{id}/quantite', name: 'app_panier_quantite', methods: ['POST'])]
    public function quantite(LignePanier $ligne, Request $request): Response
    {
        if ($refus = $this->verifier($request, $ligne)) {
            return $refus;
        }

        $this->panierService->modifierQuantite($ligne, $request->request->getInt('quantite'));

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/ligne/{id}/retirer', name: 'app_panier_retirer', methods: ['POST'])]
    public function retirer(LignePanier $ligne, Request $request): Response
    {
        if ($refus = $this->verifier($request, $ligne)) {
            return $refus;
        }

        $this->panierService->retirer($ligne);
        $this->addFlash('succes', 'Article retiré du panier.');

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Contrôles communs : utilisateur connecté, jeton CSRF valide, et ligne appartenant bien à son panier.
     * Renvoie une redirection si quelque chose ne va pas, null sinon.
     */
    private function verifier(Request $request, ?LignePanier $ligne = null): ?RedirectResponse
    {
        if (!$this->getUser()) {
            // Après connexion, l'utilisateur revient sur la page qu'il consultait
            $this->saveTargetPath($request->getSession(), 'main', $request->headers->get('referer') ?? $this->generateUrl('app_home'));
            $this->addFlash('succes', 'Connectez-vous pour ajouter des articles : votre panier sera conservé même après avoir quitté le site.');

            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('panier', $request->request->getString('_token'))) {
            $this->addFlash('erreur', 'Session expirée, merci de réessayer.');

            return $this->redirectToRoute('app_panier');
        }

        if ($ligne && $ligne->getPanier() !== $this->panierService->getPanier()) {
            throw $this->createAccessDeniedException();
        }

        return null;
    }
}
