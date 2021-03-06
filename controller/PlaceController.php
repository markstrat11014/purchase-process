<?php

namespace controller;


use model\Place;
use model\User;
use modelRepository\PlaceRepository;

class PlaceController extends LoginController
{
    public function __construct()
    {
        $this->notLoggedIn();
    }

    public function indexAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        $params = array();
        $params['menu'] = $this->render('menu/admin_menu.php');
        echo $this->render('place/index.php', $params);
    }

    public function insertAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        $params = array();
        $params['menu'] = $this->render('menu/admin_menu.php');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $place = new Place();

            $place->setZipCode($_POST['zip-code']);
            $place->setName($_POST['name']);

            $result = $place->save();

            if (!empty($result)) {
                $params['errors'] = $result;
                $params['place'] = $place;
                $_SESSION['message'] = $this->render('global/alert.php',
                    array('type' => 'danger', 'alertText' => '<strong>Greška</strong> prilikom unosa novog mesta!'));
            } else {
                $_SESSION['message'] = $this->render('global/alert.php',
                    array('type' => 'success', 'alertText' => "<strong>Uspešno</strong> ste dodali mesto {$place->getZipCode()} {$place->getName()}!"));
                header("Location: /place/");
                exit();
            }
        }

        echo $this->render('place/insert.php', $params);
    }

    public function editAction($id)
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        if (!ctype_digit((string)$id)) {
            header("Location: /404notFound/");
            exit();
        }

        $placeRepository = new PlaceRepository();
        $place = $placeRepository->loadById($id);

        if (empty($place)) {
            header("Location: /404notFound/");
            exit();
        }

        $params = array();
        $params['menu'] = $this->render('menu/admin_menu.php');
        $params['place'] = $place;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $place->setZipCode($_POST['zip-code']);
            $place->setName($_POST['name']);
            $result = $place->save();

            if (!empty($result)) {
                $params['errors'] = $result;
                $_SESSION['message'] = $this->render('global/alert.php',
                    array('type' => 'danger', 'alertText' => '<strong>Greška</strong> prilikom izmene mesta!'));
            } else {
                $_SESSION['message'] = $this->render('global/alert.php',
                    array('type' => 'success', 'alertText' => "<strong>Uspešno</strong> ste izmenili mesto {$place->getZipCode()} {$place->getName()}!"));
                header("Location: /place/");
                exit();
            }
        }

        echo $this->render('place/edit.php', $params);
    }

    public function deactivateAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        $id = json_decode($_POST['id']);
        if (!ctype_digit((string)$id)) {
            echo json_encode('false');
            exit();
        }

        $placeRepository = new PlaceRepository();
        if ($placeRepository->isPlaceSelectedInSupplier($id)) {
            echo json_encode('false');
            exit();
        }

        $place = $placeRepository->loadById($id);

        if (empty($place)) {
            echo json_encode('false');
            exit();
        }

        $result = $place->deactivate();
        echo json_encode($result);
    }

    public function getAllPlacesAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        header('Content-type: application/json');

        $jsonArray = array();
        $placeRepository = new PlaceRepository();
        $places = $placeRepository->load();
        foreach ($places as $place) {
            $json = array();
            $json['id'] = $place->getId();
            $json['zipCode'] = $place->getZipCode();
            $json['name'] = $place->getName();
            $jsonArray[] = $json;
        }
        $j['data'] = $jsonArray;
        echo json_encode($j, JSON_UNESCAPED_UNICODE);
    }

    public function getAllPlacesByFilterAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR, User::SUPPLIER]);

        header('Content-type: application/json');

        $filter = (!isset($_GET['filter'])) ? '' : trim($_GET['filter']);
        $placeRepository = new PlaceRepository();
        $places = $placeRepository->loadByFilter($filter);
        if (empty($places)) {
            $response = array('results' => array());
        } else {
            $data = array();
            foreach ($places as $place) {
                $data[] = array('id' => $place->getId(), 'text' => $place->getZipCode() . ' ' . $place->getName());
            }
            $response = array('results' => $data);
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}