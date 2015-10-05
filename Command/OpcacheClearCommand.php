<?php

namespace EnuygunCom\OpcacheClearBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OpcacheClearCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setDescription('Clear opcache cache')
            ->setName('enuyguncom:opcache:clear')
            ->addOption('host-name', null, InputOption::VALUE_REQUIRED, 'Url for clear opcode cache')
            ->addOption('host-ip', null, InputOption::VALUE_REQUIRED, 'IP for clear opcode cache')
            ->addOption('protocol', null, InputOption::VALUE_REQUIRED, 'Whether to use http or https')
            ->addOption('app_version', null, InputOption::VALUE_REQUIRED, 'Application version to check');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $webDir     = $this->getContainer()->getParameter('enuygun_com_opcache_clear.web_dir');
        $hostName   = $input->getOption('host-name')
            ? $input->getOption('host-name')
            : $this->getContainer()->getParameter('enuygun_com_opcache_clear.host_name');
        $hostIp     = $input->getOption('host-ip')
            ? $input->getOption('host-ip')
            : $this->getContainer()->getParameter('enuygun_com_opcache_clear.host_ip');
        $protocol   = $input->getOption('protocol')
            ? $input->getOption('protocol')
            : $this->getContainer()->getParameter('enuygun_com_opcache_clear.protocol');
        $version   = $input->getOption('app_version')
            ? $input->getOption('app_version')
            : $this->getContainer()->getParameter('enuygun_com_opcache_clear.app_version');

        if (!is_dir($webDir)) {
            throw new \InvalidArgumentException(sprintf('Web dir does not exist "%s"', $webDir));
        }

        if (!is_writable($webDir)) {
            throw new \InvalidArgumentException(sprintf('Web dir is not writable "%s"', $webDir));
        }

        $url = sprintf('%s://%s%s', $protocol, $hostIp, $this->getContainer()->get('router')->generate('enuygun_com_opcache_clear', array('version' => $version), false));

        $checkUrlCount = 0;
        $checkMaxUrl = 10;

        do {
            $headers = $this->mapHeaders(get_headers($url));
            // TODO integrate Curl version here, because this version requires hosts file definition of which might cause internal problems

            $response = isset($headers['x-enuygun-opcache-clear']) ? json_decode($headers['x-enuygun-opcache-clear'], true) : false;
            $appVersion = isset($headers['x-enuygun-app-version']) ? $headers['x-enuygun-app-version'] : null;
            $versionChecked = $appVersion == $version;

            if (! $response) {
                throw new \RuntimeException(sprintf('The response did not return valid json: %s', $response));
            }

            $cleared = isset($response['success']) && $response['success'] === true && $versionChecked;
            $message = isset($response['message']) ? $response['message'] : 'no response';

            $output->writeln(sprintf('<pre>%s</pre>', json_encode(compact('version', 'versionChecked', 'checkUrlCount', 'checkMaxUrl', 'cleared'))));

        } while(++$checkUrlCount < $checkMaxUrl && ! $cleared);

        if($cleared) {
            $output->writeln(sprintf('<info>Opcache cleared after # of %d trials. [x-enuygun-app-version: %s]</info>', $checkUrlCount, $appVersion));
        } else {
            throw new \RuntimeException(sprintf('<error>Opcache is NOT cleared after # of %d trials. Response message was: %s [x-enuygun-app-version: %s]</error>', $checkUrlCount, $message, $appVersion));
        }
    }

    private function mapHeaders($get_headers)
    {
        $headers = array();
        foreach($get_headers as $header) {
            $_keys = explode(':', $header, 2);

            if(sizeof($_keys) > 1) {
                $headers[$_keys[0]] = trim($_keys[1]);
            }else {
                $headers[] = $_keys[0];
            }
        }
        return $headers;
    }
}
