<?php

namespace controller;


use model\Position;
use model\User;
use modelRepository\PositionRepository;

class PositionController extends LoginController
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
        echo $this->render('position/index.php', $params);
    }

    public function insertAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        $params = array();
        $params['menu'] = $this->render('menu/admin_menu.php');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $position = new Position();
            $position->setName($_POST['name']);
            $result = $position->save();

            if (!empty($result)) {
                $params['errors'] = $result;
                $params['position'] = $position;
                $_SESSION['message'] = $this->render('global/alert.php',
                    array('type' => 'danger', 'alertText' => '<strong>Greška</strong> prilikom unosa nove pozicije!'));
            } else {
                $_SESSION['message'] = $this->render('global/alert.php',
                    array('type' => 'success', 'alertText' => "<strong>Uspešno</strong> ste dodali poziciju {$position->getName()}!"));
                header("Location: /position/");
                exit();
            }
        }

        echo $this->render('position/insert.php', $params);
    }

    public function editAction($id)
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        if (!ctype_digit((string)$id)) {
            header("Location: /404notFound/");
            exit();
        }

        $positionRepository = new PositionRepository();
        $position = $positionRepository->loadById($id);

        if (empty($position)) {
            header("Location: /404notFound/");
            exit();
        }

        $params = array();
        $params['menu'] = $this->render('menu/admin_menu.php');
        $params['position'] = $position;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $position->setName($_POST['name']);
            $result = $position->save();

            if (!empty($result)) {
                $params['errors'] = $result;
                $_SESSION['message'] = $this->render('global/alert.php',
                    array('type' => 'danger', 'alertText' => '<strong>Greška</strong> prilikom izmene pozicije!'));
            } else {
                $_SESSION['message'] = $this->render('global/alert.php',
                    array('type' => 'success', 'alertText' => "<strong>Uspešno</strong> ste izmenili poziciju {$position->getName()}!"));
                header("Location: /position/");
                exit();
            }
        }

        echo $this->render('position/edit.php', $params);
    }

    public function deactivateAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        $id = json_decode($_POST['id']);
        if (!ctype_digit((string)$id)) {
            echo json_encode('false');
            exit();
        }

        $positionRepository = new PositionRepository();
        if ($positionRepository->isPositionSelectedInEmployee($id)) {
            echo json_encode('false');
            exit();
        }

        $position = $positionRepository->loadById($id);

        if (empty($position)) {
            echo json_encode('false');
            exit();
        }

        $result = $position->deactivate();
        echo json_encode($result);
    }

    public function getAllPositionsAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR]);

        header('Content-type: application/json');

        $jsonArray = array();
        $positionRepository = new PositionRepository();
        $positions = $positionRepository->load();
        foreach ($positions as $position) {
            $json = array();
            $json['id'] = $position->getId();
            $json['name'] = $position->getName();
            $jsonArray[] = $json;
        }
        $j['data'] = $jsonArray;
        echo json_encode($j, JSON_UNESCAPED_UNICODE);
    }

    public function getAllPositionsByFilterAction()
    {
        $this->accessDenyIfNotIn([User::ADMINISTRATOR, User::EMPLOYEE]);

        header('Content-type: application/json');

        $filter = (!isset($_GET['filter'])) ? '' : trim($_GET['filter']);
        $positionRepository = new PositionRepository();
        $positions = $positionRepository->loadByFilter($filter);
        if (empty($positions)) {
            $response = array('results' => array());
        } else {
            $data = array();
            foreach ($positions as $position) {
                $data[] = array('id' => $position->getId(), 'text' => $position->getName());
            }
            $response = array('results' => $data);
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}