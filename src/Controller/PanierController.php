<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\LignePanier;
use App\Entity\Produit;
use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

#[Route('/panier')]
class PanierController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(
        private PanierService $panierService,
        private Packages $packages,
    ) {
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

    /**
     * Ajout d'un produit. Répond en JSON si le navigateur le demande (ajout sans rechargement),
     * sinon redirige vers le panier (fonctionnement classique, sans JavaScript).
     */
    #[Route('/produit/{id}', name: 'app_panier_ajouter_produit', methods: ['POST'])]
    public function ajouterProduit(Produit $produit, Request $request): Response
    {
        if ($refus = $this->verifier($request)) {
            return $refus;
        }

        if ($produit->getStock() < 1) {
            return $this->repondre($request, 'Ce produit est en rupture de stock.', 'erreur', 'app_produit_show', ['id' => $produit->getId()]);
        }

        $quantite = max(1, $request->request->getInt('quantite', 1));
        $this->panierService->ajouterProduit($this->panierService->getPanier(), $produit, $quantite);

        return $this->repondre(
            $request,
            'Ajouté au panier',
            'succes',
            'app_panier',
            [],
            ['nom' => $produit->getNom(), 'photo' => $produit->getPhoto(), 'detail' => sprintf('Quantité : %d · %s €', $quantite, number_format((float) $produit->getPrix(), 2, ',', ' '))],
        );
    }

    #[Route('/animal/{id}', name: 'app_panier_ajouter_animal', methods: ['POST'])]
    public function ajouterAnimal(Animal $animal, Request $request): Response
    {
        if ($refus = $this->verifier($request)) {
            return $refus;
        }

        if (!$animal->isDisponible()) {
            return $this->repondre($request, sprintf('%s a déjà été adopté.', $animal->getNom()), 'erreur', 'app_animal_show', ['id' => $animal->getId()]);
        }

        $this->panierService->ajouterAnimal($this->panierService->getPanier(), $animal);

        return $this->repondre(
            $request,
            sprintf('%s vous attend dans votre panier', $animal->getNom()),
            'succes',
            'app_panier',
            [],
            ['nom' => $animal->getNom(), 'photo' => $animal->getPhoto(), 'detail' => sprintf('%s · %s €', $animal->getEspece()->getNom(), number_format((float) $animal->getPrix(), 2, ',', ' '))],
        );
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
     * Renvoie une réponse si quelque chose ne va pas, null sinon.
     */
    private function verifier(Request $request, ?LignePanier $ligne = null): ?Response
    {
        if (!$this->getUser()) {
            // Après connexion, l'utilisateur revient sur la page qu'il consultait
            $this->saveTargetPath($request->getSession(), 'main', $request->headers->get('referer') ?? $this->generateUrl('app_home'));
            $this->addFlash('succes', 'Connectez-vous pour ajouter des articles : votre panier sera conservé même après avoir quitté le site.');

            return $this->veutJson($request)
                ? new JsonResponse(['redirect' => $this->generateUrl('app_login')])
                : $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('panier', $request->request->getString('_token'))) {
            return $this->repondre($request, 'Session expirée, merci de réessayer.', 'erreur', 'app_panier');
        }

        if ($ligne && $ligne->getPanier() !== $this->panierService->getPanier()) {
            throw $this->createAccessDeniedException();
        }

        return null;
    }

    /**
     * Réponse JSON (ajout sans rechargement) ou message flash + redirection (envoi classique).
     */
    private function repondre(Request $request, string $message, string $type, string $route, array $parametres = [], ?array $article = null): Response
    {
        if ($this->veutJson($request)) {
            if ($article && $article['photo'] && !str_starts_with($article['photo'], 'http')) {
                $article['photo'] = $this->packages->getUrl('images/catalogue/'.$article['photo']);
            }

            return new JsonResponse([
                'ok' => 'succes' === $type,
                'message' => $message,
                'quantite' => $this->panierService->compterArticles(),
                'article' => $article,
            ]);
        }

        $this->addFlash($type, $message.('succes' === $type ? '.' : ''));

        return $this->redirectToRoute($route, $parametres);
    }

    private function veutJson(Request $request): bool
    {
        return 'json' === $request->getPreferredFormat();
    }
}
