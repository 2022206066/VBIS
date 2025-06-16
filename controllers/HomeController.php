<?php

namespace app\controllers;

use app\core\Application;
use app\core\BaseController;
use app\models\SatelliteModel;

class HomeController extends BaseController
{
    public function home()
    {
        $satelliteModel = new SatelliteModel();
        $satellitesJs = $satelliteModel->getSatellitesAsJsArray();
        
        $this->view->render('home', 'main', [
            'satellitesJs' => $satellitesJs
        ]);
    }

    public function accessRole(): array
    {
        return ['User', 'Administrator']; // Both regular users and administrators can access the home page
    }
} 