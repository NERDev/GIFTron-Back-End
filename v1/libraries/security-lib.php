<?php

define('ROOT', realpath($_SERVER['DOCUMENT_ROOT'] . '/..'));
define('PHPROOT', realpath(ROOT . '/git/GIFTron/GIFTron-Back-End'));
define('WEBROOT', realpath(ROOT . '/webroot'));
define('VERSION', preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4]);
define('APIROOT', '/giftron/api/' . VERSION);

//add defines for phproot, webroot, version, and whitelist

class Security
{
    function __construct()
    {
        //$this->phproot  = realpath(ROOT . '/git/GIFTron/GIFTron-Back-End');
        //$this->webroot  = realpath(ROOT . '/webroot');
        //$this->version  = preg_split('/[\\x5c\/]/', str_replace(ROOT, '', __FILE__))[4];
        $this->phproot = PHPROOT;
        $this->webroot = WEBROOT;
        $this->version = VERSION;
        $this->apiroot = APIROOT;
    }

    function trusted_server($ip)
    {
        return in_array($ip, json_decode(file_get_contents("$this->phproot/metadata/whitelist")));
    }

    function require_methods($methods)
    {
        $methods = gettype($methods) == "array" ? $methods : [$methods];
        return in_array($_SERVER['REQUEST_METHOD'], $methods);
    }

    function respond($status, $data)
    {
        http_response_code($status);
        //$data = gettype($data) == "object" ? $data : (object)$data;
        $data = json_decode($data) ? $data : json_encode($data);
        exit($data);
    }

    protected function redirect($url, $immediate = true)
    {
        http_response_code(302);
        header("Location: $url");
        if ($immediate)
        {
            exit("Redirecting...");
        }
        else
        {
            echo "Redirecting...";
        }
    }

    /*
    protected function parse_session($sessionID)
    {
        return $this->storage->read("sessions/$sessionID")->data;
    }
    */

    function sanitize()
    {
        
    }

    function is_staff($user_id = null)
    {
        $user_id = $user_id ?: $this->user->id;
        try {
            return in_array(NERDEV_EMPLOYEE, $this->discord->bot->guilds->{NERDEV}->members->$user_id->info->roles);
        } catch (\Throwable $th) {
            return false;
        }
    }
    
    function permitted($guildID)
    {
        try {
            $owner = $this->discord->bot->guilds->$guildID->info->owner_id == $this->user->id;
        } catch (\Throwable $th) {
            return false;
        }

        if ($owner)
        {
            //return "this user is owner";
            return true;
        }

        //check if user's in an access role
        $guild = $this->storage->read("guilds/$guildID")->data;
        if ($guild->settings->access_roles && array_intersect($guild->settings->access_roles, $this->discord->bot->guilds->$guildID->members->{$this->user->id}->info->roles))
        {
            //return "this user is in one or more access role";
            return true;
        }

        if (!$guild->settings->strict)
        {
            //build table of roles and permissions
            foreach ($this->discord->bot->guilds->$guildID->info->roles as $role)
            {
                $serverRoles[$role->id] = $role->permissions;
            }


            //lookup permissions of roles user is a part of
            //first, get every role for the user
            foreach ($this->discord->bot->guilds->$guildID->members->{$this->user->id}->info->roles as $role)
            {
                //next, get the permissions for this role
                foreach($this->discord->list_permissions($serverRoles[$role]) as $perm)
                {
                    in_array($perm, $perms) ?: $perms[] = $perm;
                }
            }

            if (array_intersect(["generalAdministrator", "generalManageServer"], $perms))
            {
                //return "this user has sufficient privileges, and 'strict' is not set for the server";
                return true;
            }
        }

        //none of the conditions were met: this user is not permitted
        return false;
    }

    function parseException($exception)
    {
        $trace = (object)$exception->getTrace()[1];
        $where = "$trace->class$trace->type$trace->function";
        $details = json_decode($exception->getMessage());
        if (substr($exception->details->HTTP, 0, 1) == 5)
        {
            $code = 500;
            $message = "We are having trouble contacting Discord.";
        }
        
        if ($exception->details->HTTP == 429)
        {
            $code = 500;
            $message = "We are being rate-limited. Please try again later.";
        }
        return (object)["code" => $code, "message" => $message, "details" => $details];
    }

    //Old Hash Function
    /*
    protected function hash($id)
    {
        $half = strlen($id) / 2;
        $firsthalf = substr($id, 0, $half);
        $secondhalf= substr($id, $half);
        
        
        $firsttotal = 0;
        $secondtotal = 0;
        for($i = 0; $i<$half; $i++)
        {
            $firsttotal += $firsthalf[$i];
            $secondtotal += $secondhalf[$i];
        }
        
        $firstletter = ALPHABET[$firsttotal % 26];
        $secondletter = ALPHABET[$secondtotal % 26];

        return "$firstletter$secondletter$id";
        
        //$id = incoming id;
        //logic!
        //return outgoing id;
    }
    */
}