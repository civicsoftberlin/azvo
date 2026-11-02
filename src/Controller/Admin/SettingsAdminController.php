<?php

namespace App\Controller\Admin;

use App\Form\SettingsType;
use App\Service\SettingsService;
use EasyCorp\Bundle\EasyAdminBundle\Provider\AdminContextProvider; // <--- WICHTIG
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SettingsAdminController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        SettingsService $settings,
        AdminContextProvider $adminContextProvider // <--- INJEKTION
    ): Response {

        $formData = [
            'reload_interval' => $settings->get('auto_import_enable', false),
            'show_schedule_column' => $settings->get('auto_import_url', ""),
        ];

        // 2. Formular erstellen
        $form = $this->createForm(SettingsType::class, $formData);
        $form->handleRequest($request);

        // 3. Speichern, wenn abgesendet
        if ($form->isSubmitted() && $form->isValid()) {
            $settings->save($form->getData());
            $this->addFlash('success', 'Die Einstellungen wurden erfolgreich gespeichert.');

            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->render('admin/settings.html.twig', [
            'form' => $form->createView(),
            'ea' => $adminContextProvider->getContext(),
        ]);
    }
}
