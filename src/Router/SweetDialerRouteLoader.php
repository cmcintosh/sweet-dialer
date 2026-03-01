<?php
namespace Wembassy\SweetDialer\Router;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class SweetDialerRouteLoader
{
    public function loadRoutes(): RouteCollection
    {
        $routes = new RouteCollection();
        
        $routes->add('sweetdialer_voice_webhook', new Route('/voice-webhook', [
            '_controller' => 'Wembassy\\SweetDialer\\Controller\\WebhookController::voiceWebhook'
        ], [], [], '', [], ['POST', 'GET']));
        
        $routes->add('sweetdialer_status_callback', new Route('/status-callback', [
            '_controller' => 'Wembassy\\SweetDialer\\Controller\\WebhookController::statusCallback'
        ], [], [], '', [], ['POST']));
        
        $routes->add('sweetdialer_api_dashboard', new Route('/api/dashboard/data', [
            '_controller' => 'Wembassy\\SweetDialer\\Controller\\DashboardController::data'
        ], [], [], '', [], ['GET']));
        
        return $routes;
    }
}
