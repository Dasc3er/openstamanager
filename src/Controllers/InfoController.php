<?php

namespace Controllers;

use Spatie\RouteAttributes\Attributes\Get;

class InfoController extends Controller
{
    #[Get('/api/info')]
    public function about()
    {
        return 'This is the About Page!';
    }
}