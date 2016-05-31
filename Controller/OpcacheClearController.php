<?php

namespace EnuygunCom\OpcacheClearBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OpcacheClearController extends Controller
{
    /**
     * @Route("/opcache-clear/{version}/", name="_enuygun_com_opcache_clear")
     * @param Request $request
     * @param $version
     * @return array
     */
    public function opcacheClearAction(Request $request, $version)
    {
        $ipFilter = $this->container->getParameter('enuygun_com_opcache_clear.ip_filter');
        if (!empty($ipFilter) && !in_array($request->getClientIp(), $ipFilter))
            return new JsonResponse(array('success' => false, 'message' => $request->getClientIp() . ' is not allowed'), 400, array('x-enuygun-opcache-clear' => json_encode(array('success' => false, 'message' => $request->getClientIp() . ' is not allowed', 'version' => $version))));

        if (!function_exists('opcache_reset')) {
            throw new \RuntimeException('Opcache extension is not enabled.');
        }

        $success = opcache_reset();
        $message = 'Opcache cleared: ' . ($success ? 'success' : 'failed');

        return new JsonResponse(array('success' => $success, 'message' => $message), 200, array('x-enuygun-opcache-clear' => json_encode(array('success' => $success, 'message' => $message, 'version' => $version))));
    }
}
