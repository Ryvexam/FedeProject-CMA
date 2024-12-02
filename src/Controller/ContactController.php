<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/contacts')]
class ContactController extends AbstractController
{
    #[Route('/', name: 'app_contact_index', methods: ['GET'])]
    public function index(ContactRepository $contactRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $contacts = $search
            ? $contactRepository->search($search)
            : $contactRepository->findAll();

        return $this->render('contact/index.html.twig', [
            'contacts' => $contacts,
            'search' => $search
        ]);
    }

    #[Route('/new', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle custom fields
            $customFields = $request->request->all('custom_fields');
            if (is_array($customFields)) {
                $processedFields = [];
                foreach ($customFields as $field) {
                    if (isset($field['name']) && isset($field['value']) && !empty($field['name'])) {
                        $processedFields[$field['name']] = $field['value'];
                    }
                }
                $contact->setCustomFields($processedFields);
            }

            // Handle photo upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $contact->setPhotoFilename($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading photo');
                }
            }

            $entityManager->persist($contact);
            $entityManager->flush();

            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_show', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        return $this->render('contact/show.html.twig', [
            'contact' => $contact,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle custom fields
            $customFields = $request->request->all('custom_fields');
            if (is_array($customFields)) {
                $processedFields = [];
                foreach ($customFields as $field) {
                    if (isset($field['name']) && isset($field['value']) && !empty($field['name'])) {
                        $processedFields[$field['name']] = $field['value'];
                    }
                }
                $contact->setCustomFields($processedFields);
            }

            // Handle photo upload
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $contact->setPhotoFilename($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading photo');
                }
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_contact_index');
        }

        return $this->render('contact/edit.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            $entityManager->remove($contact);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_contact_index');
    }
}