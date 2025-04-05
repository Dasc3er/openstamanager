<?php

namespace Controllers;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class InfoController
{
    #[Route('/info', name: 'info')]
    public function about(): Response
    {
        return new Response('This is the About Page!');
    }
}