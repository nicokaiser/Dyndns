<?php

namespace Dyndns;

/**
 * User database.
 */
class Users
{
    private $userFile;

    public function __construct($userFile)
    {
        $this->userFile = $userFile;
    }

    public function checkCredentials($user, $password)
    {
        $lines = @file($this->userFile);

        if (is_array($lines)) {
            foreach ($lines as $line) {
                if (preg_match("/^(.*?):(.*)/", $line, $matches)) {
                    if (strtolower($matches[1]) == strtolower($user)) {
                        if (crypt($password, $matches[2]) === $matches[2]) {
                            $this->debug('Login successful for user ' . $user);
                            return true;
                        } else {
                            $this->debug('Wrong password for user: ' . $user);
                            return false;
                        }
                    }
                }
            }
        } else {
            $this->debug('Empty user file: "' . $this->userFile . '"');
        }

        $this->debug('Unknown user: ' . $user);
        return false;
    }

    private function debug($message)
    {
        $GLOBALS['dyndns']->debug($message);
    }
}
