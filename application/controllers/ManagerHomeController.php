<?php
defined('BASEPATH') OR exit('No direct script access allowed');
include (APPPATH. '/libraries/ChromePhp.php');

class ManagerHomeController extends CI_Controller {

	public function __construct() {
        parent::__construct();
        $this->load->library('session');
    }

	public function index() {
		if ($_SESSION['type'] != 2) {
			$this->load->view('errors/index.html');
		} else {
			$this->load->view('managerHome');
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
                $result = null;
				$em = $this->doctrine->em;
				$user = $em->find('\Entity\User', $_GET['fetchId']);
				if ($user === null) {
					$result['error'] = "La cédula ingresada no se encuentra en la base de datos";
				} else {
					$requests = $user->getRequests();
					if ($requests->isEmpty()) {
						$result['error'] = "El usuario no posee solicitudes";
					} else {
						$rKey = 0;
                        $statuses = $this->getAllStatuses();
                        $statusCounter = array();
                        foreach ($statuses as $status) {
                            // Initialize counter array
                            $statusCounter[$status] = 0;
                        }
						foreach ($requests as $request) {
							if ($request->getValidationDate() === null) continue;
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
							// Gather pie chart information
                            $statusCounter[$request->getStatus()] ++;
							// Gather up report information
							$result['report']['data'][$rKey] = array(
								$rKey+1,
								$request->getId(),
								$this->mapLoanType($request->getLoanType()),
								$request->getCreationDate()->format('d/m/Y'),
								$request->getStatus(),
								$request->getReunion(),
								$request->getRequestedAmount(),
								$request->getApprovedAmount(),
								$request->getComment()
							);
							$rKey++;
						}
						if ($result['requests'] == null) {
							$result['error'] = 'Este afiliado no tiene solicitudes validadas';
						} else {
                            // Fill up pie chart information
                            $result['pie']['title'] = "Estadísticas de solicitudes para el afiliado";
                            $total = array_sum($statusCounter);
                            $result['pie']['backgroundColor'] = [];
                            foreach ($statuses as $sKey => $status) {
                                $result['pie']['labels'][$sKey] = $status;
                                $result['pie']['data'][$sKey] = round($statusCounter[$status] * 100 / $total, 2);
                                $result['pie']['backgroundColor'][$sKey] =
                                    $this->generatePieBgColor($status, $result['pie']['backgroundColor']);
                                $result['pie']['hoverBackgroundColor'][$sKey] =
                                    $this->generatePieHoverColor($result['pie']['backgroundColor'][$sKey]);
                            }
                            // Fill up report information
                            $applicant = $user->getId() . ' - ' . $user->getFirstName() . ' ' . $user->getLastName();
                            $now =
                                (new DateTime('now', new DateTimeZone('America/Barbados')))->format('d/m/Y - h:i:sa');
                            $result['report']['header'] = array(
                                array("SGDP - IPAPEDI"),
                                array("FECHA Y HORA DE GENERACIÓN DE REPORTE: " . $now)
                            );
                            $result['report']['dataTitle'] = "SOLICITUDES DEL AFILIADO " . strtoupper($applicant);
                            $result['report']['filename'] = $result['report']['dataTitle'];
                            $result['report']['dataHeader'] = array(
                                'Nro.',
                                'Identificador',
                                'Tipo',
                                'Fecha de creación',
                                'Estatus',
                                'Nro. de Reunión',
                                'Monto solicitado (Bs)',
                                'Monto aprobado (Bs)',
                                'Comentario'
                            );
                            $result['report']['total'] = array(
                                array("Monto solicitado total", ""),
                                array("Monto aprobado total", "")
                            );
                            $result['report']['stats']['title'] = "ESTADÍSTICAS DE SOLICITUDES DEL AFILIADO";
                            $result['report']['stats']['dataHeader'] = array('Estatus', 'Cantidad', 'Porcentaje');
                            foreach ($statuses as $sKey => $status) {
                                $result['report']['stats']['data'][$sKey] = array($status, '', '');
                            }
                            $result['message'] = "success";
                        }
					}
				}
			} catch (Exception $e) {
				\ChromePhp::log($e);
				$result['message'] = "error";
			}
			echo json_encode($result);
		}
	}

    public function fetchPendingRequests() {
        if ($_SESSION['type'] != 2) {
            $this->load->view('errors/index.html');
        } else {
            $result = null;
            try {
                $em = $this->doctrine->em;
                // Look for all requests with the specified status
                $requestsRepo = $em->getRepository('\Entity\Request');
                $statuses = $this->getAdditionalStatuses();
                array_push($statuses, 'Recibida');
                $requests = $requestsRepo->findBy(array("status" => $statuses));
                if (empty($requests)) {
                    $result['error'] = "No se encontraron solicitudes pendientes.";
                } else {
                    $rKey = 0;
                    foreach ($requests as $request) {
                        if ($request->getValidationDate() === null) continue;
                        $user = $request->getUserOwner();
                        $result['requests'][$rKey]['id'] = $request->getId();
                        $result['requests'][$rKey]['creationDate'] = $request->getCreationDate()->format('d/m/y');
                        $result['requests'][$rKey]['comment'] = $request->getComment();
                        $result['requests'][$rKey]['reqAmount'] = $request->getRequestedAmount();
                        $result['requests'][$rKey]['approvedAmount'] = $request->getApprovedAmount();
                        $result['requests'][$rKey]['reunion'] = $request->getReunion();
                        $result['requests'][$rKey]['status'] = $request->getStatus();
                        $result['requests'][$rKey]['userOwner'] = $user->getId();
                        $result['requests'][$rKey]['showList'] = false;
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
                        $rKey++;
                    }
                    if ($result['requests'] == null) {
                        $result['error'] = 'Este afiliado no posee solicitudes validadas';
                    } else {
                        $result['message'] = "success";
                    }
                }
            } catch (Exception $e) {
                \ChromePhp::log($e);
                $result['message'] = "error";
            }

            echo json_encode($result);
        }
    }

    public function fetchRequestsByStatus() {
        if ($_SESSION['type'] != 2) {
            $this->load->view('errors/index.html');
        } else {
            $result = null;
            try {
                $em = $this->doctrine->em;
                // Look for all requests with the specified status
                $status = $_GET['status'];
				$requestsRepo = $em->getRepository('\Entity\Request');
                $requests = $requestsRepo->findBy(array("status" => $status));
                if (empty($requests)) {
                    $result['error'] = "No se encontraron solicitudes con estatus " . $_GET['status'];
                } else {
					$rKey = 0;
                    $statuses = $this->getAllStatuses();
                    $statusCounter = array();
                    foreach ($statuses as $status) {
                        // Initialize counter array
                        $statusCounter[$status] = 0;
                    }
                    foreach ($requests as $request) {
						if ($request->getValidationDate() === null) continue;
						$user = $request->getUserOwner();
                        $result['requests'][$rKey]['id'] = $request->getId();
                        $result['requests'][$rKey]['creationDate'] = $request->getCreationDate()->format('d/m/y');
                        $result['requests'][$rKey]['comment'] = $request->getComment();
                        $result['requests'][$rKey]['reqAmount'] = $request->getRequestedAmount();
                        $result['requests'][$rKey]['approvedAmount'] = $request->getApprovedAmount();
                        $result['requests'][$rKey]['reunion'] = $request->getReunion();
                        $result['requests'][$rKey]['status'] = $request->getStatus();
                        $result['requests'][$rKey]['userOwner'] = $user->getId();
                        $result['requests'][$rKey]['showList'] = false;
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
						// Gather up report information
						$result['report']['data'][$rKey] = array(
							$rKey+1,
							$request->getId(),
							$this->mapLoanType($request->getLoanType()),
							$user->getId() . ' - ' . $user->getFirstName() . ' ' . $user->getLastName(),
							$request->getCreationDate()->format('d/m/Y')
						);
						if ($_GET['status'] === "Aprobada" || $_GET['status'] === 'Rechazada') {
							array_push($result['report']['data'][$rKey], $request->getReunion());
						}
						array_push($result['report']['data'][$rKey], $request->getRequestedAmount());
						if ($_GET['status'] === "Aprobada") {
							array_push($result['report']['data'][$rKey], $request->getApprovedAmount());
						}
						array_push($result['report']['data'][$rKey], $request->getComment());
						$rKey++;
                    }
                    if ($result['requests'] == null) {
                        $result['error'] = 'Este afiliado no posee solicitudes validadas';
                    } else {
                        // Get requests status statistics.
                        foreach ($statuses as $status) {
                            $statusCounter[$status] = count($requestsRepo->findBy(array("status" => $status)));
                        }
                        // Fill up pie chart information
                        $result['pie']['title'] = "Estadísticas de solicitudes del sistema";
                        $total = array_sum($statusCounter);
                        $result['pie']['backgroundColor'] = [];
                        foreach ($statuses as $sKey => $status) {
                            $result['pie']['labels'][$sKey] = $status;
                            $result['pie']['data'][$sKey] = round($statusCounter[$status] * 100 / $total, 2);
                            $result['pie']['backgroundColor'][$sKey] =
                                $this->generatePieBgColor($status, $result['pie']['backgroundColor']);
                            $result['pie']['hoverBackgroundColor'][$sKey] =
                                $this->generatePieHoverColor($result['pie']['backgroundColor'][$sKey]);
                        }
                        // Fill up report information
                        $dataHeader = array(
                            'Nro.',
                            'Identificador',
                            'Tipo',
                            'Solicitante',
                            'Fecha de creación'
                        );
                        if ($_GET['status'] === "Aprobada" || $_GET['status'] === 'Rechazada') {
                            array_push($dataHeader, 'Nro. de Reunión');
                        }
                        array_push($dataHeader, 'Monto solicitado (Bs)');
                        if ($_GET['status'] === "Aprobada") {
                            array_push($dataHeader, 'Monto aprobado (Bs)');
                        }
                        array_push($dataHeader, 'Comentario');

                        $now = (new DateTime('now', new DateTimeZone('America/Barbados')))->format('d/m/Y - h:i:sa');
                        $result['report']['header'] = array(
                            array("SGDP - IPAPEDI"),
                            array("FECHA Y HORA DE GENERACIÓN DE REPORTE: " . $now)
                        );
                        $result['report']['dataTitle'] = "SOLICITUDES EN ESTATUS '" . strtoupper($_GET['status'] . "'");
                        $result['report']['dataHeader'] = $dataHeader;
                        $result['report']['total'] = array(
                            array("Monto solicitado total", "")
                        );
                        if ($_GET['status'] === "Aprobada") {
                            array_push($result['report']['total'], array(
                                                                     "Monto aprobado total",
                                                                     "")
                            );
                        }
                        $result['message'] = "success";
                    }
				}
            } catch (Exception $e) {
                \ChromePhp::log($e);
                $result['message'] = "error";
            }

            echo json_encode($result);
        }
    }

    public function fetchRequestsByDateInterval() {
        if ($_SESSION['type'] != 2) {
            $this->load->view('errors/index.html');
        } else {
            $result = null;
            try {
                // from first second of the day
                $from = date_create_from_format(
                    'd/m/Y H:i:s',
                    $_GET['from'] . ' ' . '00:00:00',
                    new DateTimeZone('America/Barbados')
                );
                // to last second of the day
                $to = date_create_from_format(
                    'd/m/Y H:i:s',
                    $_GET['to'] . ' ' . '23:59:59',
                    new DateTimeZone('America/Barbados')
                );
                $em = $this->doctrine->em;
                $query = $em->createQuery('SELECT t FROM \Entity\Request t WHERE t.creationDate BETWEEN ?1 AND ?2');
                $query->setParameter(1, $from);
                $query->setParameter(2, $to);
                $requests = $query->getResult();
				// Days will be used below to determine pie title
				$interval = $from->diff($to);
				$days = $interval->format("%a");
                if (empty($requests)) {
                    if ($days > 0) {
                        $result['error'] = "No se han encontrado solicitudes para el rango de fechas especificado";
                    } else {
                        $result['error'] = "No se han encontrado solicitudes para la fecha especificada";
                    }
                } else {
					$rKey = 0;
                    $statuses = $this->getAllStatuses();
                    $statusCounter = array();
                    foreach ($statuses as $status) {
                        // Initialize counter array
                        $statusCounter[$status] = 0;
                    }
                    foreach ($requests as $request) {
						if ($request->getValidationDate() === null) continue;
						$user = $request->getUserOwner();
                        $result['requests'][$rKey]['id'] = $request->getId();
                        $result['requests'][$rKey]['creationDate'] = $request->getCreationDate()->format('d/m/y');
                        $result['requests'][$rKey]['comment'] = $request->getComment();
                        $result['requests'][$rKey]['reqAmount'] = $request->getRequestedAmount();
                        $result['requests'][$rKey]['approvedAmount'] = $request->getApprovedAmount();
                        $result['requests'][$rKey]['reunion'] = $request->getReunion();
                        $result['requests'][$rKey]['status'] = $request->getStatus();
                        $result['requests'][$rKey]['userOwner'] = $user->getId();
                        $result['requests'][$rKey]['showList'] = false;
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
                        // Gather pie chart information
                        $statusCounter[$request->getStatus()] ++;
						// Gather up report information
						$result['report']['data'][$rKey] = array(
							$rKey+1,
							$request->getId(),
							$this->mapLoanType($request->getLoanType()),
							$user->getId() . ' - ' . $user->getFirstName() . ' ' . $user->getLastName(),
							$request->getCreationDate()->format('d/m/Y'),
							$request->getStatus(),
							$request->getReunion(),
							$request->getRequestedAmount(),
							$request->getApprovedAmount(),
							$request->getComment()
						);
						$rKey++;
                    }
                    if ($result['requests'] == null) {
                        $result['error'] = 'Este afiliado no posee solicitudes validadas';
                    } else {
                        // Fill up pie chart information
                        $result['pie']['title'] = $days > 0 ? (
                        "Estadísticas de solicitudes para el intervalo de fechas especificado") : (
                        "Estadísticas de solicitudes para la fecha especificada"
                        );
                        $total = array_sum($statusCounter);
                        $result['pie']['backgroundColor'] = [];
                        foreach ($statuses as $sKey => $status) {
                            $result['pie']['labels'][$sKey] = $status;
                            $result['pie']['data'][$sKey] = round($statusCounter[$status] * 100 / $total, 2);
                            $result['pie']['backgroundColor'][$sKey] =
                                $this->generatePieBgColor($status, $result['pie']['backgroundColor']);
                            $result['pie']['hoverBackgroundColor'][$sKey] =
                                $this->generatePieHoverColor($result['pie']['backgroundColor'][$sKey]);
                        }
                        // Fill up report information
                        $now = (new DateTime('now', new DateTimeZone('America/Barbados')))->format('d/m/Y - h:i:sa');
                        $result['report']['header'] = array(
                            array("SGDP - IPAPEDI"),
                            array("FECHA Y HORA DE GENERACIÓN DE REPORTE: " . $now)
                        );
                        $interval = $days > 0 ? "DEL " . $from->format('d/m/Y') . " HASTA EL " . $to->format('d/m/Y') :
                            "EL " . $to->format('d/m/Y');
                        $filename = $days > 0 ? "DEL " . $from->format('d-m-Y') . " HASTA EL " . $to->format('d-m-Y') :
                            "EL " . $to->format('d-m-Y');
                        $result['report']['filename'] = "SOLICITUDES REALIZADAS " . $filename;
                        $result['report']['dataTitle'] = "SOLICITUDES REALIZADAS " . $interval;
                        $result['report']['dataHeader'] = array(
                            'Nro.',
                            'Identificador',
                            'Tipo',
                            'Solicitante',
                            'Fecha de creación',
                            'Estatus',
                            'Nro. de Reunión',
                            'Monto solicitado (Bs)',
                            'Monto aprobado (Bs)',
                            'Comentario'
                        );
                        $result['report']['total'] = array(
                            array("Monto solicitado total", ""),
                            array("Monto aprobado total", "")
                        );
                        $result['report']['stats']['title'] = "ESTADÍSTICAS DE SOLICITUDES";
                        $result['report']['stats']['dataHeader'] = array('Estatus', 'Cantidad', 'Porcentaje');
                        foreach ($statuses as $sKey => $status) {
                            $result['report']['stats']['data'][$sKey] = array($status, '', '');
                        }
                        $result['message'] = "success";
                    }
                }
            } catch (Exception $e) {
                \ChromePhp::log($e);
                $result['message'] = "error";
            }
            echo json_encode($result);
        }
    }

	public function fetchRequestsByLoanType() {
		if ($_SESSION['type'] != 2) {
			$this->load->view('errors/index.html');
		} else {
            $result = null;
			try {
				$em = $this->doctrine->em;
				// Look for all requests with the specified loan type.
				$loanType = $_GET['loanType'];
				$requestsRepo = $em->getRepository('\Entity\Request');
				$requests = $requestsRepo->findBy(array("loanType" => $loanType));
				if (empty($requests)) {
					$result['error'] = "No se encontraron solicitudes del tipo " . $this->mapLoanType($loanType);
				} else {
					$rKey = 0;
                    $statuses = $this->getAllStatuses();
                    $statusCounter = array();
                    foreach ($statuses as $status) {
                        // Initialize counter array
                        $statusCounter[$status] = 0;
                    }
					foreach ($requests as $request) {
						if ($request->getValidationDate() === null) continue;
						$user = $request->getUserOwner();
						$result['requests'][$rKey]['id'] = $request->getId();
						$result['requests'][$rKey]['creationDate'] = $request->getCreationDate()->format('d/m/y');
						$result['requests'][$rKey]['comment'] = $request->getComment();
						$result['requests'][$rKey]['reqAmount'] = $request->getRequestedAmount();
						$result['requests'][$rKey]['approvedAmount'] = $request->getApprovedAmount();
						$result['requests'][$rKey]['reunion'] = $request->getReunion();
						$result['requests'][$rKey]['status'] = $request->getStatus();
						$result['requests'][$rKey]['userOwner'] = $user->getId();
						$result['requests'][$rKey]['showList'] = false;
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
						// Gather pie chart information
                        $statusCounter[$request->getStatus()] ++;
                        // Gather up report information
						$result['report']['data'][$rKey] = array(
							$rKey+1,
							$request->getId(),
							$user->getId() . ' - ' . $user->getFirstName() . ' ' . $user->getLastName(),
							$request->getCreationDate()->format('d/m/Y'),
							$request->getStatus(),
							$request->getReunion(),
							$request->getRequestedAmount(),
							$request->getApprovedAmount(),
							$request->getComment()
						);
						$rKey++;
					}
                    if ($result['requests'] == null) {
                        $result['error'] = 'Este afiliado no posee solicitudes validadas';
                    } else {
                        // Fill up pie chart information
                        $result['pie']['title'] = "Solicitudes de " . $this->mapLoanType($loanType);
                        $total = array_sum($statusCounter);
                        $result['pie']['backgroundColor'] = [];
                        foreach ($statuses as $sKey => $status) {
                            $result['pie']['labels'][$sKey] = $status;
                            $result['pie']['data'][$sKey] = round($statusCounter[$status] * 100 / $total, 2);
                            $result['pie']['backgroundColor'][$sKey] =
                                $this->generatePieBgColor($status, $result['pie']['backgroundColor']);
                            $result['pie']['hoverBackgroundColor'][$sKey] =
                                $this->generatePieHoverColor($result['pie']['backgroundColor'][$sKey]);
                        }
                        // Fill up report information
                        $now = (new DateTime('now', new DateTimeZone('America/Barbados')))->format('d/m/Y - h:i:sa');
                        $result['report']['header'] = array(
                            array("SGDP - IPAPEDI"),
                            array("FECHA Y HORA DE GENERACIÓN DE REPORTE: " . $now)
                        );
                        $result['report']['filename'] = "SOLICITUDES DE '" . $this->mapLoanType($loanType) . "'";
                        $result['report']['dataTitle'] = $result['report']['filename'];
                        $result['report']['dataHeader'] = array(
                            'Nro.',
                            'Identificador',
                            'Solicitante',
                            'Fecha de creación',
                            'Estatus',
                            'Nro. de Reunión',
                            'Monto solicitado (Bs)',
                            'Monto aprobado (Bs)',
                            'Comentario'
                        );
                        $result['report']['total'] = array(
                            array("Monto solicitado total", ""),
                            array("Monto aprobado total", "")
                        );
                        $result['report']['stats']['title'] = "ESTADÍSTICAS DE SOLICITUDES";
                        $result['report']['stats']['dataHeader'] = array('Estatus', 'Cantidad', 'Porcentaje');
                        foreach ($statuses as $sKey => $status) {
                            $result['report']['stats']['data'][$sKey] = array($status, '', '');
                        }
                        $result['message'] = "success";
                    }
				}
			} catch (Exception $e) {
				\ChromePhp::log($e);
				$result['message'] = "error";
			}

			echo json_encode($result);
		}
	}

    public function getApprovedAmountByDateInterval() {
        if ($_SESSION['type'] != 2) {
            $this->load->view('errors/index.html');
        } else {
            try {
                // Compute approved amount within specified time
                // from first second of the day
                $from = date_create_from_format(
                    'd/m/Y H:i:s',
                    $_GET['from'] . ' ' . '00:00:00',
                    new DateTimeZone('America/Barbados')
                );
                // to last second of the day
                $to = date_create_from_format(
                    'd/m/Y H:i:s',
                    $_GET['to'] . ' ' . '23:59:59',
                    new DateTimeZone('America/Barbados')
                );
                $em = $this->doctrine->em;
                $qb = $em->createQueryBuilder();
				$qb->select(array('h'))
					->from('\Entity\History', 'h')
					->where($qb->expr()->andX(
						$qb->expr()->eq('h.title', '?1'),
						$qb->expr()->between('h.date', '?2', '?3')
					));
                $qb->setParameter(1, 4);
				$qb->setParameter(2, $from);
                $qb->setParameter(3, $to);
                $history = $qb->getQuery()->getResult();
				$result['approvedAmount'] = $count = 0;
				foreach ($history as $h) {
					$request = $h->getOrigin();
					if ($request->getStatus() === "Aprobada") {
						if (!isset($evaluated[$request->getId()])) {
							// Perform all approved amount's computation
							$evaluated[$request->getId()] = true;
							$count++;
							if ($request->getApprovedAmount() !== null) {
								$result['approvedAmount'] += $request->getApprovedAmount();
							}
						}
					}
				}
                if (!$count) {
                    $interval = $from->diff($to);
                    $days = $interval->format("%a");
                    if ($days > 0) {
                        $result['error'] = "No se han encontrado solicitudes cerradas en el rango de fechas especificado";
                    } else {
                        $result['error'] = "No se han encontrado solicitudes cerradas en la fecha especificada";
                    }
                } else {
					$result['message'] = "success";
				}
            } catch (Exception $e) {
                \ChromePhp::log($e);
                $result['message'] = "error";
            }
            echo json_encode($result);
        }
    }

    public function getApprovedAmountById() {
        if ($_SESSION['type'] != 2) {
            $this->load->view('errors/index.html');
        } else {
            try {
                $em = $this->doctrine->em;
                $user = $em->find('\Entity\User', $_GET['userId']);
                if ($user === null) {
                    $result['error'] = "La cédula ingresada no se encuentra en la base de datos";
                } else {
                    $requests = $user->getRequests();
                    if ($requests->isEmpty()) {
                        $result['error'] = "El usuario especificado no posee solicitudes";
                    } else {
                        // Perform all approved amount's computation
                        $result['approvedAmount'] = 0;
                        $result['username'] = $user->getFirstName() . ' ' . $user->getLastName();
                        foreach ($requests as $rKey => $request) {
                            if ($request->getApprovedAmount() !== null) {
                                $result['approvedAmount'] += $request->getApprovedAmount();
                            }
                        }
                        $result['message'] = "success";
                    }
                }
            } catch (Exception $e) {
                \ChromePhp::log($e);
                $result['message'] = "error";
            }
            echo json_encode($result);
        }
    }

	public function getClosedReportByDateInterval() {
		if ($_SESSION['type'] != 2) {
			$this->load->view('errors/index.html');
		} else {
			try {
				// Compute approved amount within specified time
				// from first second of the day
				$from = date_create_from_format(
					'd/m/Y H:i:s',
					$_GET['from'] . ' ' . '00:00:00',
					new DateTimeZone('America/Barbados')
				);
				// to last second of the day
				$to = date_create_from_format(
					'd/m/Y H:i:s',
					$_GET['to'] . ' ' . '23:59:59',
					new DateTimeZone('America/Barbados')
				);
				$em = $this->doctrine->em;
				$qb = $em->createQueryBuilder();
				$qb->select(array('h'))
					->from('\Entity\History', 'h')
					->where($qb->expr()->andX(
						$qb->expr()->eq('h.title', '?1'),
						$qb->expr()->between('h.date', '?2', '?3')
					));
				$qb->setParameter(1, 4); // 4 = close
				$qb->setParameter(2, $from);
				$qb->setParameter(3, $to);
				$history = $qb->getQuery()->getResult();
				$count = 0;
				$evaluated = [];
				foreach ($history as $h) {
					$request = $h->getOrigin();
					$userOwner = $request->getUserOwner();
					if (!isset($evaluated[$request->getId()])) {
						// Gather up report information
						$evaluated[$request->getId()] = true;
						$count++;
						$result['report']['data'][$count] = array(
							$count,
							$request->getId(),
							$this->mapLoanType($request->getLoanType()),
							$userOwner->getId() . ' - ' . $userOwner->getFirstName() . ' ' . $userOwner->getLastName(),
							$request->getCreationDate()->format('d/m/Y'),
							$request->getStatus(),
							$h->getUserResponsable(),
							$request->getReunion(),
							$request->getRequestedAmount(),
							$request->getApprovedAmount(),
						);
						// Add report generation action to history
						$newLog = new \Entity\History();
						$newLog->setDate(new DateTime('now', new DateTimeZone('America/Barbados')));
						$newLog->setUserResponsable($_SESSION['name'] . ' ' . $_SESSION['lastName']);
						// 6 = report generation
						$newLog->setTitle(6);
						$newLog->setOrigin($request);
						$request->addHistory($newLog);
						// Register it's corresponding action
						$action = new \Entity\HistoryAction();
						$action->setSummary("Generación de reporte de solcitudes cerradas");
						$action->setDetail("Solicitudes cerradas entre " . $from->format('d/m/Y') . " y " . $to->format('d/m/Y'));
						$action->setBelongingHistory($newLog);
						$newLog->addAction($action);
						$em->persist($action);
						$em->persist($newLog);
						$em->merge($request);
					}
				}
				$em->flush();
				$em->clear();
				if (!$count) {
					$interval = $from->diff($to);
					$days = $interval->format("%a");
					if ($days > 0) {
						$result['error'] = "No se han encontrado solicitudes cerradas en el rango de fechas especificado";
					} else {
						$result['error'] = "No se han encontrado solicitudes cerradas en la fecha especificada";
					}
				} else {
					// Fill up report information
					$now = (new DateTime('now', new DateTimeZone('America/Barbados')))->format('d/m/Y - h:i:sa');
					$user = strtoupper($_SESSION['name'] . " " . $_SESSION['lastName']);
					$result['report']['header'] = array(
						array("SGDP - IPAPEDI"),
						array("REPORTE GENERADO POR: " . $user . ". FECHA Y HORA: " . $now)
					);
					$interval = "DEL " . $from->format('d/m/Y') . " HASTA EL " . $to->format('d/m/Y');
					$filenameInterval = "DEL " . $from->format('d-m-Y') . " HASTA EL " . $to->format('d-m-Y');
					$dataTitle = "SOLICITUDES CERRADAS " . $interval;
					$filename =  "SOLICITUDES CERRADAS " . $filenameInterval;
					$result['report']['filename'] = $filename;
					$result['report']['dataTitle'] = $dataTitle;
					$result['report']['dataHeader'] = array(
						'Nro.', 'Identificador', 'Tipo', 'Solicitante', 'Fecha de creación', 'Estatus', 'Cerrada por',
						 'Nro. de Reunión', 'Monto solicitado (Bs)', 'Monto aprobado (Bs)'
					 );
					$result['report']['total'] = array(
						array("Monto solicitado total", ""),
						array("Monto aprobado total", "")
					);
					$result['message'] = "success";
				}
			} catch (Exception $e) {
				\ChromePhp::log($e);
				$result['message'] = "error";
			}
			echo json_encode($result);
		}
	}

	public function getClosedReportByCurrentWeek() {
		if ($_SESSION['type'] != 2) {
			$this->load->view('errors/index.html');
		} else {
			try {
				// start first day of week, end last day of week
				if (date('D') == 'Mon') {
					$start = date('d/m/Y');
				} else {
					$start = date('d/m/Y', strtotime('last monday'));
				}
				if (date('D') == 'Sun') {
					$end = date('d/m/Y');
				} else {
					$end = date('d/m/Y', strtotime('next sunday'));
				}
				// from first second of the day
				$from = date_create_from_format(
					'd/m/Y H:i:s',
					$start . ' ' . '00:00:00',
					new DateTimeZone('America/Barbados')
				);
				// to last second of the day
				$to = date_create_from_format(
					'd/m/Y H:i:s',
					$end . ' ' . '23:59:59',
					new DateTimeZone('America/Barbados')
				);
				$em = $this->doctrine->em;
				$qb = $em->createQueryBuilder();
				$qb->select(array('h'))
					->from('\Entity\History', 'h')
					->where($qb->expr()->andX(
						$qb->expr()->eq('h.title', '?1'),
						$qb->expr()->between('h.date', '?2', '?3')
					));
				$qb->setParameter(1, 4); // 4 = close
				$qb->setParameter(2, $from);
				$qb->setParameter(3, $to);
				$history = $qb->getQuery()->getResult();
				$count = 0;
				$evaluated = [];
				foreach ($history as $h) {
					$request = $h->getOrigin();
					$userOwner = $request->getUserOwner();
					if (!isset($evaluated[$request->getId()])) {
						// Gather up report information
						$evaluated[$request->getId()] = true;
						$count++;
						$result['report']['data'][$count] = array(
							$count,
							$request->getId(),
							$this->mapLoanType($request->getLoanType()),
							$userOwner->getId() . ' - ' . $userOwner->getFirstName() . ' ' . $userOwner->getLastName(),
							$request->getCreationDate()->format('d/m/Y'),
							$request->getStatus(),
							$h->getUserResponsable(),
							$request->getReunion(),
							$request->getRequestedAmount(),
							$request->getApprovedAmount(),
						);
						// Add report generation action to history
						$newLog = new \Entity\History();
						$newLog->setDate(new DateTime('now', new DateTimeZone('America/Barbados')));
						$newLog->setUserResponsable($_SESSION['name'] . ' ' . $_SESSION['lastName']);
						// 6 = report generation
						$newLog->setTitle(6);
						$newLog->setOrigin($request);
						$request->addHistory($newLog);
						// Register it's corresponding action
						$action = new \Entity\HistoryAction();
						$action->setSummary("Generación de reporte de solcitudes cerradas");
						$action->setDetail("Solicitudes cerradas entre " . $from->format('d/m/Y') . " y " . $to->format('d/m/Y'));
						$action->setBelongingHistory($newLog);
						$newLog->addAction($action);
						$em->persist($action);
						$em->persist($newLog);
						$em->merge($request);
					}
				}
				$em->flush();
				if (!$count) {
					$result['error'] = "No se han detectado cierres de solicitudes esta semana.";
				} else {
					// Fill up report information
					$now = (new DateTime('now', new DateTimeZone('America/Barbados')))->format('d/m/Y - h:i:sa');
					$user = $_SESSION['name'] . " " . $_SESSION['lastName'];
					$result['report']['header'] = array(
						array("SGDP - IPAPEDI"),
						array("REPORTE GENERADO POR: " . $user . ". FECHA Y HORA: " . $now)
					);
					$interval = "DEL " . $from->format('d/m/Y') . " HASTA EL " . $to->format('d/m/Y');
					$filenameInterval = "DEL " . $from->format('d-m-Y') . " HASTA EL " . $to->format('d-m-Y');
					$dataTitle = "SOLICITUDES CERRADAS " . $interval;
					$filename =  "SOLICITUDES CERRADAS " . $filenameInterval;
					$result['report']['filename'] = $filename;
					$result['report']['dataTitle'] = $dataTitle;
					$result['report']['dataHeader'] = array(
						'Nro.', 'Identificador', 'Tipo', 'Solicitante', 'Fecha de creación', 'Estatus', 'Cerrada por',
						 'Nro. de Reunión', 'Monto solicitado (Bs)', 'Monto aprobado (Bs)'
					 );
					$result['report']['total'] = array(
						array("Monto solicitado total", ""),
						array("Monto aprobado total", "")
					);
					$result['message'] = "success";
				}
			} catch (Exception $e) {
				\ChromePhp::log($e);
				$result['message'] = "error";
			}
            \ChromePhp::log($result);
			echo json_encode($result);
		}
	}

	private function mapLoanType($code) {
		return $code == 40 ? "PRÉSTAMO PERSONAL" : ($code == 31 ? "VALE DE CAJA" : $code);
	}

    /**
     * Randomly generates a new hexadecimal color.
     *
     * @param $existing - array with existing colors.
     * @return string - randomly (hex) color that is not present in $existing array.
     */
    private function rand_color($existing) {
        do {
            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        } while (in_array($color, $existing));
        return $color;
    }

    /**
     * Generates a hexadecimal color code for a specified status.
     *
     * @param $status - current status to generate a color for.
     * @param $colors - already used colors (that can't be repeated).
     * @return string - (hex) color for the specified status.
     */
    private function generatePieBgColor($status, $colors) {
        switch ($status) {
            case 'Recibida': return '#FFD740'; // A200 amber
            case 'Aprobada': return '#00C853'; // A700 green
            case 'Rechazada': return '#FF5252'; // A200 red
            default: return $this->rand_color($colors);
        }
    }

    private function generatePieHoverColor($colour) {
        $brightness = -0.9; // 10% darker
        return($this->colourBrightness($colour,$brightness));
    }

    private function colourBrightness($hex, $percent) {
        // Work out if hash given
        $hash = '';
        if (stristr($hex,'#')) {
            $hex = str_replace('#','',$hex);
            $hash = '#';
        }
        /// HEX TO RGB
        $rgb = array(hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2)));
        //// CALCULATE
        for ($i=0; $i<3; $i++) {
            // See if brighter or darker
            if ($percent > 0) {
                // Lighter
                $rgb[$i] = round($rgb[$i] * $percent) + round(255 * (1-$percent));
            } else {
                // Darker
                $positivePercent = $percent - ($percent*2);
                $rgb[$i] = round($rgb[$i] * $positivePercent) + round(0 * (1-$positivePercent));
            }
            // In case rounding up causes us to go to 256
            if ($rgb[$i] > 255) {
                $rgb[$i] = 255;
            }
        }
        //// RBG to Hex
        $hex = '';
        for($i=0; $i < 3; $i++) {
            // Convert the decimal digit to hex
            $hexDigit = dechex($rgb[$i]);
            // Add a leading zero if necessary
            if(strlen($hexDigit) == 1) {
                $hexDigit = "0" . $hexDigit;
            }
            // Append to the hex string
            $hex .= $hexDigit;
        }
        return $hash.$hex;
    }

    private function getAllStatuses () {
        $theStatuses = array('Recibida', 'Aprobada', 'Rechazada');
        try {
            $em = $this->doctrine->em;
            $statuses = $em->getRepository('\Entity\Config')->findBy(array('key' => 'STATUS'));
            foreach ($statuses as $status) {
                array_push($theStatuses, $status->getValue());
            }
        } catch (Exception $e) {
            \ChromePhp::log($e);
        }
        return $theStatuses;
    }

    private function getAdditionalStatuses () {
        $theStatuses = [];
        try {
            $em = $this->doctrine->em;
            $statuses = $em->getRepository('\Entity\Config')->findBy(array('key' => 'STATUS'));
            foreach ($statuses as $status) {
                array_push($theStatuses, $status->getValue());
            }
        } catch (Exception $e) {
            \ChromePhp::log($e);
        }
        return $theStatuses;
    }
}
