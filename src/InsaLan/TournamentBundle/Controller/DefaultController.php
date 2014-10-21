<?php

namespace InsaLan\TournamentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use InsaLan\TournamentBundle\Entity;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $tournaments = $em->getRepository('InsaLanTournamentBundle:Tournament')->findAll();
        return array('tournaments' => $tournaments);
    }

    /**
     * @Route("/{id}", requirements={"id" = "\d+"})
     * @Template()
     */
    public function tournamentAction(Entity\Tournament $tournament)
    {
        $em = $this->getDoctrine()->getManager();
        $stages = $em->getRepository('InsaLanTournamentBundle:GroupStage')->findByTournament($tournament);

        foreach ($stages as $s) {
            foreach ($s->getGroups() as $g) {
                $g->countWins();
            }
        }

        return array('t' => $tournament, 'stages' => $stages);
    }

    /**
     * @Route("/team")
     * @Template()
     */
    public function teamListAction()
    {
        $em = $this->getDoctrine()->getManager();
        $teams =  $em->getRepository('InsaLanTournamentBundle:Team')->getAllTeams();

        return array('teams' => $teams);
    }

    /**
     * @Route("/team/captain")
     * @Method({"POST"})
     */
    public function setCaptainAction(Request $request) 
    {

        $team_id = $request->request->get('team_id');
        $player_id = $request->request->get('player_id');


        $em = $this->getDoctrine()->getManager();
        $team = $em->getRepository('InsaLanTournamentBundle:Team')->find($team_id);

        if (!$team) {
            throw $this->createNotFoundException(
                'No team found for this id : '.$team_id
            );
        }

        foreach($team->getPlayers() as $player) {
            if ($player->getId() == $player_id) {
                $team->setCaptain($player);
                $em->flush(); 
                return $this->redirect($this->generateUrl('insalan_user_default_index'));
            }
        }

        throw $this->createNotFoundException(
            'No user found for this id : '.$player_id
        );

    }

    /**
     * @Route("/team/{id}", requirements={"id" = "\d+"})
     * @Template()
     */
    public function teamDetailsAction(Entity\Team $team)
    {   
        $pvpService = $this->get('insalan.tournament.pvp_net');

        foreach ($team->getGroups() as $g)
        {
            $g->countWins();
            // TODO : LoL Only !
            foreach ($g->getMatches() as $m)
            {   
                $name = "INSALAN Match " . $m->getId();
                $m->pvpNetUrl = $pvpService->generateUrl(array("name" => $name));
            }
        }

        return array("team" => $team);

    }

}
