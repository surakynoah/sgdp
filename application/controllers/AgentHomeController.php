<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include (APPPATH. '/libraries/ChromePhp.php');

class AgentHomeController extends CI_Controller {

	public function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

	public function index() {
        if ($_SESSION['type'] != 1) {
			$this->load->view('errors/index.html');
		} else {
			$this->load->view('agentHome');
		}
    }

    // Obtain all requests with with all their documents.
    // NOTICE: sensitive information
    public function getUserRequests() {
        if ($_GET['fetchId'] != $_SESSION['id'] && $_SESSION['type'] > 2) {
            // if fetch id is not the same as logged in user, must be an
            // agent or manager to be able to execute query!
            $this->load->view('errors/index.html');
        } else {
            try {
                $em = $this->doctrine->em;
                // Get configured's max. and min. request amount.
                $em = $this->doctrine->em;
                $config = $em->getRepository('\Entity\Config');
                $result['maxReqAmount'] = $config->findOneBy(array('key' => 'MAX_AMOUNT'))->getValue();
                $result['minReqAmount'] = $config->findOneBy(array('key' => 'MIN_AMOUNT'))->getValue();
                $user = $em->find('\Entity\User', $_GET['fetchId']);
                if ($user === null) {
                    $result['error'] = "La cédula ingresada no se encuentra en la base de datos";
                } else {
                    $requests = $user->getRequests();
                    $requests = array_reverse($requests->getValues());
                    foreach ($requests as $rKey => $request) {
                        $result['requests'][$rKey]['id'] = $request->getId();
                        $result['requests'][$rKey]['creationDate'] = $request->getCreationDate()->format('d/m/y');
                        $result['requests'][$rKey]['comment'] = $request->getComment();
                        $result['requests'][$rKey]['reqAmount'] = $request->getRequestedAmount();
                        $result['requests'][$rKey]['approvedAmount'] = $request->getApprovedAmount();
                        $result['requests'][$rKey]['reunion'] = $request->getReunion();
                        $result['requests'][$rKey]['status'] = $request->getStatus();
						$result['requests'][$rKey]['type'] = $request->getLoanType();
                        $result['requests'][$rKey]['phone'] = $request->getContactNumber();
                        $result['requests'][$rKey]['due'] = $request->getPaymentDue();
                        $result['requests'][$rKey]['email'] = $request->getContactEmail();
                        $result['requests'][$rKey]['validationDate'] = $request->getValidationDate();
                        $docs = $request->getDocuments();
                        foreach ($docs as $dKey => $doc) {
                            $result['requests'][$rKey]['docs'][$dKey]['id'] = $doc->getId();
                            $result['requests'][$rKey]['docs'][$dKey]['name'] = $doc->getName();
                            $result['requests'][$rKey]['docs'][$dKey]['description'] = $doc->getDescription();
                            $result['requests'][$rKey]['docs'][$dKey]['lpath'] = $doc->getLpath();
                        }
                    }
                    $result['message'] = "success";
                }
            } catch (Exception $e) {
                \ChromePhp::log($e);
                $result['message'] = "error";
            }
            echo json_encode($result);
        }
    }

    public function deleteDocument() {
        if ($_SESSION['type'] != 1) {
            $this->load->view('errors/index.html');
        } else {
            try {
				$data = json_decode(file_get_contents('php://input'), true);
                $em = $this->doctrine->em;
                // Delete the document from the server.
                unlink(DropPath . $data['lpath']);
                // Get the specified doc entity
                $doc = $em->find('\Entity\Document', $data['id']);
                // Get it's request.
                $request = $doc->getBelongingRequest();
                // Remove this doc from it's request entity
                $request->removeDocument($doc);
                // Register History
                $history = new \Entity\History();
                $history->setDate(new DateTime('now', new DateTimeZone('America/Barbados')));
                $history->setUserResponsable($_SESSION['name'] . ' ' . $_SESSION['lastName']);
                // 5 = Elimination
                $history->setTitle(5);
                $history->setOrigin($request);
                $request->addHistory($history);
                $em->merge($request);
                // Register it's corresponding action
                $action = new \Entity\HistoryAction();
                $action->setSummary("Eliminación del documento '" . $doc->getName() . "'.");
                $action->setBelongingHistory($history);
                $history->addAction($action);
                $em->persist($action);
                $em->persist($history);
                // Delete the document.
                $em->remove($doc);
                // Persist the changes in database.
                $em->flush();
                $result['message'] = "success";
            } catch (Exception $e) {
                $result['message'] = "error";
                \ChromePhp::log($e);
            }
            echo json_encode($result);
        }
    }
}
