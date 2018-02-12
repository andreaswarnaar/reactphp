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
     * @Route(name="indexKip", path="/kip")
     * @return Response
     */
    public function indexAction() {
        return new Response('Kip');
    }

    /**
     * @Route(name="apiBase", path="/api")
     * @return JsonResponse
     */
    public function apiAction() {
        return new JsonResponse([]);
    }
}