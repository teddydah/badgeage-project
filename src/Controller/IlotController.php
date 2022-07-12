<?php

namespace App\Controller;

use App\Entity\Badgeage;
use App\Entity\Client;
use App\Entity\Ilot;
use App\Entity\OrdreFab;
use App\Form\OrdreFabType;
use App\Repository\BadgeageRepository;
use App\Repository\OrdreFabRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{nomURL}", name="ilot_")
 */
class IlotController extends AbstractController
{
    /**
     * @Route(name="of", methods={"GET", "POST"})
     */
    public function getOF(
        Ilot                   $ilot = null,
        OrdreFabRepository     $ordreFabRepository,
        BadgeageRepository     $badgeageRepository,
        Request                $request,
        EntityManagerInterface $em): Response
    {
        // ParamConverter => si $ilot est null, alors le contrôleur est exécuté
        if (null === $ilot) {
            throw $this->createNotFoundException('Ilot non trouvé.');
        }

        date_default_timezone_set('Europe/Paris');

        $badge = new Badgeage();
        $ordreFab = new OrdreFab();

        $form = $this->createForm(OrdreFabType::class, $ordreFab);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération du numéro d'OF depuis le formulaire
            $numOF = $form->get('numero')->getData();

            // Récupération de l'OF avec $numOF depuis la BDD
            $ordre = $ordreFabRepository->findOneBy(["numero" => $numOF]);

            // TODO : Ajouter : si OF existant null alors interroger API + màj MYSQL avec nouvel OF (peu probable)
            $badgeage = $badgeageRepository->findOneBy(["ilot" => $ilot, "ordreFab"=> $ordre]);

            if ($badgeage !== null) {
                // TODO : Màj la date
                // TODO : redirectToRoute("mettre date à jour")
                $this->addFlash('danger', $ilot->getNomIRL() . ' : l\'OF ' . $numOF . ' a déjà été badgé.');
            } else {
                $this->addOF($badge, $ordre, $ilot);
                $em->persist($badge);
                $em->flush();

                $this->addFlash('success', $ilot->getNomIRL() . ' : commande ' . $badge->getOrdreFab()->getNumero() . ' validée.');
            }
        }

        return $this->render('ilot/read.html.twig', ['ilot' => $ilot,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/options", name="options", methods={"GET"})
     */
    public function options(Ilot $ilot = null)
    {
        if (null === $ilot) {
            throw $this->createNotFoundException('Ilot non trouvé.');
        }

        return $this->render('ilot/options.html.twig', [
            'ilot' => $ilot
        ]);
    }

    private function addOF(Badgeage $badge, OrdreFab $ordreFab, Ilot $ilot)
    {
        $badge->setOrdreFab($ordreFab);
        $badge->setIlot($ilot);
        $badge->setDateBadgeage(new \DateTime());

        return $badge;
    }


}