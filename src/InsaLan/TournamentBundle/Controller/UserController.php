<?php

namespace InsaLan\TournamentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use InsaLan\TournamentBundle\Form\SetLolPlayerType;
use InsaLan\TournamentBundle\Form\TeamType;

use InsaLan\TournamentBundle\Entity\Player;
use InsaLan\TournamentBundle\Entity\Team;

class UserController extends Controller
{


    /**
     * @Route("/user")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();

        $tournaments = $em->getRepository('InsaLanTournamentBundle:Tournament')->findOpened();
        $player = $em->getRepository('InsaLanTournamentBundle:Player')->findOneByUser($usr->getId());

        return array('tournaments' => $tournaments, 'player' => $player);
    }

    /**
     * @Route("/user/player/set/{game}")
     * @Template()
     */
    public function setPlayerAction(Request $request, $game) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $player = $em->getRepository('InsaLanTournamentBundle:Player')->findOneByUser($usr->getId());
        if ($player === null) {
            $player = new Player();
            $player->setUser($usr);
        } 

        if ($game === 'lol') {
            return $this->lolSet($em,$usr,$player,$request);
        }
        return array('selectedGame' => $game);

    }

    /**
     * @Route("/user/player/validate/{game}")
     * @Template()
     */
    public function validatePlayerAction($game) {
        $em = $this->getDoctrine()->getManager();
        $usr = $this->get('security.context')->getToken()->getUser();
        $player = $em->getRepository('InsaLanTournamentBundle:Player')->findOneByUser($usr->getId());

        if ($player === null) {
            return $this->redirect($this->generateUrl('insalan_tournament_user_setplayer', array('game' => $game)));
        } else if ($game === 'lol') {
            return $this->lolValidation($em, $usr, $player);
        } else {
            return $this->redirect($this->generateUrl('insalan_tournament_user_index'));
        } 
    }

    /**
     * @Route("/user/join/{id}/team")
     * @Template()
     */
    public function joinTeamAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $tournament = $em
            ->getRepository('InsaLanTournamentBundle:Tournament')
            ->findOneById($id);
        $usr = $this
            ->get('security.context')
            ->getToken()
            ->getUser();
        $player = $em
            ->getRepository('InsaLanTournamentBundle:Player')
            ->findOneByUser($usr->getId());

        // Check if there is a player associated to this user
        if ($player === null || !$player->isNamed($tournament->getType())) {
            return $this->redirect($this->generateUrl('insalan_tournament_user_setplayer', array('game' => $tournament->getType())));
        } 
        // Check if this player is validated for the game
        else if (!$player->isValidated($tournament->getType())) {
            return $this->redirect($this->generateUrl('insalan_tournament_user_validateplayer', array('game' => $tournament->getType())));
        }

        return array('tournament' => $tournament, 'user' => $usr, 'player' => $player);
    }

    /**
     * @Route("/user/join/{id}/player")
     * @Template()
     */
    public function joinPlayerAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $tournament = $em
            ->getRepository('InsaLanTournamentBundle:Tournament')
            ->findOneById($id);
        $usr = $this
            ->get('security.context')
            ->getToken()
            ->getUser();
        $player = $em
            ->getRepository('InsaLanTournamentBundle:Player')
            ->findOneByUser($usr->getId());

        return array('tournament' => $tournament, 'user' => $usr, 'player' => $player);
    }

    /**
     * @Route("/user/join/{id}/team/create")
     * @Template()
     */
    public function createTeamAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $tournament = $em
            ->getRepository('InsaLanTournamentBundle:Tournament')
            ->findOneById($id);
        $usr = $this
            ->get('security.context')
            ->getToken()
            ->getUser();
        $player = $em
            ->getRepository('InsaLanTournamentBundle:Player')
            ->findOneByUser($usr->getId());

        $team = new Team();
        
        $form = $this->createForm(new TeamType(), $team);
        $form->handleRequest($request);

        if ($form->isValid() && $team->getPlainPassword() !== null && $team->getPlainPassword() !== "") {
            $factory = $this->get('security.encoder_factory');
            $encoder = $factory->getEncoder($usr);
            $team->setPassword($encoder->encodePassword($team->getPlainPassword(), sha1('pleaseHashPasswords'.$team->getName())));
            $team->setTournament($tournament);
            $player->joinTeam($team);
            $em->persist($team);
            $em->persist($player);
            $em->flush();
            return $this->redirect($this->generateUrl('insalan_tournament_user_index'));
        }

        return array('tournament' => $tournament, 'user' => $usr, 'player' => $player, 'form' => $form->createView());
    }

    /**
     * @Route("/user/join/{id}/team/existing")
     * @Template()
     */
    public function existingTeamAction($id) {
        return array();
    }

    protected function lolSet($em, $usr, $player, $request) {
        $form = $this->createForm(new SetLolPlayerType(), $player);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $player->setLolIdValidated(false);
            $em->persist($player);
            $em->flush();

            return $this->redirect(
                $this->generateUrl('insalan_tournament_user_validateplayer', array('game'=>'lol'))
            );
        }

        return array('form' => $form->createView(), 'selectedGame' => 'lol');
    }

    protected function lolValidation($em, $usr, $player) {
        if ($player->getLolIdValidated()) {
            return $this->redirect($this->generateUrl('insalan_tournament_user_index'));
        } else {
            $details = null;
            try {
                $this->fetchInfo($usr, $player);
                $em->persist($player);
                $em->flush();
            } catch(\Exception $e) {
                $className = get_class($e);

                if ('GuzzleHttp\\Exception\\ClientException' === $className && 404 == $e->getResponse()->getStatusCode()) {
                    $details = 'Invocateur introuvable sur EUW';
                }
                else if (0 === strpos($className, 'GuzzleHttp')) {
                    $details = 'Erreur de l\'API. Veuillez réessayer.';
                } else {
                    $details = 'Une erreur inconnue est survenue';
                }
            }

            return array('player' => $player, 'error' => $details, 'selectedGame' => 'lol');
        }

    }

    protected function fetchInfo($user, $player) {
        $apiLol = $this->container->get('insalan.lol');
        $apiSummoner = $apiLol->getApi()->summoner();
        $rSummoner = $apiSummoner->info($player->getLolName());
        $player->setLolId($rSummoner->id);
        $player->setLolName($rSummoner->name);
        $player->setLolPicture($rSummoner->profileIconId);

        $masteryPages = $apiSummoner->masteryPages($player->getLolId());
        foreach ($masteryPages as $page) {
            if ($page->get('name') == 'insalan'.$user->getId()) {
                $player->setLolIdValidated(true);
                break;
            }
        }

    }
}
