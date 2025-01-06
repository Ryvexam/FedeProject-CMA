<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Contact;
use App\Form\GroupAddContactsType;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/group')]
class GroupController extends AbstractController
{
    #[Route('/', name: 'app_group_index', methods: ['GET'])]
    public function index(GroupRepository $groupRepository): Response
    {
        return $this->render('group/index.html.twig', [
            'groups' => $groupRepository->findAllSorted(),
        ]);
    }

    #[Route('/new', name: 'app_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($group);
            $entityManager->flush();

            $this->addFlash('success', 'Group created successfully.');
            return $this->redirectToRoute('app_group_index');
        }

        return $this->render('group/new.html.twig', [
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_group_show', methods: ['GET'])]
    public function show(Group $group): Response
    {
        return $this->render('group/show.html.twig', [
            'group' => $group,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_group_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Group $group, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Group updated successfully.');
            return $this->redirectToRoute('app_group_show', ['id' => $group->getId()]);
        }

        return $this->render('group/edit.html.twig', [
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_group_delete', methods: ['POST'])]
    public function delete(Request $request, Group $group, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$group->getId(), $request->request->get('_token'))) {
            $entityManager->remove($group);
            $entityManager->flush();
            $this->addFlash('success', 'Group deleted successfully.');
        }

        return $this->redirectToRoute('app_group_index');
    }

    #[Route('/{id}/add-contacts', name: 'app_group_add_contacts', methods: ['GET', 'POST'])]
    public function addContacts(Request $request, Group $group, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GroupAddContactsType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedContacts = $form->get('contacts')->getData();

            foreach ($selectedContacts as $contact) {
                if (!$group->getContacts()->contains($contact)) {
                    $group->addContact($contact); // Add contact to group
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Contacts added to group successfully.');
            return $this->redirectToRoute('app_group_show', ['id' => $group->getId()]);
        }

        return $this->render('group/add_contacts.html.twig', [
            'group' => $group,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/group/{groupId}/remove-contact/{contactId}', name: 'app_group_remove_contact', methods: ['POST'])]
    public function removeContact(
        Request $request,
        int $groupId,
        int $contactId,
        EntityManagerInterface $entityManager,
        GroupRepository $groupRepository
    ): Response
    {
        $group = $groupRepository->find($groupId);
        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }

        $contact = $entityManager->getRepository(Contact::class)->find($contactId);
        if (!$contact) {
            throw $this->createNotFoundException('Contact not found');
        }

        if ($this->isCsrfTokenValid('remove-contact-'.$contactId, $request->request->get('_token'))) {
            $group->removeContact($contact); // Remove contact from group
            $entityManager->flush();
            $this->addFlash('success', 'Contact removed from group successfully.');
        }

        return $this->redirectToRoute('app_group_show', ['id' => $groupId]);
    }
}