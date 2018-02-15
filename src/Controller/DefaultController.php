<?php
/**
 * User: Andreas Warnaar
 * Date: 28-12-17
 * Time: 22:59
 */

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends Controller
{
    /**
     * Test all
     * @Route(name="indexKip", path="/kip")
     * @return Response
     */
    public function indexAction() {
        $kip = new \stdClass();
        return new Response('Karel');

    }

    /**
     * @Route(name="apiBase", path="/api")
     * @return JsonResponse
     */
    public function apiAction() {
        return new JsonResponse([]);
    }
}