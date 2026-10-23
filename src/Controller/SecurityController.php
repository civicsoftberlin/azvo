<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@EasyAdmin/page/login.html.twig', [
            // parameters usually defined in Symfony login forms
            'error' => $error,
            'last_username' => $lastUsername,
            'translation_domain' => 'admin',
            'favicon_path' => '/logo.svg',
            'page_title' => '<div class="d-flex align-items-center gap-2"><img src="/logo.svg" height="18px" alt="Logo"><span class="fw-bold tk-text-primary">LZM</span></div><div class="small text-muted">LehrZeitManagement</div>',
            'csrf_token_intention' => 'authenticate',
            'action' => $this->generateUrl('login'),
            'target_path' => $this->generateUrl('admin_dashboard'),
            'username_label' => 'Benutzername',
            'password_label' => 'Passwort',
            'sign_in_label' => 'Anmelden',
            'forgot_password_enabled' => false,
            'forgot_password_label' => 'Passwort vergessen?',
            'remember_me_enabled' => true,
            'remember_me_checked' => false,
            'remember_me_label' => 'Eingeloggt bleiben?',
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): void
    {
        // Diese Methode kann leer sein oder eine Exception werfen.
        // Symfony fängt dies ab und führt den Logout über den Firewall aus.
        throw new \LogicException('Diese Methode kann leer sein – der Logout wird vom Symfony Security-System abgefangen.');
    }
}
