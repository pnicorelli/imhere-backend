<?php

namespace ImHere\Routes;

use Symfony\Component\HttpFoundation\JsonResponse;

class Root
{
    public function root()
    {
            $data = json_decode(file_get_contents('composer.json'), $assoc=true);
            return new JsonResponse([
                'name' => $data['name'],
                'version' => $data['version']
            ]);
    }

}
