<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Utilisateur;
use App\Form\InscriptionType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        UtilisateurRepository $utilisateurRepository,
        Security $security,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $utilisateur = new Utilisateur();
        // Pré-remplissage depuis le formulaire du pied de page (?email=...)
        if ($email = $request->query->getString('email')) {
            $utilisateur->setEmail($email);
        }

        $form = $this->createForm(InscriptionType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $utilisateurRepository->findOneBy(['email' => $utilisateur->getEmail()])) {
            $form->get('email')->addError(new FormError('Un compte existe déjà avec cette adresse.'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $utilisateur->setPassword($hasher->hashPassword($utilisateur, $form->get('plainPassword')->getData()));

            // Chaque utilisateur reçoit son panier dès l'inscription
            $utilisateur->setPanier(new Panier());

            $em->persist($utilisateur);
            $em->flush();

            $this->addFlash('succes', 'Bienvenue ! Votre compte est créé.');

            // Connexion automatique après l'inscription
            return $security->login($utilisateur, 'form_login', 'main') ?? $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'form' => $form,
        ]);
    }
}
