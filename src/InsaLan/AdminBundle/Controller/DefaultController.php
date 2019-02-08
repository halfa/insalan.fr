<?php

namespace InsaLan\AdminBundle\Controller;

use InsaLan\TournamentBundle\Entity\Group;
use InsaLan\TournamentBundle\Entity\GroupStage;
use InsaLan\TournamentBundle\Entity\Match;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller {
    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction() {
        return array();
    }

    /**
     * @Route("/tournament")
     * @Template()
     */
    public function tournamentAction() {
        return array();
    }

    /**
     * @Route("/tournament/groupstage", name="GroupStageAction")
     * @Template()
     * Get all group stages (phases de poule)
     */
    public function tournamentGroupStageAction() {
        $em = $this->getDoctrine()->getManager(); // entity manager

        $groupStage = new GroupStage();
        $form = $this->createFormBuilder($groupStage)
            ->add('name')
            ->add('tournament', 'entity', array('class' => 'InsaLanTournamentBundle:Tournament'))
            ->add('save', 'submit', array('label' => 'Créer'))
            ->getForm();

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($groupStage); // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $em->flush(); // actually executes the queries (i.e. the INSERT query)
        }

        $groupStages = $em->getRepository('InsaLanTournamentBundle:GroupStage')->findAll();

        return array(
            'groupStages' => $groupStages,
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/tournament/groupstage/remove/{id}", name="GroupStageRemoveAction")
     */
    public function tournamentGroupStageRemoveAction($id) {
        $em = $this->getDoctrine()->getManager(); // entity manager

        $groupStage = $em->getRepository('InsaLanTournamentBundle:GroupStage')->find($id);

        if($groupStage != null) {
            try {
                $em->remove($groupStage);
                $em->flush();
            } catch(\Exception $e) {
                // TODO message d'erreur si on n'arrive pas à enlever le groupstage ?
                return $this->redirectToRoute('GroupStageAction');
            }
        }

        return $this->redirectToRoute('GroupStageAction');
    }

    /**
     * @Route("/tournament/groupstage/modify/{id}", name="GroupStageModifyAction")
     * @Template()
     */
    public function tournamentGroupStageModifyAction($id) {
        $em = $this->getDoctrine()->getManager(); // entity manager
        $groupStage = $em->getRepository('InsaLanTournamentBundle:GroupStage')->find($id);

        $form = $this->createFormBuilder($groupStage)
            ->add('name')
            ->add('tournament', 'entity', array('class' => 'InsaLanTournamentBundle:Tournament'))
            ->add('save', 'submit', array('label' => 'Modifier'))
            ->getForm();

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($groupStage); // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $em->flush(); // actually executes the queries (i.e. the INSERT query)
            return $this->redirectToRoute('GroupStageAction');
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/tournament/group", name="GroupAction")
     * @Template()
     * Get all groups (poules)
     */
    public function tournamentGroupAction() {
        $em = $this->getDoctrine()->getManager();

        $group = new Group();
        $form = $this->createFormBuilder($group)
            ->add('name')
            ->add('stage', 'entity', array('class' => 'InsaLanTournamentBundle:GroupStage'))
            ->add('participants', 'entity', array(
                'class' => 'InsaLanTournamentBundle:Participant',
                'multiple' => true))
            ->add('save', 'submit', array('label' => 'Créer'))
            ->getForm();

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($group); // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $em->flush(); // actually executes the queries (i.e. the INSERT query)
        }

        $groups = $em->getRepository('InsaLanTournamentBundle:Group')->findAll();

        return array(
            'groups' => $groups,
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/tournament/group/remove/{id}", name="GroupRemoveAction")
     */
    public function tournamentStageRemoveAction($id) {
        $em = $this->getDoctrine()->getManager(); // entity manager

        $group = $em->getRepository('InsaLanTournamentBundle:Group')->find($id);

        if($group != null) {
            try {
                $em->remove($group);
                $em->flush();
            } catch(\Exception $e) {
                return $this->redirectToRoute('GroupAction');
            }
        }

        return $this->redirectToRoute('GroupAction');
    }

    /**
     * @Route("/tournament/group/modify/{id}", name="GroupModifyAction")
     * @Template()
     */
    public function tournamentGroupModifyAction($id) {
        $em = $this->getDoctrine()->getManager(); // entity manager
        $group = $em->getRepository('InsaLanTournamentBundle:Group')->find($id);

        $form = $this->createFormBuilder($group)
            ->add('name')
            ->add('stage', 'entity', array('class' => 'InsaLanTournamentBundle:GroupStage'))
            ->add('participants', 'entity', array(
                'class' => 'InsaLanTournamentBundle:Participant',
                'multiple' => true))
            ->add('save', 'submit', array('label' => 'Modifier'))
            ->getForm();

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($group); // tell Doctrine you want to (eventually) save the Product (no queries yet)
            $em->flush(); // actually executes the queries (i.e. the INSERT query)
            return $this->redirectToRoute('GroupAction');
        }

        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/tournament/match", name="MatchAction")
     * Get all matches
     */
    public function tournamentMatchAction() {
        return $this->redirect('/tournament/admin');
    }

    /**
     * @Route("/pizza")
     * @Route("/pizza/{id}")
     * @Template()
     */
    public function pizzaAction() {
        // public function pizzaAction($id = null) {
        return array();
        // return $this->redirect($this->generateUrl("insalan_pizza_admin_index", array("id" => $id)));
    }

    /**
     * @Route("/web")
     * @Template()
     */
    public function webAction() {
        return array();
    }
}
