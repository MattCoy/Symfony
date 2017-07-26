<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;

use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Entity\AdvertRepository;
use OC\PlatformBundle\Entity\Image;
use OC\PlatformBundle\Entity\Application;
use OC\PlatformBundle\Entity\AdvertSkill;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdvertController extends Controller
{
  public function indexAction($page)
  {
    if ($page < 1) {
      // On déclenche une exception NotFoundHttpException, cela va afficher
      // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
      throw new NotFoundHttpException('Page "'.$page.'" inexistante.');
    }
    $nbPerPage = 2;
    
    // liste d'annonces
    $listAdverts = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('OCPlatformBundle:Advert')
        ->getAdverts($page, $nbPerPage)
    ;
    //count($listAdverts) ne retourne pas 5 mais le nombre total d'annonces dans la bdd
    // $listAdverts est un objet paginator
    $nbPages = ceil(count($listAdverts) / $nbPerPage);
    
    if ($page > $nbPages) {
      // On déclenche une exception NotFoundHttpException, cela va afficher
      // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
      throw new NotFoundHttpException('Page "'.$page.'" inexistante.');
    }    
    
    // liste de candidatures
    $listApplications = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('OCPlatformBundle:Application')
        ->findAll()
    ;

    // Et modifiez le 2nd argument pour injecter notre liste
    return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
        'listAdverts' => $listAdverts, 'listApplications' => $listApplications, 
        'nbPages' => $nbPages, 'page' => $page
    ));
  }

  public function viewAction($id)
  {
    $em = $this->getDoctrine()->getManager();

    // On récupère l'annonce $id
    $advert = $em
      ->getRepository('OCPlatformBundle:Advert')
      ->find($id)
    ;

    if (null === $advert) {
      throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
    }

    // On avait déjà récupéré la liste des candidatures
    $listApplications = $em
      ->getRepository('OCPlatformBundle:Application')
      ->findBy(array('advert' => $advert))
    ;

    // On récupère maintenant la liste des AdvertSkill
    $listAdvertSkills = $em
      ->getRepository('OCPlatformBundle:AdvertSkill')
      ->findBy(array('advert' => $advert))
    ;

    return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
      'advert'           => $advert,
      'listApplications' => $listApplications,
      'listAdvertSkills' => $listAdvertSkills
    ));
  }

  public function addAction(Request $request)
  {
        // Création de l'entité Advert
    $advert = new Advert();
    $advert->setTitle('Recherche développeur Silex.');
    $advert->setAuthor('Alexandre');
    $advert->setContent("Nous recherchons un développeur Silex débutant sur Bordeaux. Blabla…");
    
    // Création de l'entité Image
    $image = new Image();
    $image->setUrl('http://gravit.org/wp/wp-content/uploads/2014/04/logo-stickers-680x330.png');
    $image->setAlt('Job de rêve');

    // On lie l'image à l'annonce
    $advert->setImage($image);

    // Création d'une première candidature
    $application1 = new Application();
    $application1->setAuthor('Marine');
    $application1->setContent("J'ai toutes les qualités requises.");

    // Création d'une deuxième candidature par exemple
    $application2 = new Application();
    $application2->setAuthor('Pierre');
    $application2->setContent("Je suis très motivé.");

    // On lie les candidatures à l'annonce
    $application1->setAdvert($advert);
    $application2->setAdvert($advert);

    // On récupère l'EntityManager
    $em = $this->getDoctrine()->getManager();
    
    // On récupère toutes les compétences possibles
    $listSkills = $em->getRepository('OCPlatformBundle:Skill')->findAll();

    // Pour chaque compétence
    foreach ($listSkills as $skill) {
      // On crée une nouvelle « relation entre 1 annonce et 1 compétence »
      $advertSkill = new AdvertSkill();

      // On la lie à l'annonce, qui est ici toujours la même
      $advertSkill->setAdvert($advert);
      // On la lie à la compétence, qui change ici dans la boucle foreach
      $advertSkill->setSkill($skill);

      // Arbitrairement, on dit que chaque compétence est requise au niveau 'Expert'
      $advertSkill->setLevel('Expert');

      // Et bien sûr, on persiste cette entité de relation, propriétaire des deux autres relations
      $em->persist($advertSkill);
    }

    // Étape 1 : On « persiste » l'entité
    $em->persist($advert);

    // Étape 1 ter : pour cette relation pas de cascade lorsqu'on persiste Advert, car la relation est
    // définie dans l'entité Application et non Advert. On doit donc tout persister à la main ici.
    $em->persist($application1);
    $em->persist($application2);

    // Étape 2 : On « flush » tout ce qui a été persisté avant
    $em->flush();

    // Reste de la méthode qu'on avait déjà écrit
    if ($request->isMethod('POST')) {
      $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

      // Puis on redirige vers la page de visualisation de cettte annonce
      return $this->redirectToRoute('oc_platform_view', array('id' => $advert->getId()));
    }

    // Si on n'est pas en POST, alors on affiche le formulaire
    return $this->render('OCPlatformBundle:Advert:add.html.twig', array('advert' => $advert));
  }

    public function editAction($id, Request $request)
    {
      $em = $this->getDoctrine()->getManager();

      // On récupère l'annonce $id
      $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

      if (null === $advert) {
        throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
      }

      // La méthode findAll retourne toutes les catégories de la base de données
      $listCategories = $em->getRepository('OCPlatformBundle:Category')->findAll();

      // On boucle sur les catégories pour les lier à l'annonce
      foreach ($listCategories as $category) {
        $advert->addCategory($category);
      }

      // Pour persister le changement dans la relation, il faut persister l'entité propriétaire
      // Ici, Advert est le propriétaire, donc inutile de la persister car on l'a récupérée depuis Doctrine

      // Étape 2 : On déclenche l'enregistrement
      $em->flush();

      return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
        'advert' => $advert
      ));
    }

    public function editImageAction($advertId)
    {
      $em = $this->getDoctrine()->getManager();

      // On récupère l'annonce
      $advert = $em->getRepository('OCPlatformBundle:Advert')->find($advertId);

      // On modifie l'URL de l'image par exemple
      $advert->getImage()->setUrl('test.png');

      // On n'a pas besoin de persister l'annonce ni l'image.
      // Rappelez-vous, ces entités sont automatiquement persistées car
      // on les a récupérées depuis Doctrine lui-même

      // On déclenche la modification
      $em->flush();

      return new Response('OK');
    }

  public function deleteAction(Request $request, $id)
  {
    $em = $this->getDoctrine()->getManager();

    // On récupère l'annonce $id
    $advert = $em->getRepository('OCPlatformBundle:Advert')->find($id);

    if (null === $advert) {
      throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
    }

    // On boucle sur les catégories de l'annonce pour les supprimer
    foreach ($advert->getCategories() as $category) {
      $advert->removeCategory($category);
    }
    //On boucle sur les skills pour les supprimer
    $listSkillsFromAdvert = $em->getRepository('OCPlatformBundle:AdvertSkill')->findBy(array('advert'=>$advert->getId()));

    // Pour chaque compétence de cette annonce
    foreach ($listSkillsFromAdvert as $skillA) {    
      // on supprime
      $em->remove($skillA);
    }
    
    //On boucle sur les candidatures pour les supprimer
    $listApplicationsFromAdvert = $em->getRepository('OCPlatformBundle:Application')->findBy(array('advert'=>$advert->getId()));

    // Pour chaque compétence de cette annonce
    foreach ($listApplicationsFromAdvert as $application) {    
      // on supprime
      $em->remove($application);
    }
    
    $em->remove($advert);
    
    // On déclenche la modification
    $em->flush();

    $session = $request->getSession();
    //création du message
    $session->getFlashBag()->add('info', 'annonce supprimée!');

    // Puis on redirige vers la page d'accueil
    return $this->redirectToRoute('oc_platform_home');
  }
  
  public function menuAction($limit)
  {
    // liste d'annonces
    $listAdverts = $this
        ->getDoctrine()
        ->getManager()
        ->getRepository('OCPlatformBundle:Advert')
        ->findBy(array(), array('date'=>'DESC'), $limit, 0)
    ;

    return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
      // Tout l'intérêt est ici : le contrôleur passe
      // les variables nécessaires au template !
      'listAdverts' => $listAdverts
    ));
  }

  
}
