<?php

namespace App\Command;

use App\Entity\Animal;
use App\Entity\Categorie;
use App\Entity\Espece;
use App\Entity\LignePanier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Remplit la base avec un catalogue de démonstration.
 * Usage : symfony console app:demo
 * Attention : vide d'abord les tables du catalogue et des paniers.
 */
#[AsCommand(name: 'app:demo', description: 'Charge un catalogue de démonstration (espèces, catégories, produits, animaux, compte démo)')]
class DemoCommand extends Command
{
    /** Photos Unsplash (licence Unsplash, utilisation libre) affichées directement depuis leur serveur. */
    private const UNSPLASH = 'https://images.unsplash.com/';
    private const FORMAT = '?w=900&h=760&fit=crop&auto=format&q=80';

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$io->confirm('Le catalogue et les paniers existants vont être supprimés. Continuer ?', true)) {
            return Command::SUCCESS;
        }

        // On vide les lignes de panier (les paniers eux-mêmes restent, vides), puis le catalogue
        // dans l'ordre inverse des dépendances.
        $this->em->createQuery('DELETE FROM '.LignePanier::class.' l')->execute();
        foreach ([Animal::class, Produit::class, Categorie::class, Espece::class] as $classe) {
            foreach ($this->em->getRepository($classe)->findAll() as $entite) {
                $this->em->remove($entite);
            }
            $this->em->flush();
        }

        // ---------- Espèces ----------
        $especes = [];
        foreach (['Chien', 'Chat', 'Poisson', 'Oiseau', 'Reptile', 'Rongeur'] as $nom) {
            $especes[$nom] = (new Espece())->setNom($nom);
            $this->em->persist($especes[$nom]);
        }

        // ---------- Catégories ----------
        $categories = [];
        foreach (['Alimentaire', 'Loisirs', 'Fashion', 'Santé'] as $nom) {
            $categories[$nom] = (new Categorie())->setNom($nom);
            $this->em->persist($categories[$nom]);
        }

        // ---------- Produits ----------
        // [nom, description, prix, stock, photo, poids (g), expiration, catégorie, espèces]
        $produits = [
            ['Croquettes Prime Chien 12 kg', "Recette riche en protéines pour chiens actifs.\nPoulet frais, riz complet et oméga-3 pour un pelage brillant.", '64.90', 25, self::UNSPLASH.'photo-1714068691210-073dc52c6c1d'.self::FORMAT, 12000, '+14 months', 'Alimentaire', ['Chien']],
            ['Pâtée Gourmet Chat ×12', "Assortiment de 12 barquettes au poisson et à la volaille.\nSans céréales, sans colorants.", '18.50', 40, self::UNSPLASH.'photo-1558993457-4bc6ec2c3734'.self::FORMAT, 1020, '+10 months', 'Alimentaire', ['Chat']],
            ['Flocons tropicaux 250 ml', 'Aliment complet en flocons pour poissons d\'eau douce tropicale. Renforce les couleurs.', '7.90', 60, self::UNSPLASH.'photo-1636045466232-539c7bd7817e'.self::FORMAT, 50, '+18 months', 'Alimentaire', ['Poisson']],
            ['Mélange de graines premium', 'Graines sélectionnées et fruits secs pour oiseaux et petits rongeurs.', '9.90', 35, self::UNSPLASH.'photo-1532598187460-98fe8826d1e2'.self::FORMAT, 1000, '+12 months', 'Alimentaire', ['Oiseau', 'Rongeur']],
            ['Balle rebond indestructible', 'Caoutchouc naturel ultra-résistant, rebond imprévisible. Flotte dans l\'eau.', '12.90', 50, self::UNSPLASH.'photo-1612502169027-5a379283f9c0'.self::FORMAT, 180, null, 'Loisirs', ['Chien']],
            ['Canne à plumes', 'Canne télescopique avec plumes et clochette pour réveiller l\'instinct de chasse.', '8.50', 45, self::UNSPLASH.'photo-1637066470832-f4ae1709d75b'.self::FORMAT, 60, null, 'Loisirs', ['Chat']],
            ['Arbre à chat Tour 150 cm', "Quatre plateformes, deux niches et des poteaux en sisal.\nStructure lestée, très stable.", '129.00', 6, self::UNSPLASH.'photo-1601758065893-25c11bfa69b5'.self::FORMAT, 18000, null, 'Loisirs', ['Chat']],
            ['Roue silencieuse 28 cm', 'Roue d\'exercice à roulement silencieux pour hamsters et gerbilles.', '24.90', 15, self::UNSPLASH.'photo-1657076761228-bdb21cf0bc7c'.self::FORMAT, 700, null, 'Loisirs', ['Rongeur']],
            ['Manteau imperméable Night', 'Manteau doublé polaire, bandes réfléchissantes pour les sorties du soir.', '39.90', 20, self::UNSPLASH.'photo-1530041539828-114de669390e'.self::FORMAT, 250, null, 'Fashion', ['Chien']],
            ['Collier cuir & or', 'Cuir pleine fleur et boucle dorée. Taille ajustable.', '29.00', 30, self::UNSPLASH.'photo-1667716705760-233650f8f3fe'.self::FORMAT, 90, null, 'Fashion', ['Chien', 'Chat']],
            ['Nœud papillon soirée', 'Nœud en satin noir à clipser sur le collier, pour les grandes occasions.', '14.50', 25, self::UNSPLASH.'photo-1604543219730-fa94b8e359e3'.self::FORMAT, 20, null, 'Fashion', ['Chien', 'Chat']],
            ['Shampoing doux pelage', 'Formule hypoallergénique à l\'avoine, pH adapté aux chiens et chats.', '11.90', 40, self::UNSPLASH.'photo-1647002380358-fc70ed2f04e0'.self::FORMAT, 250, '+24 months', 'Santé', ['Chien', 'Chat']],
            ['Lampe chauffante terrarium', 'Lampe céramique 100 W pour maintenir la bonne température du terrarium.', '34.90', 10, self::UNSPLASH.'photo-1777062573331-76eff62d46b4'.self::FORMAT, 400, null, 'Santé', ['Reptile']],
        ];

        foreach ($produits as [$nom, $description, $prix, $stock, $photo, $poids, $expiration, $categorie, $especesProduit]) {
            $produit = (new Produit())
                ->setNom($nom)
                ->setDescription($description)
                ->setPrix($prix)
                ->setStock($stock)
                ->setPhoto($photo)
                ->setPoids($poids)
                ->setDateExpiration($expiration ? new \DateTimeImmutable($expiration) : null)
                ->setCategorie($categories[$categorie]);
            foreach ($especesProduit as $nomEspece) {
                $produit->addEspece($especes[$nomEspece]);
            }
            $this->em->persist($produit);
        }

        // ---------- Animaux ----------
        // [nom, description, prix, photo, naissance, sexe, race, espèce]
        $animaux = [
            ['Rex', 'Chiot joueur et très affectueux, déjà sociabilisé avec les enfants. Vacciné et pucé.', '890.00', self::UNSPLASH.'photo-1705624980194-6325687bb1aa'.self::FORMAT, '-4 months', 'Mâle', 'Berger australien', 'Chien'],
            ['Bulle', 'Petite chienne calme et câline, idéale en appartement.', '1150.00', self::UNSPLASH.'photo-1549297161-14f79605a74c'.self::FORMAT, '-5 months', 'Femelle', 'Caniche toy', 'Chien'],
            ['Mochi', 'Chatonne au caractère doux, adore les siestes au soleil.', '950.00', self::UNSPLASH.'photo-1727683024583-c7ce0cf9e691'.self::FORMAT, '-3 months', 'Femelle', 'British Shorthair', 'Chat'],
            ['Nox', 'Chaton curieux au pelage noir de jais.', '790.00', self::UNSPLASH.'photo-1769684936634-3f7af4e4f9db'.self::FORMAT, '-4 months', 'Mâle', 'Bombay', 'Chat'],
            ['Nemo', 'Poisson-clown d\'élevage, parfait pour un aquarium récifal.', '29.90', self::UNSPLASH.'photo-1535591273668-578e31182c4f'.self::FORMAT, null, null, 'Amphiprion ocellaris', 'Poisson'],
            ['Kiwi', 'Perruche bavarde et sociable, à adopter de préférence en couple.', '45.00', self::UNSPLASH.'photo-1560595643-90bb555b2eaa'.self::FORMAT, '-8 months', 'Mâle', 'Perruche ondulée', 'Oiseau'],
            ['Draco', 'Gecko docile, facile à manipuler. Idéal pour débuter en terrariophilie.', '120.00', self::UNSPLASH.'photo-1633767319476-d43c16b828c4'.self::FORMAT, '-14 months', 'Femelle', 'Gecko léopard', 'Reptile'],
            ['Noisette', 'Hamster doré très actif le soir.', '19.90', self::UNSPLASH.'photo-1425082661705-1834bfd09dca'.self::FORMAT, '-2 months', 'Femelle', 'Hamster doré', 'Rongeur'],
        ];

        foreach ($animaux as [$nom, $description, $prix, $photo, $naissance, $sexe, $race, $espece]) {
            $this->em->persist((new Animal())
                ->setNom($nom)
                ->setDescription($description)
                ->setPrix($prix)
                ->setPhoto($photo)
                ->setDateNaissance($naissance ? new \DateTimeImmutable($naissance) : null)
                ->setSexe($sexe)
                ->setRace($race)
                ->setDisponible(true)
                ->setEspece($especes[$espece]));
        }

        // ---------- Compte de démonstration ----------
        $demo = $this->em->getRepository(Utilisateur::class)->findOneBy(['email' => 'demo@fauna.fr']);
        if (!$demo) {
            $demo = (new Utilisateur())->setEmail('demo@fauna.fr');
            $demo->setPassword($this->hasher->hashPassword($demo, 'demo1234'));
            $this->em->persist($demo);
        }

        $this->em->flush();

        $io->success([
            sprintf('%d produits et %d animaux ajoutés.', count($produits), count($animaux)),
            'Compte de démonstration : demo@fauna.fr / demo1234',
        ]);

        return Command::SUCCESS;
    }
}
