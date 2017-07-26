<?php

namespace CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends Controller
{
    public function indexAction()
    {
        

        // Et modifiez le 2nd argument pour injecter notre liste
        return $this->render('CoreBundle:Home:index.html.twig');
    }
    
    public function contactAction(Request $request)
    {
        $session = $request->getSession();
        //création du message
        $session->getFlashBag()->add('notice', 'la page de contact arrive bientôt!');

        // Puis on redirige vers la page d'accueil
        return $this->redirectToRoute('core_homepage');
    }
}
