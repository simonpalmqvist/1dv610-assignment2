<?php

namespace auth\model;

require_once('Users.php');
require_once('UserSession.php');
require_once(dirname(__FILE__) . '/../exception/UsernameIsMissingException.php');
require_once(dirname(__FILE__) . '/../exception/PasswordIsMissingException.php');
require_once(dirname(__FILE__) . '/../exception/WrongUsernameOrPasswordException.php');
require_once(dirname(__FILE__) . '/../exception/InvalidCookiesException.php');

class Authentication {
    private $users;

    public function __construct (\PDO $dbConnection) {
        $this->users = new Users($dbConnection);
    }

    public static function userIsAuthenticated () : bool  {
        return UserSession::isActive();
    }

    public function loginUserWithCredentials (string $username, string $password, bool $rememberLogin) {
        $this->validateCredentials($username, $password);
        UserSession::setWith($username);
        if ($rememberLogin) {
            $this->setCookieFor($username);
        }
    }

    public function canLoginWithCookies () : bool {
        return UserSession::hasCookiesSet();
    }

    public function LoginUserWithCookies () {
        try {
            $this->users->findUserWithCookie(UserSession::getCookieUsername(), UserSession::getCookiePassword());
            $this->startSession(UserSession::getCookieUsername());
            $this->setCookieFor(UserSession::getCookieUsername());
        } catch (\Exception $exception) {
            throw new \InvalidCookiesException();
        }
    }

    public function logoutUser () {
        $this->users->removeUserCookie(UserSession::getSessionUsername()); // removes cookie
        UserSession::destroy();
    }

    private function validateCredentials ($username, $password) {
        if (empty($username))
            throw new \UsernameIsMissingException();

        if (empty($password))
            throw new \PasswordIsMissingException();

        $this->matchUsernameAndPassword($username, $password);
    }

    private function matchUsernameAndPassword(string $username, string $password) {
        if (!$this->users->userExists($username)) {
            throw new \WrongUsernameOrPasswordException();
        }

        $user = $this->users->findUser($username);
        $this->validatePassword($password, $user['password']);
    }

    private function validatePassword (string $password, string $candidate) {
        if (!password_verify($password, $candidate))
            throw new \WrongUsernameOrPasswordException();
    }

    private function startSession (string $username) {
        UserSession::setWith($username);
    }

    private function setCookieFor (string $username) {
        $secret = bin2hex(random_bytes(60));
        UserSession::setCookies($secret);
        $this->users->updateUserWithCookie($username, $secret);
    }
}