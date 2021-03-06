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
        $webDir = $this->getContainer()->getParameter('enuygun_com_opcache_clear.web_dir');
        $hostName = $input->getOption('host-name')
            ? $input->getOption('host-name')
            : $this->getContainer()->getParameter('enuygun_com_opcache_clear.host_name');
        $hostIp = $input->getOption('host-ip')
            ? $input->getOption('host-ip')
            : $this->getContainer()->getParameter('enuygun_com_opcache_clear.host_ip');
        $protocol = $input->getOption('protocol')
            ? $input->getOption('protocol')
            : $this->getContainer()->getParameter('enuygun_com_opcache_clear.protocol');
        $version = $input->getOption('app_version')
            ? $input->getOption('app_version')
            : $this->getContainer()->getParameter('enuygun_com_opcache_clear.app_version');
        $verbose = $input->getOption('verbose');
        $appKey = $this->getContainer()->getParameter('enuygun_com_opcache_clear.app_key');

        if (!is_dir($webDir)) {
            throw new \InvalidArgumentException(sprintf('Web dir does not exist "%s"', $webDir));
        }

        if (!is_writable($webDir)) {
            throw new \InvalidArgumentException(sprintf('Web dir is not writable "%s"', $webDir));
        }

        $url = sprintf('%s://%s%s', $protocol, $hostIp, $this->getContainer()->get('router')->generate('_enuygun_com_opcache_clear', array('version' => $version), false));

        $checkUrlCount = 0;
        $checkMaxUrl = 10;

        do {
            sleep($checkUrlCount * 2);
            if ($verbose)
                $output->writeln(sprintf('URL: <info>%s</info>', $url));

            $curlHeaders = [
                'Host: ' . $hostName
            ];

            $curl_options = array(
                CURLOPT_USERAGENT => 'Enuygun Opcache Clear Agent',
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_VERBOSE => false,
                CURLOPT_FAILONERROR => true,
                CURLOPT_HTTPHEADER => $curlHeaders,
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            );

            $ch = curl_init();
            curl_setopt_array($ch, $curl_options);

            $result = curl_exec($ch);

            // Then, after your curl_exec call:
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($result, 0, $header_size);
            $body = substr($result, $header_size);

            if ($verbose)
                $output->writeln(sprintf('Header: <info>%s</info>', $header));

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);

                $output->writeln(sprintf('Curl error reading "%s" headers: [%s], error: %s', $url, json_encode($curlHeaders), $error));
                exit(0); // exit gracefully
            }

            curl_close($ch);

            $result = json_decode($body, true);

            $headers = $this->mapHeader($header);

            $response = isset($headers['x-enuygun-opcache-clear']) ? json_decode($headers['x-enuygun-opcache-clear'], true) : false;
            $appVersion = isset($headers[$appKey]) ? $headers[$appKey] : null;
            $versionChecked = $appVersion == $version;

            if (!$response) {
                $output->writeln(sprintf('The response did not return valid json: %s', $header));
                exit(0); // exit gracefully
            }

            $cleared = isset($response['success']) && $response['success'] === true && $versionChecked;
            $message = isset($response['message']) ? $response['message'] : 'no response';

            if ($verbose)
                $output->writeln(sprintf('<comment>%s</comment>', json_encode(compact('version', 'appVersion', 'versionChecked', 'checkUrlCount', 'checkMaxUrl', 'cleared'))));

        } while (++$checkUrlCount < $checkMaxUrl && !$cleared);

        if ($cleared) {
            $output->writeln(sprintf('<info>Opcache cleared after # of %d trials. [x-enuygun-app-version: %s]</info>', $checkUrlCount, $appVersion));
        } elseif ($versionChecked) {
            $output->writeln(sprintf('<info>Opcache clear failed but version is up to date after # of %d trials. [%s: %s]</info>', $checkUrlCount, $appKey, $appVersion));
        } else {
            $output->writeln(sprintf('<error>Opcache is NOT cleared after # of %d trials. Response message was: %s [%s: %s]</error>', $checkUrlCount, $message, $appKey, $appVersion));
        }
    }

    private function mapHeaders($get_headers)
    {
        $headers = array();
        foreach ($get_headers as $header) {
            $_keys = explode(':', $header, 2);

            if (sizeof($_keys) > 1) {
                $headers[$_keys[0]] = trim($_keys[1]);
            } else {
                $headers[] = $_keys[0];
            }
        }
        return $headers;
    }

    private function mapHeader($headerStr)
    {
        return $this->mapHeaders(explode(PHP_EOL, $headerStr));
    }
}
