<?php
/**
 * Created by PhpStorm.
 * User: miquel
 * Date: 28/02/14
 * Time: 11:44
 */

namespace JordiLlonch\Bundle\DeployBundle\Helpers;


class SupervisordHelper extends Helper
{
    public function getName()
    {
        return 'supervisord';
    }

    public function restart($programName)
    {
        try {
            $this->deployer->execRemoteServers($this->restartCommand($programName));
        } catch (\Exception $e) {
            throw new \Exception('Supervisor could not restart.' . $e->getMessage() . ' Code ' . $e->getCode());
        }
    }

    private function restartCommand($programName)
    {
        return 'sudo supervisor_handler.sh -a restart -p ' . $programName . ' -r';
    }
}
