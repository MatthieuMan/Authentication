<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class HomepageController extends AbstractController
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/homepage", name="homepage")
     */
    public function index(): Response
    {
        if (!$this->security->getUser()) {
            return $this->redirectToRoute('landing_page');
        }

        return $this->render('homepage/homepage.html.twig');
    }
}
