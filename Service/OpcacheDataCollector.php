<?php
/**
 * Created by PhpStorm.
 * User: behcetmutlu
 * Date: 20/12/16
 * Time: 15:48
 */

namespace EnuygunCom\OpcacheClearBundle\Service;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OpcacheDataCollector extends DataCollector
{
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {

        $appFile = __DIR__ . '/../../../../../../web/app.php';
        $appContents = file_get_contents($appFile);
        $commitNumber = null;

        if(preg_match('/\$appVersion = \'(.*)\';/', $appContents, $matches)) {
            $commitNumber = $matches[1];
        }

        $date = new  \DateTime(date ("Y-m-d H:i:s", filemtime($appFile)));

        $statusMap = [
            '-1days' => 'green',
            '-1weeks' => 'yellow',
            '-1year' => 'red'
        ];

        $status = 'green';

        foreach ($statusMap as $time => $status) {
            if( (new \DateTime($time)) < $date) {
                break;
            }
        }

        if(new \DateTime('-1 days'))

        $this->data = array(
            'deployed_at' => $date->format('Y-m-d H:i'),
            'commit_number' => $commitNumber,
            'status' => $status,
            'server_id' => $request->cookies->get('SERVERID', $_SERVER['SERVER_NAME']),
            'method' => $request->getMethod(),
            'acceptable_content_types' => $request->getAcceptableContentTypes(),
        );
    }

    public function getDeployedAt()
    {
        return $this->data['deployed_at'];
    }

    public function getCommitNumber()
    {
        return $this->data['commit_number'];
    }

    public function getStatus()
    {
        return $this->data['status'];
    }

    public function getServerId()
    {
        return $this->data['server_id'];
    }

    public function getAcceptableContentTypes()
    {
        return $this->data['acceptable_content_types'];
    }

    public function getName()
    {
        return 'app.request_collector';
    }
}