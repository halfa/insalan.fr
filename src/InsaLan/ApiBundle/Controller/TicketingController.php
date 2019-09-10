<?php

namespace InsaLan\ApiBundle\Controller;

use InsaLan\ApiBundle\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use InsaLan\TicketingBundle\Entity\ETicket;

class TicketingController extends Controller
{

    const ERR_TICKET_NOT_FOUND = array("no" => 1, "msg" => "Ticket not found");
    const ERR_PARTICIPANT_NOT_FOUND = array("no" => 2, "msg" => "Participant not found");
    const ERR_TICKET_ALREADY_SCANNED = array("no" => 3, "msg" => "Already scanned");
    const ERR_TICKET_CANCELLED = array("no" => 4, "msg" => "Ticket cancelled");

    /**
     * @Route("/ticket/get")
     * @Method({"POST"})
     */
    public function getETicketAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        // Deny access if the user is not an admin
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Access denied!');

        // Get JSON data
        $parametersAsArray = [];
            if ($content = $request->getContent()) {
                $parametersAsArray = json_decode($content, true);
            }

        // Find e-ticket
        $token = $parametersAsArray["token"];
        $eTicket = $em->getRepository('InsaLanTicketingBundle:ETicket')->findOneByToken($token);
        if ($eTicket === null) {
          return new JsonResponse(array("err" => self::ERR_TICKET_NOT_FOUND));
        }

        // Find participant
        $participant = $em->getRepository('InsaLanTournamentBundle:Player')->findOneByETicket($eTicket);
        if ($participant === null) {
          $participant = $em->getRepository('InsaLanTournamentBundle:Manager')->findOneByETicket($eTicket);
        }
        if ($participant === null) {
          return new JsonResponse(array("err" => self::ERR_PARTICIPANT_NOT_FOUND));
        }

        $res = array(
          "name" => $participant->getUser()->getFirstname()." ".$participant->getUser()->getLastname(),
          "phone" => $participant->getUser()->getPhoneNumber(),
          "gameName" => $participant->getGameName(),
          "tournament" => $participant->getTournament()->getName(),
          "ticketScanned" => $eTicket->getIsScanned(),
          "status" => ETicket::getStatuses()[$eTicket->getStatus()]
          );
        return new JsonResponse($res);
    }

    /**
     * @Route("/ticket/validate")
     * @Method({"POST"})
     */
    public function validateTicketAction(Request $request)
    {
      $em = $this->getDoctrine()->getManager();
      // Deny access if the user is not an admin
      $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'Access denied!');

      // Get JSON data
      $parametersAsArray = [];
          if ($content = $request->getContent()) {
              $parametersAsArray = json_decode($content, true);
          }

      // Find e-ticket
      $token = $parametersAsArray["token"];
      $eTicket = $em->getRepository('InsaLanTicketingBundle:ETicket')->findOneByToken($token);
      if ($eTicket === null) {
        return new JsonResponse(array("err" => self::ERR_TICKET_NOT_FOUND));
      }
      if ($eTicket->getStatus() == ETicket::STATUS_SCANNED) {
        return new JsonResponse(array("err" => self::ERR_TICKET_ALREADY_SCANNED));
      } else if ($eTicket->getStatus() == ETicket::STATUS_VALID){
        $eTicket->setStatus(ETicket::STATUS_SCANNED);
        $em->persist($eTicket);
        $em->flush();
        return new JsonResponse(array("err" => null));
      } else {
        return new JsonResponse(array("err" => self::ERR_TICKET_CANCELLED));
      }
    }
}
