<?php
declare(strict_types=1);

namespace App\controllers;

use App\core\Controller;
use App\models\User;

final class AuthController extends Controller
{
    /** Formulaire d'inscription */
    public function signupForm(): void
    {
        $user = new User();
        $coaches = $user->coaches(); // pour le select si rôle = adhérent
        $this->render('auth/signup', [
            'title'   => 'Inscription',
            'coaches' => $coaches
        ]);
    }

    /** Traitement inscription (POST) */
    public function signupPost(): void
    {
        if (!csrf_verify($_POST['_token'] ?? null)) {
            http_response_code(400);
            flash('error','Jeton CSRF invalide.');
            $this->redirect(BASE_URL.'?action=inscription');
        }

        $first   = trim($_POST['first_name'] ?? '');
        $last    = trim($_POST['last_name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = preg_replace('/\s+/', ' ', trim($_POST['phone'] ?? ''));
        $address = trim($_POST['address'] ?? '');
        $pass    = (string)($_POST['password'] ?? '');
        $pass2   = (string)($_POST['password_confirm'] ?? '');
        $role    = ($_POST['role'] ?? 'adherent') === 'coach' ? 'coach' : 'adherent';
        $gender  = $_POST['gender'] ?? null;
        $age     = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
        $coach_id = isset($_POST['coach_id']) && $_POST['coach_id'] !== '' ? (int)$_POST['coach_id'] : null;

        // validations
        if ($first === '' || $last === '' || $email === '' || $phone === '' || $address === '' || $pass === '' || $pass2 === '') {
            flash('error','Tous les champs requis doivent être remplis.');
            $this->redirect(BASE_URL.'?action=inscription');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error','Email invalide.');
            $this->redirect(BASE_URL.'?action=inscription');
        }
        if (!preg_match('/^\+?[0-9 \-\.]{9,20}$/', $phone)) {
            flash('error','Téléphone invalide (ex: 06 12 34 56 78).');
            $this->redirect(BASE_URL.'?action=inscription');
        }
        if (strlen($address) < 5 || strlen($address) > 255) {
            flash('error','Adresse invalide (5–255 caractères).');
            $this->redirect(BASE_URL.'?action=inscription');
        }
        if ($pass !== $pass2) {
            flash('error','Les mots de passe ne correspondent pas.');
            $this->redirect(BASE_URL.'?action=inscription');
        }
        if (strlen($pass) < 6) {
            flash('error','Mot de passe trop court (min. 6).');
            $this->redirect(BASE_URL.'?action=inscription');
        }
        if ($role === 'adherent' && !$coach_id) {
            flash('error','Choix du coach obligatoire pour un adhérent.');
            $this->redirect(BASE_URL.'?action=inscription');
        }

        $u = new User();
        if ($u->findByEmail($email)) {
            flash('error','Un compte existe déjà avec cet email.');
            $this->redirect(BASE_URL.'?action=inscription');
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $id = $u->create([
            'first_name'=>$first,
            'last_name' =>$last,
            'email'     =>$email,
            'phone'     =>$phone,
            'address'   =>$address,
            'password'  =>$hash,
            'role'      =>$role,
            'gender'    =>$gender,
            'age'       =>$age,
            'coach_id'  =>$role === 'adherent' ? $coach_id : null,
        ]);

        $_SESSION['user'] = [
            'id' => $id,
            'first_name' => $first,
            'last_name'  => $last,
            'email'      => $email,
            'phone'      => $phone,
            'address'    => $address,
            'role'       => $role,
            'coach_id'   => $role === 'adherent' ? $coach_id : null
        ];
        flash('success','Inscription réussie. Bienvenue !');
        $this->redirect(BASE_URL.'?action=home');
    }

    /** Formulaire de connexion */
    public function loginForm(): void
    {
        $this->render('auth/login', ['title'=>'Connexion']);
    }

    /** Traitement connexion (POST) */
    public function loginPost(): void
    {
        if (!csrf_verify($_POST['_token'] ?? null)) {
            http_response_code(400);
            flash('error','Jeton CSRF invalide.');
            $this->redirect(BASE_URL.'?action=connexion');
        }

        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');
        if ($email === '' || $pass === '') {
            flash('error','Email et mot de passe requis.');
            $this->redirect(BASE_URL.'?action=connexion');
        }

        $u = new User();
        $user = $u->findByEmail($email);
        if (!$user || !password_verify($pass, $user['password'])) {
            flash('error','Identifiants invalides.');
            $this->redirect(BASE_URL.'?action=connexion');
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'email'      => $user['email'],
            'phone'      => $user['phone'],
            'address'    => $user['address'],
            'role'       => $user['role'],
            'coach_id'   => $user['coach_id'] ? (int)$user['coach_id'] : null
        ];
        flash('success','Connexion réussie.');
        $this->redirect(BASE_URL.'?action=home');
    }

    /** Déconnexion */
    public function logout(): void
    {
        unset($_SESSION['user']);
        flash('success','Déconnecté.');
        $this->redirect(BASE_URL.'?action=home');
    }
}
